<?php

namespace SkinJson;

function wfRegister( $unused, $taint, $ok ) {
	echo $ok;
	echo $taint;
}

class SomeClass {
	public static function onHook2(
		&$output,
		$page,
		$title,
		$user,
		$request,
		$mw
	) {
		$output = $_GET['something'];
	}
}

function doStuff() {
	( new HookRunner() )->onHook1( 'unused', $_GET['tainted'], 'foo' );
	$output = '';
	( new HookRunner() )->onHook2( $output );
	echo $output;
}
