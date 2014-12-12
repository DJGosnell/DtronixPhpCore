<?php

namespace Core;

class UploadHandlerLib {

	private static $ERROR_STATUS_INFO = array(
		1 => "The uploaded file exceeds the maximum size "
	);

	public function __construct() {
		
	}

	public function uploadInfo() {
		$table = new HtmlTableLib(array("Property", "Value", "Info"));
		$table->addStyle("width: 500px; padding: 5px");
		
		
		$table->addRow(array(
			"php.ini post_max_size",
			ini_get("post_max_size"),
			"OK"
		));
		
		// php.ini max_execution_time Checks
		$upload_max_filesize = ini_get("upload_max_filesize");
		if($upload_max_filesize < 60) {
			$upload_max_filesize_info = "Execution time is under one minute.  This may be too short for file uploads";
		} else {
			$upload_max_filesize_info = "OK";
		}

		$table->addRow(array(
		"php.ini upload_max_filesize",
		$upload_max_filesize,
		$upload_max_filesize_info
		));
		
		$table->addRow(array(
			"php.ini ",
			ini_get("upload_max_filesize"),
			"OK"
		));

		// php.ini max_execution_time Checks
		$max_execution_time = ini_get("max_execution_time");
		if($max_execution_time < 60) {
			$max_execution_time_info = "Execution time is under one minute.  This may be too short for file uploads.";
		} else {
			$max_execution_time_info = "OK";
		}

		$table->addRow(array(
		"php.ini max_execution_time",
		$max_execution_time,
		$max_execution_time_info
		));

		$table->addRow(array(
			"php.ini memory_limit",
			ini_get("memory_limit"),
			"OK"
		));

		$table->addRow(array(
			"php.ini max_input_time",
			ini_get("memory_limit"),
			"OK"
		));

		$table->output();
	}

	public function handleUpload() {
		if(count($_FILES) == 0) {
			return false;
		}
	}

}

?>
