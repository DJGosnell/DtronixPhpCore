<?php

namespace Core;

use Core\SettingsComponent;

/**
 * Class to handle the connected user.  Handles the client and sessions.
 * 
 * REQUIRES:
 * 	Settings component.
 */
class AuthComponent extends Component {

	/**
	 * User ID. -1 indicates a anonymous user.
	 * @var int 
	 */
	public $Id = -1;

	/**
	 * Array of the user's database row.
	 * @var array
	 */
	public $UserInfo;

	/**
	 * True if the connected client is logged-in, false otherwise.
	 * @var bool
	 */
	private $is_logged_in = false;

	/**
	 * IPv4 address of the client.
	 * @var string
	 */
	public $Ipv4 = "0.0.0.0";

	/**
	 * Contains all the permissions for this user.
	 * @var array
	 */
	private $permissions = null;

	/**
	 * Permission ID that is associated with this account.  Set to Guest by default.
	 * @var int
	 */
	private $permission_id = 3;

	/**
	 * Contains the current session id for the user, otherwise null if the client does not have a session.
	 * @var string
	 */
	private $session_id = null;

	/**
	 * Internal variable to prevent multiple session checking.  Cab be true even when the user is not logged in.
	 * @var bool 
	 */
	private $verified_session = false;

	/**
	 * Name of the session cookie name with a prefix depending on the system configurations.
	 * @var string
	 */
	private $session_cookie_name;

	/**
	 * Contains the entire profile array for this user.  Null if no profile has been loaded.
	 * @var array
	 */
	private $profile = null;

	/**
	 * Constructor called on class initialization.
	 * @param \Core\Main $main Main class.
	 */
	public function __construct() {
		parent::__construct($this);

		Log::debug("Initializing User component.");

		$this->session_cookie_name = \Config::COOKIE_PREFIX . "session";

		// Get the user's IP address.
		if(isset($_SERVER["REMOTE_ADDR"])) {
			$this->Ipv4 = $_SERVER["REMOTE_ADDR"];
		}

		// TODO: IMpliment banned IP addresses/ranges.
	}

	public function onRun() {
		$this->verifySession();
		$this->getPermissions();

		Log::debug("Session is " . (($this->is_logged_in) ? "Valid." : "Invalid."));
	}

	/**
	 * Returns user logging status.
	 * 
	 * @return bool True if user is logged in, false otherwise.
	 */
	public static function isLoggedIn() {
		return self::$instance->is_logged_in;
	}

	/**
	 * Checks a permission against the loaded permission set for this user.
	 * 
	 * @param string $permission Permission value to check.
	 * 
	 * @return bool
	 */
	public static function getPermission($permission) {
		return (self::$instance->permissions[$permission] == "1") ? true : false;
	}

	public static function permissionGroup() {
		return self::$instance->permissions["name"];
	}

	public static function getProfileItem($name) {
		if(self::$instance->profile === null) {
			self::$instance->loadProfile();
		}

		if(array_key_exists($name, self::$instance->profile) === false) {
			Log::error("Trying to retrieve profile property '" . $name . "' which does not exist.");
		}

		return self::$instance->profile[$name];
	}

	private function loadProfile() {
		if($this->is_logged_in === false) {
			Log::error("Trying to load user profile on a client that is not logged in.");
		}



		$user_profile = \UserProfilesModel::select()->
				where("id", $this->UserInfo["UserProfiles_id"])->
				limit(0, 1)->executeFetch();

		if($user_profile === false) {
			Log::error("Unable to get user profile for user: '" . $this->Username . "'.");
		}

		$this->profile = $user_profile;
	}

	/**
	 * Method to login a user with the provided username and password.
	 * @param string $username User's username.
	 * @param string $password MD5 hashed password.
	 * 
	 * @return bool
	 */
	public static function login($username, $password) {

		if(self::$instance->is_logged_in) {
			return true;
		}

		// Basic checks on the username first.
		if(empty($username)) {
			return false;
		}

		// Ensure that the user exists.
		$user_info = \UsersModel::select()->
				where("username", $username)->
				where("password", $password)->executeFetch();

		if($user_info === false) {
			return false;
		}

		// Set the info on this class.
		self::$instance->Username = $user_info["username"];
		self::$instance->Id = $user_info["id"];
		self::$instance->is_logged_in = true;

		// Create the session and ensure that it was successful.
		if(self::$instance->createSessionCookieAndRow() === false) {
			return false;
		}

		return true;
	}

