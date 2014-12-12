<?php

namespace Core;

/**
 * Core class for all caching functions.
 */
class CacheComponent {

	private $key_base;

	/**
	 * Constructor called on class initialization.
	 * @param \Core\Main $main Main class.
	 */
	public function __construct() {
		parent::__construct($this);
		
		if(\Config::CACHE_OUTPUT === false) {
			return;
		}
		$session_id = $_COOKIE[\Config::COOKIE_PREFIX . "session"];
		$this->key_base = "sc_" . $session_id;

		Log::debug("Trying to output cached page.");
		$this->tryOutputCachedPage();
	}

	private function tryOutputCachedPage() {
		// If this is a post, then we do not want to return anything cached.
		if($_SERVER["REQUEST_METHOD"] == "POST") {
			return;
		}

		$success = false;
		$cached_page = $this->fetch("_page_" . $_SERVER["REQUEST_URI"], $success);

		if($success) {

			Log::debug("Fetching page successful.");

			print $cached_page;

			Log::debug("(Max Memory Usage) System: " . memory_get_peak_usage(true) . "; PHP: " . memory_get_peak_usage(false) . ";");

			// Output the log if so desired.
			Log::outputLog();

			die();
		}
	}

	public function cacheOutputPage() {
		$this->add("_page_" . $_SERVER["REQUEST_URI"], ob_get_contents(), 10);
	}

	/**
	 * Fetch a stored variable from the cache
	 * @link http://php.net/manual/en/function.apc-fetch.php
	 * @param string $key The <i>key</i> used to store the value
	 * @param bool $success [optional] <p>
	 * Set to <b>TRUE</b> in success and <b>FALSE</b> in failure.
	 * </p>
	 * @return mixed The stored variable or array of variables on success; <b>FALSE</b> on failure
	 */
	public function fetch($key, &$success = null) {
		Log::debug("Cache fetched: " . $key);
		return apc_fetch($this->key_base . $key, $success);
	}

	/**
	 * Cache a new variable in the data store
	 * @link http://php.net/manual/en/function.apc-add.php
	 * @param string $key <p>
	 * Store the variable using this name. <i>key</i>s are
	 * cache-unique, so attempting to use <b>apc_add</b> to
	 * store data with a key that already exists will not overwrite the
	 * existing data, and will instead return <b>FALSE</b>.
	 * </p>
	 * @param mixed $var [optional] <p>
	 * The variable to store
	 * </p>
	 * @param int $ttl [optional] <p>
	 * Time To Live; store <i>var</i> in the cache for
	 * <i>ttl</i> seconds. After the
	 * <i>ttl</i> has passed, the stored variable will be
	 * expunged from the cache (on the next request). If no <i>ttl</i>
	 * is supplied (or if the <i>ttl</i> is
	 * 0), the value will persist until it is removed from
	 * the cache manually, or otherwise fails to exist in the cache (clear,
	 * restart, etc.).
	 * </p>
	 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 * Second syntax returns array with error keys.
	 */
	public function add($key, $var = null, $ttl = 0) {
		Log::debug("Cache Added: " . $key);
		return apc_add($this->key_base . $key, $var, $ttl);
	}

}

