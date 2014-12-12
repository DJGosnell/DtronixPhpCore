<?php

namespace Core;

/**
 * Aids in validation of form data. 
 */
class InputValidationLib {

	/**
	 * Contains all the invalid data entries that have been parsed.
	 * @var array
	 */
	private $validation_results;

	/**
	 * Field name to check validations against.
	 * @var string
	 */
	private $field;

	/**
	 * True if the specified field is an array.
	 * @var bool
	 */
	private $is_field_array;

	/**
	 * Contains the  error text string for the current validation.
	 * @var string
	 */
	private $error_text;

	/**
	 * Values to validate. 
	 * @var array
	 */
	private $input;

	/**
	 * Creates a new FormValidationLib instance.
	 * @param array $input Data to validate.  Null to use $_POST.
	 */
	public function __construct(&$input = null) {
		if($input == null) {
			$this->input = &$_POST;
		} else {
			$this->input = &$this->input;
		}
	}

	/**
	 * Call to specify a field to validate before any specific validation calls.
	 * 
	 * @param string $field Field input to validate.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function field($field) {
		// If this is an array, break off the name from the array brackets.
		$field_name = explode("[", $field);

		// Set the array status.
		if(count($field_name) > 1) {
			$this->is_field_array = true;
		} else {
			$this->is_field_array = false;
		}

		$this->field = $field_name[0];
		return $this;
	}

	/**
	 * Validation method to verify an input was submitted and has a value.
	 * 
	 * @param string $error_text Text to display in the validation results if the input did not validate.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function required($error_text = false) {
		$this->error_text = $error_text ? : "Input is required.";
		$this->validateInput("require", null);
		return $this;
	}

	/**
	 * 
	 * @param array $values Array of strings to verify the input is amung.
	 * @param int $defailt_index Index from the $values to use if the input does 
	 * 		not match one of the values or is not set.  Set to -1 to throw 
	 * @param type $error_text Text to display in the validation results if the input did not validate.
	 * @return \Core\InputValidationLib
	 */
	public function allowedValues($values, $defailt_index = -1, $error_text = false) {
		$this->error_text = $error_text ? : "Input is required.";
		$this->validateInput("allowed-values", array($values, $defailt_index));
		return $this;
	}

	/**
	 * Validation method to verify an input matches a regular expression pattern.
	 * 
	 * @param string $regex Regular expression to match against. Regex is called using preg_match.
	 * @param string $error_text Text to display in the validation results if the input did not validate.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function matches($regex, $error_text = false) {
		$this->error_text = $error_text ? : "Input does not match required form.";
		$this->validateInput("matches", array($regex));
		return $this;
	}

	/**
	 * Validation method to verify an input has the same value as another input.
	 * 
	 * @param string $second_input Other input to compaire the first input to.
	 * @param string $error_text Text to display in the validation results if the input did not validate.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function equals($second_input, $error_text = false) {
		$this->error_text = $error_text ? : "Input does not equal '$second_input'.";
		$this->validateInput("equals", array($second_input));
		return $this;
	}

	/**
	 * Validation method to verify an input is a number.
	 * 
	 * @param string $error_text Text to display in the validation results if the input did not validate.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function number($error_text = false) {
		$this->error_text = $error_text ? : "Input is required to be a number.";
		$this->validateInput("number", null);
		return $this;
	}

	/**
	 * Validation method to verify an input number is a whole number.
	 * 
	 * @param string $error_text Text to display in the validation results if the input did not validate.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function wholeNumber($error_text = false) {
		$this->error_text = $error_text ? : "Input is required to be a whole number.";
		$this->validateInput("whole-number", null);
		return $this;
	}

	/**
	 * Validation method to verify an input number is between.
	 * 
	 * @param int $greater_than Smallest (inclusive) number the input can be.
	 * @param int $less_than Latgest (inclusive) number the input can be. 
	 * @param string $error_text Text to display in the validation results if the input did not validate.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function numberRange($greater_than, $less_than, $error_text = false) {
		$this->error_text = $error_text ? : "Input number is required to be between $greater_than and $less_than.";
		$this->validateInput("number-range", array($greater_than, $less_than));
		return $this;
	}

	/**
	 * Validation method to verify an input length is between two numbers.
	 * 
	 * @param type $greater_than shortest (inclusive) number the  input length can be.
	 * @param type $less_than Longest (inclusive) number the input length can be. 
	 * @param type $error_text Text to display in the validation results if the input did not validate.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function lengthBetween($greater_than, $less_than, $error_text = false) {
		$this->error_text = $error_text ? : "Input is required to have a length between $greater_than and $less_than.";
		$this->validateInput("length-between", array($greater_than, $less_than));
		return $this;
	}

	/**
	 * Validation method to verify the field specified contains a file.
	 * 
	 * @param string $error_text Text to display in the validation results if the input did not validate.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function fileRequired($error_text = false) {
		$this->error_text = $error_text ? : "File is required.";
		$this->validateInput("file-require", null);
		return $this;
	}

	/**
	 * Validation method to verify an uploaded file's size is less than the specified ammount.
	 * 
	 * @param int $max_size Maximum size (in bytes) for an uploaded file to be.
	 * @param string $error_text Text to display in the validation results if the input did not validate.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function fileMaxSize($max_size, $error_text = false) {
		$this->error_text = $error_text ? : "File can not be more than " . StringHelper::convertBytes($max_size) . ".";
		$this->validateInput("file-max-size", array($max_size));
		return $this;
	}

	/**
	 * Validation method to verify an input has the same value as another input.
	 * 
	 * @param array $allowed_types Array of file extention strings.
	 * @param string $error_text Text to display in the validation results if the input did not validate.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function fileTypesAllowed($allowed_types, $error_text = false) {
		$this->error_text = $error_text ? : "File type is not allowed.";
		$this->validateInput("file-type-allowed", array($allowed_types));
		return $this;
	}

	/**
	 * Custom validation to be called on the specified input. <br />
	 * <br />
	 * The value for the input will be passed as the first parameter to the passed callback.
	 * 
	 * @param callable $callback Method to be called for validation.
	 * @param bool $validation_passed If set to true, the callback will be only called if there are not any validation errors up to the point of being called.
	 * @return \Core\InputValidationLib Current instance to allow method chaining.
	 */
	public function callback($callback, $validation_passed = false) {
		if($this->validation_results !== null && isset($this->validation_results[$this->field]) || isset($this->input[$this->field]) === false) {
			return $this;
		}

		if($validation_passed && $this->validation_results !== null) {
			return $this;
		}

		$result = $callback($this->input[$this->field]);

		if($result === true) {
			return $this;
		}

		if(is_array($result)) {
			$this->validation_results[$result["input"]] = $result["description"];
		}
		return $this;
	}

