<?php


class UsersModel extends \Core\Model {

	/**
	 * Model table name.
	 * @var string
	 */
	protected static $name = "Users";

	/**
	 * Model table columns.
	 * @var array
	 */
	public static $columns = array("id", "username", "Permissions_id", "UserProfiles_id", "password", "email", "date_registered", "activation_code", "banned", "ban_reason", "last_online");

// ----- DO NOT EDIT ON OR ABOVE THIS LINE! CODE AUTOMATICALLY GENERATED. ----- 
}