<?php

// We need to load this class now, so that when PHPUnit takes a snapshot of static attributes
// it also includes GlobalScope::global_variable_map. Failing to back up this property (or backing
// it up when it was already populated) will result in weird false positives.
require_once __DIR__ . '/../vendor/phan/phan/src/Phan/Language/Scope/GlobalScope.php';
require_once __DIR__ . '/../vendor/autoload.php';
// Then we need to do that for our files, to catch e.g. SecurityCheckPlugin::$docblockCache
// and TaintednessBaseVisitor::$reanalyzedClasses
$taintCheckFiles = scandir( __DIR__ . '/../src/' );
foreach ( $taintCheckFiles as $file ) {
	if ( $file[0] !== '.' ) {
		$class = str_replace( '.php', '', $file );
		class_exists( "\\SecurityCheckPlugin\\$class" );
	}
}
