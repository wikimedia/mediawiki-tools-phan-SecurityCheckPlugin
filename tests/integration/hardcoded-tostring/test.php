<?php

class HardcodedEscapedToString {
	public function __toString() {
		return 'foo';
	}
}

$escapedToString = new HardcodedEscapedToString;
echo htmlspecialchars( $escapedToString ); // DoubleEscaped

echo htmlspecialchars( "Hi $escapedToString" ); // DoubleEscaped

echo htmlspecialchars( "Hi " . ( new HardcodedEscapedToString ) ); // DoubleEscaped
