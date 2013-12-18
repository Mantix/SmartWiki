<?php
class SmartWikiString {

	private $value;

	public function __construct($value) {
		$this->value = SmartWikiString::parseString($value);
	}

	public function __toString() {
		return $this->value;
	}

	public function setValue($value) {
		$this->value = SmartWikiString::parseString($value);
	}

	public function getValue() {
		return $this->value;
	}

	public function getWikiValue() {
		return $this->value;
	}

	public function __destruct() {
		unset($this->value);
	}

	public static function parseString($value) {
		return strval($value);
	}

	public function equals(SmartWikiString $other) {
		return ($this->getValue() == $other->getValue());
	}

	public function isEmpty() {
		return ($this->value == NULL || $this->value == '');
	}

}