<?php

class Message {
	public function __toString() {
		return 'foo';
	}
}

$msg = new Message;
echo htmlspecialchars( $msg );

echo htmlspecialchars( "Hi $msg" );

echo htmlspecialchars( "Hi " . ( new Message ) );
