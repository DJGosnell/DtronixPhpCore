<?php

namespace Core;

use \Exception;

/**
 * Static class to aid in debugging and error logging. 
 */
class Log {

	/**
	 * @var array Contains associative array with "type" of log value and the actual output as the "value".
	 */
	private static $log = array();

	/**
	 * 0: The log goes into an empty void, never to be seen again.
	 * 1: Saves the log to "application.log".
	 * 2: Outputs the log to a javascript console.log command.
	 * 3: Saves the log to the log SQL database.
	 * 
	 * @var int Defines the location that the log will be written to.
	 */
	private static $log_location = 2;

	/**
	 * @var float Time that the execution started.
	 */
	private static $start_time = 0;

	/**
	 * Internal variable that keeps the last memory usage since the last log call.
	 * @var number
	 */
	private static $last_memory_usage = 0;

	/**
	 * Sets the current log level.
	 * 0: No information was logged.
	 * 1: Debug
	 * 2: Information
	 * 3: Error
	 * @var int 
	 */
	private static $log_current_level = 0;

	/**
	 * Contains benchmark points to measure.
	 * @var array
	 */
	private static $bench_points = array();

	/**
	 * Method to initialize starup variables required for time logging. 

	 * @param number Time that the script started.
	 */
	public static function initialize() {
		self::$log_location = \Config::LOG_LOCATION;
		self::$start_time = microtime(true);
		self::debug("Loaded Core classes.  Initialized Log class.");
	}

	/**
	 * Sets the logging location for this request. <br />
	 * 0: The log goes into an empty void, never to be seen again. <br />
	 * 1: Saves the log to the configured error log. <br />
	 * 2: Outputs the log to a javascript console.log command. <br />
	 * 3: Saves the log to the log SQL database.
	 * 
	 * @param int $location Integer defining the location to write the log.
	 */
	public static function setLogLocation($location) {
		self::$log_location = $location;
	}

	/**
	 * <p>Method to be called twice around an operation to get debugging information<br />
	 * and execution times.</p>
	 * <p>Usage: Call at the start of the code you want to benchmark with just an ID string.<br />
	 * Call the second time to log the results.  String passed to the $text paramter will <br />
	 * be passed to this benchmark log.</p>
	 * 
	 * @param string $id Reference to this benchmark.
	 * @param string $text Text to send to the debug log.
	 */
	public static function benchmark($id, $text = "") {
		if(isset(self::$bench_points[$id]) === false) {
			// Set the beginning bench mark point.
			self::$bench_points[$id] = array(memory_get_usage(false), microtime(true));
		} else {
			$mem_delta = memory_get_usage(false) - self::$bench_points[$id][0];

			$bench_line = "[Benchmark] (";

			if($mem_delta < 0) {
				$bench_line .= '-';
			} else {
				$bench_line .= '+';
			}

			$bench_line .= self::convertBytes(abs($mem_delta), 1);
			$bench_line .= ") (";
			$bench_line .= number_format((microtime(true) - self::$bench_points[$id][1]) * 1000, 2, '.', ',');
			$bench_line .= " ms) ";
			$bench_line .= $text;

			self::$log[] = self::buildLogLine("info", debug_backtrace(), $bench_line);
			unset(self::$bench_points[$id]);
		}
	}

	/**
	 * Logs a debugging item.
	 * 
	 * @param string $log_text Text to send to the debug log.
	 */
	public static function debug($log_text) {
		if(\Config::LOG_DEBUG) {
			if(self::$log_current_level < 1) {
				self::$log_current_level = 1;
			}

			self::$log[] = self::buildLogLine("log", debug_backtrace(), $log_text);
		}
	}

	/**
	 * Logs a debugging item.
	 * 
	 * @param string $log_text Text to send to the info log.
	 */
	public static function info($log_text) {
		if(\Config::LOG_DEBUG) {
			if(self::$log_current_level < 1) {
				self::$log_current_level = 1;
			}

			self::$log[] = self::buildLogLine("info", debug_backtrace(), $log_text);
		}
	}

	/**
	 * Adds a string of text to the log line without any additional preformatted text.
	 * 
	 * @param string $log_text Raw text to output to the log.
	 */
	public static function line($log_text) {
		if(\Config::LOG_DEBUG) {
			self::$log[] = array("type" => "log", "value" => $log_text);
		}
	}

