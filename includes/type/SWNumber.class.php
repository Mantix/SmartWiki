<?php
class SWNumber {

	private $value;

	public function __construct($value) {
		$this->value = SWNumber::parseInt($value);
	}

	public function __toString() {
		return '' . $this->value;
	}

	public function setValue($value) {
		$this->value = SWNumber::parseInt($value);
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

	public static function parseInt($value) {
		return intval($value);
	}

	public function equals(SWNumber $other) {
		return ($this->getValue() == $other->getValue());
	}

	public function isEmpty() {
		return ($this->value == NULL || $this->value == '');
	}

}