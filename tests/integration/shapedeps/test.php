<?php

function buildForm( $f ) {
	foreach ( $f as $k => $v ) {
		htmlspecialchars( $k );
	}
}
$x = [ 'foo' => htmlspecialchars( 'x' ) ];
buildForm( $x ); // Safe, it's the key that gets escaped


function safe1() {
	$array = [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ];
	echoSafe( $array ); // Safe because it outputs the safe value
}
function echoSafe( $array ) {
	echo $array['safe'];
}


function safe2() {
	$array = [ 'esc' => htmlspecialchars( 'foo' ), 'unsafe' => $_GET['a'] ];
	escapeUnsafe( $array ); // Safe because it escapes the unsafe value
}
function escapeUnsafe( $array ) {
	htmlspecialchars( $array['unsafe'] );
}


function unsafe1() {
	$array = [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ];
	echoUnsafe( $array ); // Unsafe, it outputs the unsafe value
}
function echoUnsafe( $array ) {
	echo $array['unsafe'];
}


function unsafe2() {
	$array = [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ];
	echoUnknown( $array ); // Unsafe, can't determine what value is output
}
function echoUnknown( $array ) {
	echo $array[$GLOBALS['foo']];
}


function unsafe3() {
	$array = [ 'esc' => htmlspecialchars( 'foo' ), 'unsafe' => $_GET['a'] ];
	escapeEscaped( $array ); // Unsafe, it escapes the escaped value
}
function escapeEscaped( $array ) {
	htmlspecialchars( $array['esc'] );
}


function unsafe4() {
	$array = [ 'esc' => htmlspecialchars( 'foo' ), 'unsafe' => $_GET['a'] ];
	escapeUnknown( $array ); // Unsafe, we don't know what value is escaped
}
function escapeUnknown( $array ) {
	htmlspecialchars( $array[$GLOBALS['a']] );
}


function safe3() {
	echoSecondUnsafe( [
		'first' => [ 'unsafe' => $_GET['a'], 'safe' => 'safe' ],
		'second' => [ 'unsafe' => 'safe', 'safe' => 'safe' ]
	] );// Safe, it echoes from second
}
function echoSecondUnsafe( $arg ) {
	$sec = $arg['second'];
	echo $sec['unsafe'];
}