	/**
	 * Logs an error with an associated backtrace.
	 * @param string $log_text Text to send to the error log.
	 * @param bool $throw If ture, error call will kill the script execution. If false, error will not throw.
	 */
	public static function error($log_text, $throw = true) {
		if(self::$log_current_level < 3) {
			self::$log_current_level = 3;
		}
		if(DEBUG) {
			echo "<pre style='text-align: left;'>";
			echo $log_text;
			echo "</pre>";
		}
		self::$log[] = self::buildLogLine("error", debug_backtrace(), $log_text);

		// If this is an unrecoverable error, we need to stop execution.
		if($throw) {
			throw new Exception("A fatal error occured.  A report has been filed with the administrator.");
		}
	}

	/**
	 * Saves or outputs a log to the user.  Output type depends on $log_location.
	 */
	public static function outputLog() {
		// If we do not have anything in the log, then there is nothing to output.
		if(count(self::$log) === 0) {
			return;
		}

		switch(self::$log_location) {
			case 0: break;
			// Empty void.
			case 1:
				// Check to see if we need to log anything to the server
				if(self::$log_current_level > 1) {
					$log_file_handle = fopen(BASEPATH . APP_DIR . "/" . \Config::LOG_FILE, 'a');
					// Log header.
					fprintf($log_file_handle, "[IP:%s TIME:%s USER_ID:%s] BEGIN LOG:\n", self::$main->User->Ipv4, time(), self::$main->User->Id);

					// Log body.  Just seperate each part of the log with a sepearate line.
					foreach(self::$log as $log_item) {
						fwrite($log_file_handle, $log_item['value']);
						fwrite($log_file_handle, "\n");
					}

					// Log footer.
					fwrite($log_file_handle, "END LOG;\n");

					fclose($log_file_handle);
				}

				break;

			case 2:
				// Output as a javascript object easily read by FireBug
				echo "<script type=\"text/javascript\">\n";
				echo "if(typeof console == \"object\" && typeof console.group == \"function\"){\n";
				echo "console.";
				echo (\Config::LOG_COLLAPSED_GROUPS) ? "groupCollapsed" : "group";
				echo "(\"PHP Server\");\n";
				foreach(self::$log as $log_item) {
					echo 'console.';
					echo $log_item['type'];
					echo '(';
					echo json_encode($log_item['value']);

					if($log_item['type'] === 'error') {
						echo ', null';
					}
					echo ");\n";
				}
				echo "\nconsole.groupEnd();\n}\n</script>";
				break;
			case 3:
				// TODO: Write logging code to write to database
				/* @var $model Core\LogModel */
				Main::app()->Loader->model('Core\LogModel');
				break;
		}
	}
	
	/**
	 * Builds a log line item with all the memory and time information.
	 * 
	 * @param string $log_type Destribes what type of log item this is.<br />
	 *	Used for the Javascript log outout.
	 * @param array $debug_trace backtrace from where the log was called.
	 * @param string $log_value Text to write about this log line.
	 * 
	 * @return array Associative array with a log type and a value for the log.
	 */
	private static function buildLogLine($log_type, $debug_trace, $log_value) {
		$calling_info = $debug_trace[0];
		// Calling information
		$log_builder = "";
		$calling_file = explode("/", $calling_info["file"]);
		$log_builder .= sprintf("[%+15.15s:%3s] ", $calling_file[count($calling_file) - 1], $calling_info["line"]);

		// Time output.
		$log_builder .= "(";
		$log_builder .= number_format((microtime(true) - self::$start_time) * 1000, 2, '.', ',');
		$log_builder .= " ms) ";

		// Output the memory usage information if requested.
		if(\Config::LOG_MEMORY_USAGE === true) {
			$log_builder .= "(";
			$new_memory = $mem_delta = memory_get_usage(false);

			// Check to see if we want to log the delta or absolute value.
			if(\Config::LOG_MEMORY_USAGE_DELTA) {
				$mem_delta = $new_memory - self::$last_memory_usage;
				// Add the plus or minus depending on whether a memory cleanup event happened at some point.
				if($mem_delta < 0) {
					$log_builder .= '-';
				} else {
					$log_builder .= '+';
				}
				self::$last_memory_usage = $new_memory;
			}

			$log_builder .= self::convertBytes(abs($mem_delta), (\Config::LOG_MEMORY_USAGE_DELTA) ? 0 : 3 );
			$log_builder .= ') ';
		}

		$log_builder .= $log_value;

		if(\Config::LOG_IMMEDIATE_MODE) {
			echo $log_type . " " . $log_builder . "<br>\n";
		}

		return array("type" => $log_type, "value" => $log_builder);
	}

	public static function convertBytes($bytes, $round = 0) {
		$unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $round) . ' ' . $unit[$i];
	}

}
