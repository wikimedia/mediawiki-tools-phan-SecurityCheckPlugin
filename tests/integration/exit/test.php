<?php

function exit1() {
	exit( $_GET['baz'] ); // Unsafe
}
function exit2() {
	exit( 1 ); // Safe!
}
function exit3() {
	exit(); // Safe!
}

function die1() {
	die( $_GET['baz'] ); // Unsafe
}
function die2() {
	die( 1 ); // Safe!
}
function die3() {
	die(); // Safe!
}
