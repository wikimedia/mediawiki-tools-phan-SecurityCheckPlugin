<?php

function echoSafe( int $x ) {
	echo $x;
}
echoSafe( $_GET['foo'] );

function echoUnsafe( string $x ) {
	echo $x;
}
echoUnsafe( $_GET['baz'] );

function returnSafe() : int {
	return $_GET['baz'];
}
echo returnSafe();

function returnUnsafe() : string {
	return $_GET['baz'];
}
echo returnUnsafe();
