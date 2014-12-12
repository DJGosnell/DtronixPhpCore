<?php

namespace Core;

class StringHelper {

	private static $GENERATE_HASH_STRING = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

	/**
	 * Generates as random string composed of upper and lower case letters and numbers.
	 * 
	 * @param int $length Length that the hash should be.
	 * @return string Generated hash.
	 */
	public static function random($length = 12) {
		$built_hash = "";
		for ($i = 0; $i < $length; $i++) {
			$built_hash .= self::$GENERATE_HASH_STRING[rand(0, 61)];
		}

		return $built_hash;
	}

	/**
	 * Takes a number of bytes and outputs a formatted string with them rouned
	 * up to the nearest unit (KB, MB, GB, etc...).
	 * 
	 * @author xelozz <xelozz@gmail.com> asfas fafs
	 * @link http://us2.php.net/manual/en/function.memory-get-usage.php#96280 Original code.
	 * 
	 * @param number $bytes Number of bytes to convert to a formatted string.
	 * @param int $round Number of deciman places to round the number off to.
	 * @return string Formatted string
	 */
	public static function convertBytes($bytes, $round = 0) {
		$unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		return round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $round) . ' ' . $unit[$i];
	}

	private static $time_elapsed_array = null;

	/**
	 * Represents a time passed as a natural string. eg. (3 Weeks).
	 * 
	 * @param int $time Time to represent.
	 * 
	 * @return string Time elapsed
	 * @author Zach http://www.zachstronaut.com/posts/2009/01/20/php-relative-date-time-string.html
	 */
	public static function timeElapsedString($time) {
		$etime = time() - $time;

		if(self::$time_elapsed_array === null) {
			self::$time_elapsed_array = array(
				12 * 30 * 24 * 60 * 60 => 'year',
				30 * 24 * 60 * 60 => 'month',
				24 * 60 * 60 => 'day',
				60 * 60 => 'hour',
				60 => 'minute',
				1 => 'second'
			);
		}

		if($etime < 1) {
			return '0 seconds';
		}

		foreach (self::$time_elapsed_array as $secs => $str) {
			$d = $etime / $secs;
			if($d >= 1) {
				$r = round($d);
				return $r . ' ' . $str . ($r > 1 ? 's' : '');
			}
		}
	}
}