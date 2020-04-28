<?php

function outerFunc( &$ref ) {
	$clos = function ( &$refArg ) use ( &$ref ) {
		$refArg = $_GET['x'];
		$ref = $_GET['x'];
	};
	$var = 'safe';
	$clos( $var );
	echo $var; // NOTE: This is unsafe, but not caught because Analyzable::analyse skips closures with `use`.
	echo $ref;
}

$globVar = 'safe';
outerFunc( $globVar );
echo $globVar;
