<?php

namespace Core;

class Component {
	
	/**
	 * Instance variable for the child class.
	 * @var static
	 */
	protected static $instance;
	
	/**
	 * Constructor which must be called for all components.
	 * 
	 * @param \Core\Component $child Child reference.
	 */
	public function __construct(&$child) {
		static::$instance = &$child;
	}
	
	/**
	 * Event method to be overriden in the derived class.
	 * Called on Main->Run execution.
	 */
	public function onRun() {
		
	}
	
	/**
	 * Event method to be overriden in the derived class.
	 * Called on Main->__destruct execution.
	 */
	public function onExit(){
		
	}
	
	/**
	 * Returns the current instance of this class.
	 * 
	 * @return static
	 */
	public static function &i(){
		return static::$instance;
	}

}

?>