	/**
	 * Logs the current user out of the system.  The system will try to reset all variables related
	 * to the user's login status, but as a general practice, don't do anything that is user-specifig
	 * crucial after calling this method.
	 * 
	 * @return void
	 */
	public static function logout() {
		if(self::$instance->is_logged_in === false) {
			return;
		}

		self::$instance->deleteSessionCookie();
		self::$instance->deleteSessionRow();

		self::$instance->Username = "";
		self::$instance->Id = -1;
		self::$instance->is_logged_in = false;
		self::$instance->permission_id = 3;
		self::$instance->getPermissions();
	}

	/**
	 * Regenerate a session ID for the user and set a new cookie.
	 * 
	 * @return void
	 */
	public static function regenerateSession() {
		// If the user is not logged in, there is nothing we can do.
		if(self::$instance->is_logged_in === false) {
			return;
		}

		Log::info("Regenerating Session");
		// Instruct the method to update the existing database session instead of creating a new one.
		self::$instance->createSessionCookieAndRow(true);
	}

	/**
	 * Verifies the session data that the user has with the server.  If the user
	 * does not have a session, they are setup with guest access.
	 * 
	 * @return void
	 */
	private function verifySession() {

		// If we have already verified the session, there is nothing more to do.
		if($this->verified_session) {
			return;
		}

		// Go ahead and set the state to verified session.
		$this->verified_session = true;

		// Get the session ID if the client has one.
		if(isset($_COOKIE[\Config::COOKIE_PREFIX . "session"]) === false) {
			return;
		}

		// Get the ID and separate it from the hash.
		$session_parts = explode("-", $_COOKIE[\Config::COOKIE_PREFIX . "session"]);

		// Verify that we have the proper number of parts to this session string.
		if(count($session_parts) !== 2) {
			return;
		}

		//Get the session ID from the first part of the string.
		$this->session_id = $session_parts[0];

		// Session has is in the second part.
		$session_hash = $session_parts[1];


		// See if the user had a hash or not.
		if($this->session_id === null || empty($this->session_id)) {
			return;
		}
		// Search to see if the database contains the requested hash.

		$session_info = \SessionsModel::select(array(
					"Sessions.*",
					"JUsers_id.*"
				))->
				join("Users_id")->
				where("Sessions.id", $this->session_id)->
				limit(0, 1)->executeFetch();

		// Verify that we have a session.
		if($session_info === false) {
			$this->deleteSessionCookie();
			return;
		}

		// Verify that the has matches.
		if($session_info["hash"] !== $session_hash) {
			// It did not match our records.  Delete the cookie!
			$this->deleteSessionCookie();
			return;
		}

		// Check to see if the user is banned.
		if($session_info["banned"] == "1") {
			// Abandon ship.  Quit everything at this point and alert the user what has happened to his account.
		}

		// See if we should verify the user agent.
		if(SettingsComponent::get("core.user.session_verify_user_agent") == "1") {
			//TODO: Write this code later.
		}

		// Check for session timeout.
		if(SettingsComponent::get("core.user.session_max_time") + $session_info["last_active"] <= time()) {
			$this->deleteSessionCookie();
			$this->deleteSessionRow();
			return;
		}

		// Update the last active time.
		$session_id = $this->session_id;
		\SessionsModel::update(array(
				"ipv4" => $_SERVER["REMOTE_ADDR"],
				"last_active" => time()
			))->where("id", $session_id)->
			executeTransaction();

		// Copy the loaded user information.
		$this->Id = $session_info["id"];
		$this->permission_id = $session_info["Permissions_id"];
		$this->UserInfo = $session_info;
		$this->is_logged_in = true;


		// Check to see if this is the first visit to the website on this borwser session.
		if(key_exists(\Config::COOKIE_PREFIX . "visit", $_COOKIE) === false) {
			$this->regenerateSession();
			setcookie(\Config::COOKIE_PREFIX . "visit", true, 0, "/");
		}
	}

	/**
	 * Delete the cookie from the user's browser.
	 */
	private function deleteSessionCookie() {
		setcookie($this->session_cookie_name, null, 0, "/");
	}

	/**
	 * Remove the session row from the database.
	 */
	private function deleteSessionRow() {
		if($this->is_logged_in) {
			\SessionsModel::delete()->
				where("id", $this->session_id)->executeTransaction();
		}
	}

