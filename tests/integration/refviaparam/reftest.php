<?php

$a = "Some text";

appendStuff( $a, $_POST['foo'] );

echo $a;

function appendStuff( &$param, $arg ) {
	$param .= $arg;
}
