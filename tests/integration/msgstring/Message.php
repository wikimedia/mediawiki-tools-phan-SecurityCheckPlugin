<?php

namespace MediaWiki\Message;

class Message {
	/** @return-taint escaped */
	public function __toString() {
		return 'd';
	}
}
