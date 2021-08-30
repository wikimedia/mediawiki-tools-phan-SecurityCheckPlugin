<?php

/**
 * @return-taint none
 */
function returnSafe(): string {
	return $_GET['x'];// TODO: This line must NOT appear in caused-by
}

function testCausedBy() {
	$x = $_GET['unsafe'];
	$x .= returnSafe();
	echo $x;
}
