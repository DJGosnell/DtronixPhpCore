<?php

/**
 * Global configurations for the script.
 */
class Config extends \Core\ConfigDefaults {

	const TITLE = "Dtronix PHP Core";

	/**
	 * Base URL that to the website.  Inclues trailing slash.
	 * @var string
	 */
	const BASE_URL = "http://";

	/**
	 * Base URL that to the website.  Inclues trailing slash.
	 * @var string
	 */
	const ASSETS_URL = "http://";

	/**
	 * SQL server configuration file.
	 * @var string
	 */
	const SQL_CONFIG = "default";
	//const LOG_IMMEDIATE_MODE = true;

	const LOG_SQL_QUERIES = false;
	const LOG_COLLAPSED_GROUPS = false;
	

	/**
	 * Sets the logging location for this request. <br />
	 * 0: The log goes into an empty void, never to be seen again. <br />
	 * 1: Saves the log to the configured error log. <br />
	 * 2: Outputs the log to a javascript console.log command. <br />
	 * 3: Saves the log to the log SQL database.
	 * 
	 * @var int
	 */
	const LOG_LOCATION = 2;

	/**
	 * Components to load at execution.
	 * @var array
	 */
	public static $COMPONENT_LOAD = array("SettingsComponent", "AuthComponent");

}

