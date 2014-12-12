<?

/**
 * Base path to the current working directory. 
 */
define('BASEPATH', rtrim(dirname(__FILE__), '/') . "/");
/**
 * Set the environment that we are working with. <br />
 * development = Enviroment on development server. <br />
 * testing = Enviroment on testign server prepping for deployment.<br />
 * release = Production enviroment.<br />
 */
define("ENVIRONMENT", "development");

/**
 * Sets the default application directory to load on startup.
 */
define("APP_DIR", "application");

/**
 * Defines a custom configuration file to load.  Leave empty to load the default config file.
 */
$configuration = "default";

switch (ENVIRONMENT) {
	case 'development':
		error_reporting(-1);
		define("DEBUG", true);
		define("RELEASE", false);
		break;
	case 'testing':
		error_reporting(0);
		define("DEBUG", true);
		define("RELEASE", false);
		break;
	case 'release':
		error_reporting(0);
		define("DEBUG", false);
		define("RELEASE", true);
		break;
}

if(file_exists(BASEPATH . APP_DIR . "/" . $configuration . ".config.php") === false) {
	die("Could not load the configuration file for the " . ENVIRONMENT . " " . $configuration . " enviroment.");
}

// Require configurations.
require(BASEPATH . "core/config_defaults.php");
require(BASEPATH . APP_DIR . "/" . $configuration . ".config.php");

// Load and initialize cache class to check if we can load a cached version of this page.


require(BASEPATH . "core/log.php");
\Core\Log::initialize();

// Load base classes.
require(BASEPATH . "core/controller.php");
require(BASEPATH . "core/view.php");
require(BASEPATH . "core/model.php");
require(BASEPATH . "core/database.php");
require(BASEPATH . "core/component.php");
require(BASEPATH . "core/globals.php");
require(BASEPATH . "core/router.php");
require(BASEPATH . "core/main.php");

//require(BASEPATH . APP_DIR . "/components/settings.php");
//require(BASEPATH . APP_DIR . "/components/user.php");

// Start up the server!
new \Core\Main();