<?php


class Hooks {
	public static function run( $hookName, $args ) {
	}
}

function wfRegister( Parser $parser, $taint, $ok ) {
	echo $ok;
	echo $taint;
}

class SomeClass {
	public static function onMediaWikiPerformAction(
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
	$parser = new Parser;
	Hooks::run( 'ParserFirstCallInit', [ $parser, $_GET['tainted'], 'foo' ] );
	$output = '';
	Hooks::run( 'MediaWikiPerformAction', [ &$output ] );
	echo $output;
}
