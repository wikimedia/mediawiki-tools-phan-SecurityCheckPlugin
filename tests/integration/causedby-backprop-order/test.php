<?php

// Test ordering of backpropagated caused-by lines

function test1Main() {
	$a = $_GET;
	$b = $a;
	test1Sink( $b ); // Sink caused by 12, 13, 14 (in this order); arg by 7, 6 (in this order)
}

function test1Sink( $t ) {
	$x = $t;
	$y = $x;
	echo $y;
}


function test2Main() {
	$a = $_GET;
	$b = $a;
	test2Middle( $b );// Sink caused by 25, 26, 27, 31, 32, 33 (in this order); arg by 20, 19 (in this order)
}

function test2Middle( $c ) {
	$d = $c;
	$e = $d;
	test2Sink( $e );
}

function test2Sink( $f ) {
	$g = $f;
	$h = $g;
	echo $h;
}
