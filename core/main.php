<?php

/**
 * Contains all critical functionality components for the opperation of the application 
 */

namespace Core;

use \PDO,
	\PDOException;

/*
 * Class to handle all incoming connections and return the proper data to the client.
 */

class Main {

	/**
	 * Instance of the main class.
	 * @var \Core\Main
	 */
	private static $instance;

	/**
	 * Array of references for all the loaded components.
	 * @var array
	 */
	private $components;

	/**
	 * Handles all routing and forwarding functionality.
	 * @var \Core\Router
	 */
	private $router;

	/**
	 * Contains a list of all loaded class files.
	 * @var array
	 */
	private $loaded_files;

	public function __construct() {
		self::$instance = &$this;

		// Turn on output buffering.
		ob_start();

		// Handle any PHP errors.
		set_error_handler("\Core\handleError");
		spl_autoload_register(array(&$this, "autoloadClass"));

		$this->router = new Router();

		// Load required components.
		foreach(\Config::$COMPONENT_LOAD as $component) {
			require(BASEPATH . "core/components/" . namingConversion("camel", "underscore", $component) . ".php");
			$class_name = '\Core\\' . $component;

			$this->$component = new $class_name();
			$this->components[$component] = &$this->$component;
		}

		if(DEBUG) {
			Log::debug("Main class started. Connecting to SQL DB...");
		}

		if(\Config::SQL_CONFIG != null) {
			Model::$db = Database::connectFromConfig(\Config::SQL_CONFIG);
		}

		$this->run();
	}

	/**
	 * Called by spl_autoload_register to handle all loading of requred classes.
	 * 
	 * @param string $fully_qualified_name Full name of the class to load with included namespaces
	 */
	private function autoloadClass($fully_qualified_name) {
		$namespaces = explode("\\", $fully_qualified_name);
		$class_name = array_pop($namespaces);
		$class_name_len = strlen($class_name);
		$namespaces_count = count($namespaces);
		$class_path = BASEPATH;
		$skip_first_namespace = false;

		// Find the correct path for the type of class.
		if($class_name_len > 3 && strpos($class_name, "Lib", $class_name_len - 3)) {
			$class_path .= "core/libs";

			if(isset($namespaces[0]) && $namespaces[0] == "Core") {
				$skip_first_namespace = true;
			}
		} elseif($class_name_len > 5 && strpos($class_name, "Model", $class_name_len - 5)) {
			$class_path .= APP_DIR . "/models";
		} elseif($class_name_len > 6 && strpos($class_name, "Helper", $class_name_len - 6)) {
			$class_path .= "core/helpers";

			if(isset($namespaces[0]) && $namespaces[0] == "Core") {
				$skip_first_namespace = true;
			}
		} elseif($class_name_len > 9 && strpos($class_name, "Component", $class_name_len - 9)) {
			Log::debug("To enable component class, use configuration file.");
			Log::error("Attempted to autoload component class $fully_qualified_name.");
		} else {
			Log::error("Unable to determine type of class for: " . $fully_qualified_name, true);
		}

		$this->loaded_files[] = $fully_qualified_name;

		// Convert the actual namespaces to underscore notation for directory transversal.
		for($i = ($skip_first_namespace) ? 1 : 0; $i < $namespaces_count; $i++) {
			$class_path .= "/" . namingConversion("camel", "underscore", $namespaces[$i]);
		}

		// Add the converted class name with the php extention.
		$class_path .= "/" . namingConversion("camel", "underscore", $class_name) . ".php";

		require($class_path);
	}

	/**
	 * Starts execution of the server by calling the requested method.
	 */
	private function run() {

		// Fire the onRun events.
		foreach($this->components as $component) {
			/* @var $component \Core\Component */
			$component->onRun();
		}

		// Do any required forwarding.
		$this->router->forwardUrl();

		// Start by parsing the request.
		$this->router->parseUri();

		Log::debug("Loading controller file: " . $this->router->ClassFile);
		require($this->router->ClassFile);

		// Get the class information.
		$class_reflection = new \ReflectionClass($this->router->Class);

		$class_proper_name = substr($class_reflection->name, 0, strpos($class_reflection->name, "Controller"));

		// Create a controller class instance.
		$controller_class = $class_reflection->newInstanceArgs();

		// Ensure that it has the method we are wanting to call.
		if($class_reflection->hasMethod($this->router->Method) === false) {

			// Set the method to be the controller default.
			$this->router->Method = \Config::CONTROLLER_DEFAULT_METHOD;
		}

		// Get the method and its information.
		$method = $class_reflection->getMethod($this->router->Method);
		// If the user did not provide enough parameters, say that there has been a critical error.
		if(count($this->router->Arguments) < $method->getNumberOfRequiredParameters()) {
			View::info("Malformed request.  Please go back a page and try again.");
			die();
		}

		Log::debug("Controller: " . $class_proper_name . "->" . $method->name . "() Started.");

		// Call the method
		try {
			$method->invokeArgs($controller_class, $this->router->Arguments);
			if(View::$auto_header_footer === true) {
				View::output("footer");
			}
		} catch(\Exception $e) {
			if(RELEASE) {
				ob_clean();
			}
			View::output("error500");
		}
		Log::debug("Controller exited.");
	}

	/**
	 * Destructor to handle finalization operations such as SQL operations.
	 */
	public function __destruct() {
		// Fire the onExit events.
		foreach($this->components as $component) {
			/* @var $component \Core\Component */
			$component->onExit();
		}

		foreach(\Core\Database::$databases as $database) {
			$database->executeTransactionStatements();
		}

		Log::line("(Max Memory Usage) System: " . memory_get_peak_usage(true) / 1000 . " KBytes; PHP: " . memory_get_peak_usage(false) / 1000 . " KBytes;");

		// Output the log if so desired.
		Log::outputLog();
	}

}