<?php

namespace Core;

/**
 * Global configurations for the script.
 */
class ConfigDefaults {
	/**
	 * Title of this website.
	 * @var string
	 */

	const TITLE = "";
	/**
	 * Base URL that to the website.  Inclues trailing slash.
	 * @var string
	 */
	const BASE_URL = "";

	/**
	 * Base URL that to the website.  Inclues trailing slash.
	 * @var string
	 */
	const ASSETS_URL = "";

	/**
	 * SQL server configuration file.
	 * @var string
	 */
	const SQL_CONFIG = null;
	
	/**
	 * Sets the logging location for this request. <br />
	 * 0: The log goes into an empty void, never to be seen again. <br />
	 * 1: Saves the log to the configured error log. <br />
	 * 2: Outputs the log to a javascript console.log command. <br />
	 * 3: Saves the log to the log SQL database.
	 * 
	 * @var int
	 */
	const LOG_LOCATION = 1;

	/**
	 * Defines to log debug information or not.
	 * @var bool
	 */
	const LOG_DEBUG = true;

	/**
	 * Defines to log debug information or not.
	 * @var bool
	 */
	const LOG_SQL_QUERIES = true;

	/**
	 * If set to true, the memory usage will be displayed at every log output.
	 * @var bool
	 */
	const LOG_MEMORY_USAGE = true;

	/**
	 * If set to true, the memory usage only report deltas in the memory.
	 * Set to false if you want to se absolute values for the memory usage.
	 * @var bool
	 */
	const LOG_MEMORY_USAGE_DELTA = true;

	/**
	 * Defines the file to log to.
	 * @var string
	 */
	const LOG_FILE = "error.log";

	/**
	 * If set to true, the logger will log every load of Helpers, Libraries, etc...
	 * @var bool
	 */
	const LOG_LOADER = false;

	/**
	 * If set to true, the logger will output the log items immediately to the output buffer.
	 * @var bool
	 */
	const LOG_IMMEDIATE_MODE = false;

	/**
	 * If set to true, the firebug output console group will start collapsed.
	 * Good for debugging javascript.
	 * @var bool
	 */
	const LOG_COLLAPSED_GROUPS = true;

	/**
	 * Default view name to load.
	 * @var string 
	 */
	const VIEW_DEFAULT = "default";

	/**
	 * View to use for info output.
	 * @var string
	 */
	const VIEW_INFO = "info";

	/**
	 * Defines the default controller to use.
	 * @var string 
	 */
	const CONTROLLER_DEFAULT = "Index";

	/**
	 * Defines the default method to call in the controller.
	 * @var string 
	 */
	const CONTROLLER_DEFAULT_METHOD = "default_index";

	/**
	 * This is a prefix attached to all cookies and.
	 * @var string
	 * @remark Leave an empty string (NOT NULL) to not use a prefix.
	 */
	const COOKIE_PREFIX = "";

	/**
	 * Set to true to enable output caching.
	 * Used by the Cache component.
	 * @var bool
	 */
	const CACHE_OUTPUT = false;

	/**
	 * Components to load at execution.
	 * @var array
	 */
	public static $COMPONENT_LOAD = array();

	/**
	 * Used by the router to determine what forwarders to use.
	 * @var array
	 */
	public static $ROUTER_FORWARDERS = array();

	/**
	 * Settings to load from the database on-application-load.
	 * Used by the Settings component.
	 * 
	 * @var array
	 */
	public static $SETTINGS_AUTOLOAD = array("core.view.default",
		"core.user.session_max_time",
		"core.user.session_verify_user_agent");

	/**
	 * True to have the server compress the PHP output for with GZip compression.
	 * @var bool
	 */

	const COMPRESS_OUTPUT = true;

	/**
	 * If set to true, classes are no longer required to be manually loaded with the \Core\Load class.
	 */
	const AUTOLOAD_CLASSES = true;

}