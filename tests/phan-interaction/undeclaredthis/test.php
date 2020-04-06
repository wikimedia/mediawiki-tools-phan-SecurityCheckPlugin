<?php
/*
 * For T249491
 */

class MessageValue {
	private $key;
	public function getKey() {
		return $this->key; // This would give "Variable $this is undeclared"
	}
}

class DataMessageValue extends MessageValue {
	public function dump() {
		return htmlspecialchars( $this->getKey() );
	}
}
