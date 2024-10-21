<?php

class TestForeachValue {
	public static function execElInForeach( $items ) {
		foreach ( $items as $item ) {
			// Here, $item must be linked to the $items param for unknown offsets only (and not as a whole)
			echo $item['k'];
		}
	}
}
'@taint-check-debug-method-first-arg TestForeachValue::execElInForeach';

TestForeachValue::execElInForeach( [ $_GET['a'] ] ); // Unsafe

class TestForeachKey {
	public static function execKeyInForeach( $items ) {
		$flipped = [];
		foreach ( $items as $item ) {
			$flipped[$item] = 'safe';
		}

		foreach ( $flipped as $key => $val ) {
			// Here, $key is linked to unknown offsets of $items
			echo $key;
		}
	}
}
'@taint-check-debug-method-first-arg TestForeachKey::execKeyInForeach';

TestForeachKey::execKeyInForeach( $_GET['a'] ); // Unsafe caused by 18, 19, 22, 24

class TestForeachWrapped {
	public static function execAllInForeach( $items ) {
		$wrapped = [ $items ];
		foreach ( $wrapped as $el ) {
			// Here, $el is linked to $items as a whole
			echo $el;
		}
	}
}
'@taint-check-debug-method-first-arg TestForeachWrapped::execAllInForeach';

TestForeachWrapped::execAllInForeach( $_GET['a'] ); // Unsafe

$arr = [ 'output' => 'safe', 'b' => [ 'output' => $_GET['a'] ] ];
foreach ( $arr as $value ) {
	// Here, $value must have the same taintedness as the `[ 'output' => $_GET['a'] ]` inner array.
	'@phan-debug-var-taintedness $value';
}
