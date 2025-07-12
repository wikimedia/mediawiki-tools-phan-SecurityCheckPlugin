<?php

namespace PassByRefHook;

interface PassByRefHook {
	public function onPassByRef( &$arg );
}
class HookRunner implements PassByRefHook {
	public function onPassByRef( &$arg ) {
	}
}

class MyClass {
	public static function myHandler( &$arg ) {
		$arg = $_GET['x'];
	}
}

function testEvil() {
	$var1 = '';
	( new HookRunner() )->onPassByRef( $var1 );
	echo $var1;
}
