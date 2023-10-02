<?php

// Simple sinks

function echoAll( $x ) {
	echo $x;
}

function echoFooKey( $x ) {
	echo $x['foo'];
}

function echoUnknown( $x ) {
	echo $x[rand()];
}

function echoKeys( $x ) {
	foreach ( $x as $k => $_ ) {
		echo $k;
	}
}

// Combinations of the previous

function echoAllAndFooKey( $x ) {
	echo $x;
	echo $x['foo'];
}

function echoAllAndUnknown( $x ) {
	echo $x;
	echo $x[rand()];
}

function echoAllAndKeys( $x ) {
	echo $x;
	foreach ( $x as $k => $_ ) {
		echo $k;
	}
}

function echoFooKeyAndUnknown( $x ) {
	echo $x['foo'];
	echo $x[rand()];
}

function echoFooKeyAndKeys( $x ) {
	echo $x['foo'];
	foreach ( $x as $k => $_ ) {
		echo $k;
	}
}

function echoUnknownAndKeys( $x ) {
	echo $x[rand()];
	foreach ( $x as $k => $_ ) {
		echo $k;
	}
}
