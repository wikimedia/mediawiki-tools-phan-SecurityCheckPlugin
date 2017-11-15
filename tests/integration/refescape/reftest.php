<?php

$a = "Some text";

appendStuff( $a );

echo htmlspecialchars( $a );

function appendStuff( &$param ) {
	$param .= $_POST['foo'];
}
