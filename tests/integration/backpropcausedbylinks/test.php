<?php

/** @param-taint $x exec_html */
function sinkFunc( $x ) {
}

triggerBackpropagation();

function addsUnsafeAndCallsSink( $arg ) {
	$unsafe = $arg;
	$unsafe .= $_GET['a'];
	sinkFunc( $unsafe ); // Unsafe caused by 11, annotation
}

function triggerBackpropagation() {
	$x = $_GET['a'];
	addsUnsafeAndCallsSink( $x ); // Unsafe caused by 10, 12, annotation, 16 (line 11 isn't relevant here)
}
