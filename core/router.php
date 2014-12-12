<?php

namespace Core;

use Config;

/**
 * Handles the user's GET requests.  Opens and executes the correct scripts
 * or passes them on to another address.
 */
class Router {

	/**
	 * Arguments that are parsed from the input URI.
	 * 
	 * @var array
	 */
	public $Arguments;

	/**
	 * Controller file name and class name to open.
	 * 
	 * @var string
	 */
	public $Class;

	/**
	 * Full path to the class file requested.
	 * @var string
	 */
	public $ClassFile;

	/**
	 * Controller method to call inside the defined class.
	 * 
	 * @var string
	 */
	public $Method;

	/**
	 * Contains current requested route.
	 * 
	 * @var string
	 */
	private $route;

	/**
	 * Contains all forwarders that this router respects.
	 * @var array
	 */
	private $forwarders;

	/**
	 * Parses input URL handles forwards as necessary.
	 */
	public function __construct() {
		$path = $_SERVER["REQUEST_URI"];

		$this->forwarders = Config::$ROUTER_FORWARDERS;

		// Remove the preceding forward slash.
		if($path[0] === "/") {
			$path = substr($path, 1);
		}

		$this->route = $path;
	}

	/**
	 * Forwards the URL based on any forwarders that are loaded.
	 */
	public function forwardUrl() {
		if(count($this->forwarders) === 0) {
			return;
		}

		$search = array_keys($this->forwarders);
		$replace = array_values($this->forwarders);
		$this->route = preg_replace($search, $replace, $this->route, 1);
	}

	/**
	 * Routes incomming connection to correct location and returns files and parameters. 
	 */
	public function parseUri() {
		// Get all the parts of the url for parsing.
		$url_parts = explode("/", $this->route);

		// Get the class.  If one was not set, go to the default.
		if(isset($url_parts[0]) && $url_parts[0] !== "") {
			// Get the class that the user specified.
			$this->Class = namingConversion("hyphen", "camel", $url_parts[0]) . "Controller";
		}

		$this->ClassFile = BASEPATH . APP_DIR . '/controllers/' . namingConversion("hyphen", "underscore", $url_parts[0]) . "_controller.php";

		// Ensure that the class specified is valid!
		if(file_exists($this->ClassFile) === false) {
			// Woops.  We did not have a valid class file.  Give the default controller.
			$this->Class = "IndexController";
			$this->ClassFile = BASEPATH . APP_DIR . "/controllers/index_controller.php";
		}

		// Get the method.  If one was not set, go to the default.
		if(isset($url_parts[1])) {
			// Transform dashes to underscores.
			$this->Method = str_replace("-", "_", $url_parts[1]);
		} else {
			$this->Method = Config::CONTROLLER_DEFAULT_METHOD;
		}

		// Check to see if we have any arguments.  If so, store them in the Arguments property.
		if(count($url_parts) > 2) {
			$this->Arguments = array_slice($url_parts, 2);
		} else {
			// We did not have arguments.  Give back an empty array.
			$this->Arguments = array();
		}
	}

	/**
	 * Redirects a browser to a location internal to the program. <br>
	 * Example: Router::redirect("User/logout");
	 * 
	 * @param string $location Internal URL to direct the user to.
	 */
	public static function redirect($location) {
		header("Location: " . Config::BASE_URL . $location);
		die();
	}

}

