<?php

// Ensure that multiple calls to the same function taking a pass-by-ref work as expected

function alwaysEscape( string &$arg ) {
    $arg = htmlspecialchars( $arg );
}

function alwaysTaint( string &$arg ) {
    $arg = $_GET['foo'];
}

function testSafe() {
    $unsafe1 = (string)$_GET['x'];
    alwaysEscape( $unsafe1 );
    echo $unsafe1;
    $unsafe2 = (string)$_GET['y'];
    alwaysEscape( $unsafe2 );
    echo $unsafe2;
}

function testUnsafe() {
    $safe1 = 'foo';
    alwaysTaint( $safe1 );
    echo $safe1;
    $safe2 = 'bar';
    alwaysTaint( $safe2 );
    echo $safe2;
}
