<?php

namespace Core;

/**
 * Base class to all views. 
 */
class View {
	
	/**
	 * True if the autoHeaderFooter method was previously called.
	 * @var bool
	 */
	public static $auto_header_footer = false;
	
	/**
	 * Outputs a view to the output buffer.
	 * 
	 * @param string $name Name of the view to output. 
	 *	<br>Filename and directory inside the view directory.
	 *	<br>Prepend with an exclamation point to specify a view inside the "core" directory.
	 * @param array $arguments Associative array to be extracted into the scope of the view.
	 * @param bool $return Set to true to return the view as a string.
	 * @return mixed String if $return was set to true. Void otherwise.
	 */
	public static function output($name, array $arguments = null, $return = false) {
		if($return) {
			ob_start();
		}

		// Ensure that there is no directory transversal going on here.
		if(strpos($name, ".") !== false) {
			Log::error("Invalid character '.' found in view name '$name'");
		}

		$view_path = BASEPATH;

		// If we have an exclamation mark, we then have a path that relates to the core directory.
		if($name[0] == "!") {
			$view_path .= "core/" . substr($name, 1) . ".php";
		} else {
			$view_path .= APP_DIR . "/views/" . $name . ".php";
		}
		if(file_exists($view_path) === false) {
			Log::error("The specified view path '$view_path' does not exist.");
		}
		
		// If we have any arguments passed to the view, we extract them into this scope.
		if($arguments !== null) {
			extract($arguments);
		}

		require $view_path;
		
		// Return the output as a string if requested to do so.
		if($return) {
			return ob_get_clean();
		}
	}
	
	/**
	 * Helper method to output the "info" view to the user quickly.
	 * 
	 * @param string $text What to output to the user.
	 */
	public static function info($text) {
		View::output("info", array(
			"text" => $text
		));
	}
	
	/**
	 * Helper method to redirect the user to a specified URL after the designated
	 * duration.
	 * 
	 * @param string $text Text to display to the client before redirecting.
	 * @param string $location Full URL to redirect the client to.
	 * @param int $seconds Total seconds to delay the redirect.
	 */
	public static function infoRedirect($text, $location, $seconds) {
		View::output("info_redirect", array(
			"text" => $text,
			"location" => $location,
			"seconds" => $seconds
		));
	}
	
	/**
	 * Helper method to automatically output the default header at the time 
	 * of this method being called and the default footer after the controller
	 * has exited.
	 * 
	 * @param string $title Text to display on the title of the page.
	 */
	public static function autoHeaderFooter($title = null) {
		View::output("header", array("title" => $title));
		self::$auto_header_footer = true;
	}

}
