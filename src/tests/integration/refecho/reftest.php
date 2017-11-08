<?php

$a = "Some text";

appendStuff( $a );

echo $a;

function appendStuff( &$param ) {
	$param .= $_POST['foo'];
}
