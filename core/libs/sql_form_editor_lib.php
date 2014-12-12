<?php

namespace Core;

class SqlFormEditorLib {

	private $columns;
	private $foreign_key_values;
	private $submit_url;
	private $data;

	public function __construct($data, $submit_url) {
		$this->data = $data;
		$this->submit_url = $submit_url;
	}

	public function addText($column, $formal_name = null) {
		$this->columns[] = array(
			"type" => "text",
			"name" => $column,
			"formal_name" => $formal_name,
		);
	}

	public function addDate($column, $formal_name = null) {
		$this->columns[] = array(
			"type" => "date",
			"name" => $column,
			"formal_name" => $formal_name,
		);
	}

	public function addForeignKey($column, $formal_name = null) {
		$this->columns[] = array(
			"type" => "foreign_key",
			"name" => $column,
			"formal_name" => $formal_name,
		);
	}

	public function addForeignValues($foreign_key_column, $key_values) {
		$this->foreign_key_values[$foreign_key_column] = $key_values;
	}

	public function output($validation = false) {
		View::output("!libs/views/sql_form_editor_lib/table", array(
			"submit_url" => $this->submit_url,
			"columns" => &$this->columns,
			"rows" => &$this->data,
			"foreign_key_values" => &$this->foreign_key_values,
			"validation" => $validation
		));
	}

}

?>
