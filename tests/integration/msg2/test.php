<?php

class Message {
	private $foo = 'safe';
	public function text() {
		return $this->foo;
	}
	public function parse() {
		return $this->foo;
	}
	public function escaped() {
		return $this->foo;
	}
	public function rawParams( $params ) {
		$this->foo = $params;
		return $this;
	}
	public function params( $params ) {
		return $this;
	}
}

$msg = new Message;

// These are safe because nothing is output here
// Note these are not inlined because the plugin is not that smart yet.
$safe1 = $msg->rawParams( $_GET['baz'] );
$safe1 = $safe1->params( 'foo' );
$safe1 = $safe1->parse();
$safe2 = $msg->rawParams( $_GET['baz'] );
$safe2 = $safe2->parse();
$safe3 = $msg->rawParams( $_GET['baz'] );
$safe3 = $safe3->escaped();
$safe4 = $msg->rawParams( $_GET['baz'] );
$safe4 = $safe4->text();
// And these are unsafe because the raw param is output as-is
echo $safe1;
echo $safe2;
echo $safe3;
echo $safe4;
