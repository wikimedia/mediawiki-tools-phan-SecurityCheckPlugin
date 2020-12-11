<?php

$taint = $_GET['baz'];

file_put_contents( $taint, 'h4x0r3d' );
fopen( $taint, 0 );
opendir( $taint );
print $taint;
printf( "$taint %s",'foo' );
printf( "%s", $taint );
printf( "%d", $taint ); // TODO Ideally safe
proc_open( $taint, [], $_ );

$fileName = rawurlencode( $_GET['foo'] );
file_get_contents( $fileName ); // Safe