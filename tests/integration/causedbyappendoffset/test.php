<?php

class TestCausedByAppendOffset {
	/**
	 * @param string $a
	 * @param-taint $a tainted
	 * @param string $b
	 * @param-taint $b tainted
	 * @return string
	 */
	private static function appendTwo( $a, $b ) {
	}

	private static function getString( array $data ) {
		$ret = [ 'string' => '' ];

		$x = htmlspecialchars( 'foo' );
		$ret['string'] .= self::appendTwo( $data['safe'], $x );
		$ret['string'] .= self::appendTwo( 'x', 'y' );
		$ret['string'] .= $data['unsafe'];

		return $ret;
	}

	public function execute() {
		echo self::getString( [ 'unsafe' => $_GET['a'] ] )['string'];
	}
}
