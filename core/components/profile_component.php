<?php

namespace Core;

/**
 * Manages retrieving and setting system wide options. 
 */
class ProfileComponent extends Component {

	private $time;
	private $stats = array();

	/**
	 * Constructor called on class initialization.
	 * @param \Core\Main $main Main class.
	 */
	public function __construct() {
		parent::__construct($this);
	}

	public function onRun() {
		$this->time = microtime(true);

		/**
		 * Number designates the number of commands to run the profiler between.
		 * @link http://php.net/manual/en/control-structures.declare.php Read for more information.
		 */
		declare(ticks = 10);
		register_tick_function(array($this, "tick"));
	}

	public function onExit() {
		foreach($this->stats as $stat) {
			Log::line(sprintf("@{%+15.15s:%3s} (%.2fms) (%+5sKB) %+15.15s() Called By {%+15.15s:%3s} %+15.15s()", $stat[0], $stat[1], $stat[6], ceil($stat[7] / 1000), $stat[2], $stat[3], $stat[4], $stat[5]));
		}
	}

	/**
	 * Called every specified control structure tick.
	 */
	public function tick() {
		$trace = debug_backtrace();
		$this->stats[] = array(
			(isset($trace[1]["file"])) ? pathinfo($trace[1]["file"], PATHINFO_BASENAME) : "",	// 0 - Function file
			(isset($trace[1]["line"])) ? $trace[1]["line"] : "",								// 1 - Function line #
			(isset($trace[1]["function"])) ? $trace[1]["function"] : "",						// 2 - Function name
			(isset($trace[2]["file"])) ? pathinfo($trace[2]["file"], PATHINFO_BASENAME) : "",	// 3 - Called from file
			(isset($trace[2]["line"])) ? $trace[2]["line"] : "",								// 4 - Called from line #
			(isset($trace[2]["function"])) ? $trace[2]["function"] : "",						// 5 - Called from function
			(microtime(true) - $this->time) * 1000,												// 6 - Execution time since last call
			memory_get_usage(false)																// 7 - Memory usage
		);
		$this->time = microtime(true);
	}

}