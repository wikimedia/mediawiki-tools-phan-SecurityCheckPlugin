<?php

// Make sure we don't fatal
function bah() {
	$foo = 'bog';
	global $$foo;
}
