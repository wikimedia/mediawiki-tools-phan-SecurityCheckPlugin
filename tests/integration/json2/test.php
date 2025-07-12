<?php

namespace TestJson2;

interface FirstHook {
	public function onFirst( &$arg );
}

interface SecondHook {
	public function onSecond( &$arg );
}

class MyHookRunner implements FirstHook, SecondHook {
	public function onFirst( &$arg ) {
	}

	public function onSecond( &$arg ) {
	}
}

class MyHookHandler {
	public function onFirst( &$arg ) {
		$arg = $_GET['something'];
	}
	public function onSecond( $arg ) {
		echo $arg;
	}
	public static function additionalSecondHookHandler( $arg ) {
		echo $arg;
	}
	public function onThirdHook__method() {

	}
	public function onFourthHook_spec() {

	}
}

function doStuff( MyHookRunner $hookRunner ) {
	$arg1 = '';
	$hookRunner->onFirst( $arg1 );
	echo $arg1;
	$hookRunner->onSecond( $arg1 );
}
