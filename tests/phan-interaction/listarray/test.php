<?php
/*
 * For T249647
 */

$r = [];
$r['user'] = $_GET['foo'];
list( $r['added'] ) = [ $_GET ]; // Avoid: "PhanTypeInvalidDimOffset Invalid offset "added" of array type ..."
[ $r['foo'] ] = [ $_GET ]; // Avoid: "PhanTypeInvalidDimOffset Invalid offset "foo" of array type ..."
list( $r[42] ) = [ $_GET ]; // Avoid: "PhanTypeInvalidDimOffset Invalid offset 42 of array type ..."
[ $r[43] ] = [ $_GET ]; // Avoid: "PhanTypeInvalidDimOffset Invalid offset 43 of array type ..."
