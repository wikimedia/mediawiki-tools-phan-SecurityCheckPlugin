<?php

$a = "Some text";

appendStuff( $a );

echo escapeshellarg($a);

function appendStuff( &$param ) {
	$param .= $_POST['foo'];
}