	/**
	 * Returns the validation result. <br />
	 * Returns array with validation errors on validation failure; Otherwise, returns null on on successful validation.<br />
	 * Will always return null if no validation has been performed.
	 * 
	 * @return array
	 */
	public function result() {
		return $this->validation_results;
	}

	/**
	 * Method to handle all validation and common validation functions.
	 * 
	 * @param string $validation_type Type of validation to perform on the specified input.
	 * @param array $arguments Values to pass to the specific validation type.  Always pass an array or null.
	 * @return boolean True on successful validation; Otherwise returns false.
	 */
	private function validateInput($validation_type, $arguments) {
		// Check to see if this name has been checked already and contains an error.
		if($this->validation_results !== null && isset($this->validation_results[$this->field])) {
			return false;
		}

		// If the input is not set, then we can not do validation on it.
		if(isset($this->input[$this->field]) === false) {
			$validation_type = false;
		}


		if($validation_type === "allowed-values") {
			// Does the value exist in the array?
			if($validation_type === false && $arguments[1] != -1) {
				// If this input is not set, then set the value.
				$this->input[$this->field] = $arguments[0][$arguments[1]];
				return true;
			} else {
				// Ensure that the value is in the allowed value array.
				if(($index = array_search($this->input[$this->field], $arguments[0])) !== false) {
					$this->input[$this->field] = $arguments[0][$index];
				}
			}
		}

		// List of validation methods to select from.
		switch($validation_type) {
			case "require":
				if(empty($this->input[$this->field]) === false) {
					return true;
				}
				break;

			case "equals":
				if(isset($this->input[$arguments[0]]) && $this->input[$this->field] == $this->input[$arguments[0]]) {
					return true;
				}
				break;

			case "match":
				if(preg_match($arguments[0], $this->input[$this->field]) === 1) {
					return true;
				}
				break;

			case "number":
				if(is_numeric($this->input[$this->field])) {
					return true;
				}
				break;

			case "whole-number":
				if((int)$this->input[$this->field] == $this->input[$this->field]) {
					return true;
				}
				break;

			case "number-range":
				if(is_numeric($this->input[$this->field]) && $this->input[$this->field] >= $arguments[0] && $this->input[$this->field] <= $arguments[1]) {
					return true;
				}
				break;

			case "length-between":
				$str_len = strlen($this->input[$this->field]);
				if($str_len >= (int)$arguments[0] && $str_len <= (int)$arguments[1]) {
					return true;
				}
				break;

			case "file-require":
				if(isset($_FILES[$this->field]) && $_FILES[$this->field]["error"] == 0) {
					return true;
				}
				break;

			case "file-max-size":
				if(isset($_FILES[$this->field]) && $_FILES[$this->field]["error"] == 0 && $_FILES[$this->field]["size"] <= $arguments[0]) {
					return true;
				}
				break;

			case "file-type-allowed":
				if($this->is_field_array) {
					
				}
				if(isset($_FILES[$this->field]) && $_FILES[$this->field]["error"] == 0) {
					$extention = pathinfo($_FILES[$this->field]["name"], PATHINFO_EXTENSION);
					if(in_array($extention, $arguments[0])) {
						return true;
					}
				}
				break;
		}


		// If we got to this point, then the data is invalid.
		$this->validation_results[$this->field] = $this->error_text;
		return false;
	}

}
