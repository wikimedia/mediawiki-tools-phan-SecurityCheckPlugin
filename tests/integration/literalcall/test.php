<?php

/* Test for literal calls -- this is valid PHP syntax */

$foo = 'strlen'( $_GET['baz'] );
echo $foo;

function echoEvil( $x ) {
	echo $x;
}
'echoEvil'( $_GET['foo'] );
