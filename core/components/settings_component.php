<?php

namespace Core;

/**
 * Manages retrieving and setting system wide options. 
 */
class SettingsComponent extends Component {

	/**
	 * Contains a cache for the loaded properties.
	 * @var array 
	 */
	private $cached_properties;

	/**
	 * Constructor called on class initialization.
	 * @param \Core\Main $main Main class.
	 */
	public function __construct() {
		parent::__construct($this);
	}

	public function onRun() {
		self::load(\Config::$SETTINGS_AUTOLOAD);
	}

	/**
	 * Gets a property value from the master settings list.
	 * 
	 * @param string $property Property to lookup.
	 * @param mixed $default_value If the value does not exist in the database, this value will be saved and returned.
	 * 
	 * @return mixed Sored value if found, false otherwise.
	 */
	public static function get($property, $default_value = false) {
		// Check to see if the property is cached already.
		if(isset(self::$instance->cached_properties[$property])) {
			return self::$instance->cached_properties[$property]["value"];
		}

		// We don't have a cahced value for this property, so we need to get the value from the database.
		$returned_value = \SettingsModel::select()->
			where("property", $property)->
			executeFetch();

		Log::debug("Fetched property: " . $property);
		// Check to see if we have a default value set.
		if($returned_value === false) {
			if($default_value === false) {
				// No default value was given so we need to send an error.
				Log::error("Unknown property value: " . $property . " requested.");
			}
			\SettingsModel::insert(array(
				"property" => $property,
				"value" => $default_value,
				"description" => "Automaticly generated."
			));
			$returned_value = \SettingsModel::getByProperty($property);
		}

		// Cache the property.
		self::$instance->cached_properties[$property] = $returned_value;

		return $returned_value["value"];
	}

	/**
	 * Sets a property value from the master settings list.  Throws exception if property was not found.
	 * @param type $property Property to lookup.
	 * @return mixed Sored value if found, false otherwise.
	 */
	public static function set($property, $value) {
		$returned_value = \SettingsModel::getByProperty($property);
		if($returned_value == false) {
			return false;
		}

		return \SettingsModel::update(array(
				"value" => $value
				), $returned_value["id"]);
	}

	/**
	 * Loads a set of given properties into the cache.
	 * 
	 * @param string[] $properties Array of properties to load.
	 */
	public static function load($properties) {
		$returned_properties = \SettingsModel::select(array(
				"id",
				"property",
				"value"
			))->whereIn("property", $properties)->executeFetchAll();

		// Ensure that we have the same number of properties as we requested.  If not, we need to let the admin know.
		if(count($properties) !== count($returned_properties)) {
			Log::error("Preload of settings failed.  Did not find all settings requested. \nSettings reuqsted: " . json_encode($properties) . " \nSettings recieved: " . json_encode($returned_properties));
		}

		foreach($returned_properties as $value) {
			self::$instance->cached_properties[$value["property"]] = array("id" => $value["id"], "value" => $value["value"]);
		}
	}

}