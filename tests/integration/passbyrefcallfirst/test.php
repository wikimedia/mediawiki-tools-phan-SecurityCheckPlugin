<?php

// Test for when the call is found before the function definition

function testSafe() {
    $unsafe1 = $_GET['x'];
    echoAndEscape( $unsafe1 ); // Unsafe
    echo $unsafe1; // Safe
}

function testUnsafe() {
    $safe1 = 'foo';
    echoAndTaint( $safe1 ); // Safe
    echo $safe1; // Unsafe
}

function echoAndEscape( string &$arg ) {
    echo $arg;
    $arg = htmlspecialchars( $arg );
}

function echoAndTaint( string &$arg ) {
    echo $arg;
    $arg = $_GET['foo'];
}

