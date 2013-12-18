<?php
class SmartWikiBoolean {

	private $value;

	public function __construct($value) {
		$this->value = SmartWikiBoolean::parseBool($value);
	}

	public function __toString() {
		return '' . $this->value;
	}

	public function setValue($value) {
		$this->value = SmartWikiBoolean::parseBool($value);
	}

	public function getValue() {
		return $this->value;
	}

	public function getWikiValue() {
		return ($this->value == TRUE ? 'Yes' : 'No');
	}

	public function __destruct() {
		unset($this->value);
	}

	public static function parseBool($value) {
		if (($value == 'TRUE') || ($value == 'true') || ($value == 'ON') || ($value == 'on') || ($value == 'YES') || ($value == 'Yes') || ($value == 'yes') || ($value == 1) || ($value == '1')) {
			return TRUE;
		}
		return FALSE;
	}

	public function equals(SmartWikiBoolean $other) {
		return ($this->getValue() == $other->getValue());
	}

	public function isEmpty() {
		return ($this->value == NULL || $this->value == '');
	}

}