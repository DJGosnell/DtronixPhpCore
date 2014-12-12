<?php

namespace Core {

	/**
	 * Handles all errors.
	 */
	function handleError($errno, $errstr, $errfile, $errline) {
		$error_builder = array();

		$error_builder[] = "" . $errno;
		$error_builder[] = "PHP Error String: " . $errstr;
		$error_builder[] = "PHP Error File:   " . str_replace(BASEPATH, "", $errfile);
		$error_builder[] = "PHP Error Line:   " . $errline;
		// Get backtrace.
		ob_start();
		debug_print_backtrace();
		$error_builder[] = "PHP Backtrace:";
		$error_builder[] = ob_get_clean();

		$error_builder[] = Log::error(implode(PHP_EOL, $error_builder));
	}

}

namespace {

	function asBytes($ini_v) {
		$ini_v = trim($ini_v);
		$s = array('g' => 1 << 30, 'm' => 1 << 20, 'k' => 1 << 10);
		return intval($ini_v) * ($s[strtolower(substr($ini_v, -1))] ? : 1);
	}

	/**
	 * Converts naming conventions between each other.
	 * 
	 * @param string $from Type of string naming convention (camel, underscore, hyphen) to convert from.
	 * @param string $to Type of string naming convention (camel, underscore, hyphen) to convert to.
	 * @param string $value Value to convert to a new naming convention.
	 * @return string Converted string.
	 */
	function namingConversion($from, $to, $value) {
		$search_regex = null;

		if(empty($value)) {
			return $value;
		}

		switch($from) {
			case "camel":
				$value[0] = strtolower($value[0]);
				$search_regex = '/([A-Z])/';
				break;

			case "underscore":
				$value[0] = strtoupper($value[0]);
				$search_regex = '/_([a-zA-Z])/';
				break;

			case "hyphen":
				$search_regex = '/-([a-zA-Z])/';
				break;

			default:
				die("Unknown 'from' naming convention: $from");
				break;
		}

		switch($to) {
			case "underscore":
				$value[0] = strtolower($value[0]);
				return preg_replace_callback($search_regex, function($match) {
					return "_" . strtolower($match[1]);
				}, $value);

			case "camel":
				$value[0] = strtoupper($value[0]);
				return preg_replace_callback($search_regex, function($match) {
					return strtoupper($match[1]);
				}, $value);

			case "hyphen":
				$value[0] = strtolower($value[0]);
				return preg_replace_callback($search_regex, function($match) {
					return "-" . strtolower($match[1]);
				}, $value);

			default:
				die("Unknown 'to' naming convention: $to");
				break;
		}
	}

	/**
	 * Returns the first $variable if it isset; Otherwise returns the specified
	 * default variable.
	 * 
	 * @param type $variable Variable to check if it is set.
	 * @param type $default The default value for the return value if the variable is not set.
	 * @return mixed The first non-null variable.
	 */
	function ifsetor(&$variable, $default = null) {
		if(isset($variable)) {
			return $variable;
		} else {
			return $default;
		}
	}

}