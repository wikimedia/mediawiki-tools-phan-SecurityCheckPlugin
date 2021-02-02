<?php

function named1( $ignored = '', $used = '' ) {
	echo $used;
}
named1( ignored: $_GET['A'] ); // Safe
named1( used: $_GET['A'] ); // Unsafe
named1( doesnotexist: $_GET['x'] ); // Considered safe

function passThroughNamed( $ignored = '', $used = '' ) {
	return $used;
}
echo passThroughNamed( ignored: $_GET['a'] ); // Safe
echo passThroughNamed( used: $_GET['a'] ); // Unsafe
echo passThroughNamed( doesnotexist: $_GET['a'] ); // Considered safe

