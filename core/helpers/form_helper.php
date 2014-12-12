<?php

namespace Core;

class FormHelper {

	/**
	 * Outputs the value of the previously posted text input.
	 * 
	 * @param type $input_name Field name for this element.
	 * @param type $default Default value if no value is set.
	 * @param type $escape_char Character that is wrapping this element in the HTML. Either ' or ".
	 * @return string Value that is the default for this input.
	 */
	public static function defaultInputValue($input_name, $default = '', $escape_char = '"') {
		if(isset($_POST[$input_name])) {
			echo str_replace($escape_char, '\\' . $escape_char, $_POST[$input_name]);
		} else {
			return $default;
		}
	}

	public static function defaultCheckboxState($input_name) {
		if(isset($_POST[$input_name])) {
			echo 'checked';
		}
	}

	public static function inputText($name, $validation, $default_value = null) {
		?>
		<input type="text" name="<?= $name ?>" id="<?= $name ?>" value="<?= FormHelper::defaultInputValue($name, $default_value) ?>"/><div class="form_error" id="form_<?= $name ?>_error"><?= ifsetor($validation[$name]) ?></div><?
	}

	public static function inputSelect($name, $options, $validation, $default_value = null) {
		?>
		<select name="<?= $name ?>">
			<? foreach($options as $id => $option): ?>
				<option value="<?= $id ?>"<?= ($default_value == $id) ? " selected" : ""; ?>><?= $option ?></option>
			<? endforeach; ?>
		</select>
		<div class="form_error" id="form_<?= $name ?>_error"><?= ifsetor($validation[$name]) ?></div><?
	}

}