<?php
class Message {
	/** @return-taint tainted */
	public function text() {
	}
	/** @return-taint escaped */
	public function parse() {
	}
	/** @return-taint escaped */
	public function __toString() {
		return 'd';
	}
}
