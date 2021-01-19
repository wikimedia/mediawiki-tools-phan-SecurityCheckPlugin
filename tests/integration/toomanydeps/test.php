<?php

function outputStuff($x){
	echo $x;
}

class DifferenceEngine {
	public static function showDiffPage() {
		echo self::addHeader( '' ); // This shouldn't backpropagate HTML_EXEC to $otitle
		outputStuff( self::addHeader( '' ) ); // Ditto
	}

	public static function addHeader( $otitle ) {
		return $otitle;
	}

	public static function formatDiffRow() {
		self::addHeader( $_GET['a'] ); // This is not an XSS
	}
}
