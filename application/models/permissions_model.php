<?php

// WARNING! This script is partially automatically generated!
// Write manual scripts below the marked line.

class PermissionsModel extends \Core\Model {

	/**
	 * Model table name.
	 * @var string
	 */
	protected static $name = "Permissions";

	/**
	 * Model table columns.
	 * @var array
	 */
	public static $columns = array("id", "name", "base_permissions_id", "can_register", "can_login", "can_edit_users", "can_edit_settings", "can_manage_images");

// ----- DO NOT EDIT ON OR ABOVE THIS LINE! CODE AUTOMATICALLY GENERATED. ----- 

	/**
	 * Gets the permission set for the given ID.  If the user is of a sub-group, then 
	 * the parent group is returned as the first value.
	 *
	 * @param int $id Permission ID set.
	 * @return array Returns associative array of the permission set.
	 */
	public static function getPermissionSet($id) {
		$statement = self::$db->prepare("
			SELECT *
			FROM Permissions
			WHERE id=:id_int
			OR id = (SELECT base_permissions_id FROM Permissions where id=:id_int)
			LIMIT 2;");

		$statement->bindValue(":id_int", $id, PDO::PARAM_INT);

		return self::$db->executeFetchAll($statement, PDO::FETCH_ASSOC);
	}

}

