<?php

/** @return-taint shell */
function getShell(): string {
	return 'hardcoded';
}

function returnParam( $par ) {
	$a = $par;
	return $a;
}

function returnParamWithAddedShellTaint( $par2 ) {
	$b = '';
	$b .= $par2;
	$b .= getShell();
	$b .= returnParam( $par2 );
	$b .= getShell();
	return $b;
}

function execParamWithAddedShellAndReturnIt( $par3 ) {
	$c = $par3;
	$d = returnParamWithAddedShellTaint( $c );
	echo $d;
	return $d;
}

shell_exec( execParamWithAddedShellAndReturnIt( 'safe' ) ); // ShellInjection caused by 26, 24, 19, 18, 16, annotation
// For the following line we must have:
// * TODO: ShellInjection caused by: ( 26, 24, 19, 18, 16, annotation ) [direct taint], ( 23, 24, 15, 17, 9, 10, 19, 26 ) [preserved taint]
// * TODO: XSS caused by 23, 24, 15, 17, 9, 10, 19, 25
shell_exec( execParamWithAddedShellAndReturnIt( $_GET['unsafe'] ) );

function putInArray( $par ) {
	$a = $par;
	$b = [ $a ];
	$c = $b;
	return $c;
}
function echoPutInArray( $arg ) {
	$x = $arg;
	$y = putInArray( $x );
	echo $y;
}
echoPutInArray( $_GET['a'] ); // TODO: XSS caused by 42, 43, 36, 37, 38, 39, 44

function simplePreserveWithAssignment1( $a ) {
	$b = $a;
	return $b;
}

function simplePreserveWithAssignment2( $c ) {
	$e = $c;
	$d = simplePreserveWithAssignment1( $e );
	return $d;
}

echo simplePreserveWithAssignment1( $_GET['a'] ); // XSS caused by 49, 50
echo simplePreserveWithAssignment2( $_GET['a'] ); // TODO: XSS caused by 54, 55, 49, 50, 56

function simplePreserve( $x ) {
	return $x;
}
function echoSimplePreserved( $x ) {
	$v = simplePreserve( $x );
	echo $v;
}
echoSimplePreserved( $_GET['a'] ); // TODO: XSS caused by 66, 63, 67

function returnTainted() {
	$a = $_GET['x'];
	$b = $a;
	$c = $b;
	return $c;
}
echo returnTainted(); // XSS caused by 75, 74, 73, 72
