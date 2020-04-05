<?php

class Html {
	public static function rawElement( $a, $b, $c ) : string {
		return $a . $b . $c;
	}
}

class A {
	public function main() {
		$this->output(
			Html::rawElement( 'div', [], Html::rawElement( 'ul', [], Html::rawElement( 'li', [], $_GET['baz'] ) ) )
		);
	}

	private function output( $x ) {
		echo $x;
	}
}
