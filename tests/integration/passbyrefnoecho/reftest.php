<?php

$a = "Some text";

appendStuff( $a );

function appendStuff( &$param ) {
	$param .= $_POST['foo'];
}
