<?php

class Message {
	public function text() {
		return 'placeholder';
	}
	public function parse() {
		return 'placeholder';
	}
	public function escaped() {
		return 'placeholder';
	}
	public function rawParams( $params ) {
		return $this;
	}
	public function params( $params ) {
		return $this;
	}
}

$msg = new Message;

// We emit an issue here due to the use of rawparams
$unsafe1 = $msg->rawParams( $_GET['baz'] )->params( 'foo' )->parse();
$unsafe2 = $msg->rawParams( $_GET['baz'] )->parse();
$unsafe3 = $msg->rawParams( $_GET['baz'] )->escaped();
$unsafe4 = $msg->rawParams( $_GET['baz'] )->text();
// These are still unsafe but we don't emit any issue (yet). Should we?
echo $unsafe1;
echo $unsafe2;
echo $unsafe3;
echo $unsafe4; // This one is super-unsafe, and we also emit a separate XSS notice for text()
