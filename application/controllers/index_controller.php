<?php

use Core\AuthComponent,
	Core\View;

class IndexController extends \Core\Controller {

	/**
	 * Class initializer; 
	 */
	public function __construct() {
		
	}

	/**
	 * Outputs HTML for viewing a user's profile. 
	 * @param $uid User ID to lookup.
	 */
	public function default_index() {
		echo "Hello World"
	}
}