<?php

class SessionsModel extends \Core\Model {

	/**
	 * Model table name.
	 * @var string
	 */
	protected static $name = "Sessions";

	/**
	 * Model table columns.
	 * @var array
	 */
	public static $columns = array("id", "Users_id", "hash", "last_active", "user_data", "user_agent", "ipv4");

// ----- DO NOT EDIT ON OR ABOVE THIS LINE! CODE AUTOMATICALLY GENERATED. ----- 

}