	/**
	 * Create a session in the database and set the user's session cookie.
	 * 
	 * @param string $existing_session
	 * @return boolean 
	 */
	private function createSessionCookieAndRow($use_existing_session = false) {
		$session_row = -1;

		// Generate a 64 character hash for the user session.
		$new_hash = StringHelper::random(40);
		$user_agent_hash = md5($_SERVER["HTTP_USER_AGENT"]);

		// See if the user has a session already.  If so, update it. If not, then create it.
		if($use_existing_session && $this->is_logged_in) {
			// Update an existing row.
			\SessionsModel::update(array(
					"ipv4" => $this->Ipv4,
					"last_active" => time(),
					"hash" => $new_hash
				))->
				where("id", $this->session_id)->executeTransaction();

			// Set the row to the ID since we are updating the existing session.
			$session_row = $this->session_id;
		} else {
			// Generate a completely new row.
			$session_row = \SessionsModel::insert(array(
					"ipv4" => $this->Ipv4,
					"last_active" => time(),
					"user_agent" => $user_agent_hash,
					"hash" => $new_hash,
					"Users_id" => $this->Id
				))->executeInsertId();
		}


		// Ensure that the session was created.
		if($session_row === -1) {
			Log::error("Could not generate a session row for the user: " . $this->Username, false);
			return false;
		}

		// Generate the experation time from the settings.
		$cookie_expiration_date = time() + SettingsComponent::get("core.user.session_max_time");

		// Set the session cookie.
		setcookie($this->session_cookie_name, $session_row . '-' . $new_hash, $cookie_expiration_date, "/");

		return true;
	}

	/**
	 * Gets the permission set from the database for this user.
	 */
	private function getPermissions() {

		$permissions_result = \PermissionsModel::getPermissionSet($this->permission_id);
		// Ensure that we were successful
		if($permissions_result === false) {
			Log::error("Could not fetch user permissions for User: " . $this->Id);
		}

		// Set this variable to our base permissions.
		$permissions_set = $permissions_result[0];

		// Check to see if we have base permissions in addition to the user specific settings.
		if(count($permissions_result) > 1) {

			// Loop through the overrides.
			foreach($permissions_result[1] as $permission => $value) {

				// If the value is not null, then it is an override.
				if($value !== null) {

					// Overrwrite with user specific value.
					$permissions_set[$permission] = $value;
				}
			}
		}

		$this->permissions = $permissions_set;
	}

	public static function getUserInfo($field) {
		return self::$instance->UserInfo[$field];
	}

	// Helper Methods

	/**
	 * Calling this method ensures that the user is logged in before any more code
	 * is executed.  If the user is not logged in, the script will halt and output an error.
	 */
	public static function requireSession() {
		if(self::$instance->isLoggedIn() === false) {

			// Flush any excess information that we have in the buffer.
			ob_flush();

			// Clean the OB so that nothing that was not meant to be released, is.
			ob_clean();

			View::output("header", array("title" => "Error: User Not Logged In."));
			View::info("You must be logged in to use this feature.");
			View::output("footer");
			// Abandon ship.
			die();
		}
	}

	/**
	 * Call to verify that the user has sufficient permissions to access the page that this method is called on.
	 * 
	 * @param mixed $permissions Array or String of a permission to check on the logged user.
	 * @retuns void
	 */
	public static function requirePermissions($permissions) {
		self::$instance->requireSession();

		if(is_string($permissions)) {
			$permissions = array($permissions);
		}

		$permission_granted = true;

		// Check each permission group
		foreach($permissions as $permission) {
			if(self::$instance->getPermission($permission) === false) {
				$permission_granted = false;
				break;
			}
		}

		// Check to see if the permission check was allowed.  If so, then exit out of this function.
		if($permission_granted === true) {
			return;
		}


		// Flush any excess information that we have in the buffer.
		ob_flush();

		// Clean the OB so that nothing that was not meant to be released, is.
		ob_clean();

		View::output("header", array("title" => "Permission Error"));
		View::info("You do not have sufficient permissions to access this page.");
		View::output("footer");
		// Abandon ship.
		die();
	}

	/**
	 * Calling this method ensures that the user is not logged in before any more code
	 * is executed.  If the user is logged in, the script will halt and output an error.
	 * 
	 * @returns void
	 */
	public static function requireNoSession() {
		if(self::$instance->isLoggedIn()) {

			Log::info("Killing script.");

			// Info: Ending and cleaning the OB uses less memory than flushing and cleaning.
			// Clean out any buffer information that we have left.
			//ob_end_clean();
			// Start a new OB.
			//ob_start("ob_gzhandler");


			View::output("header", array("title" => "Error: User Logged In."));
			View::info("You must not be logged in to use this feature.");
			View::output("footer");
			// Abandon ship.
			die();
		}
	}

}