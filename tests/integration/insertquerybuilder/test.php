<?php

use Wikimedia\Rdbms\InsertQueryBuilder;

$iqb = new InsertQueryBuilder();

$iqb->row( $_GET['a'] ); // Unsafe
$iqb->rows( $_GET['a'] ); // Unsafe

$iqb->row( [ $_GET['a'] ] ); // Safe (tries to insert in a column called '0')
$iqb->rows( [ $_GET['a'] ] ); // Unsafe

$safeKeyUnsafeValue = [
	'bar' => $_GET['b']
];
$iqb->row( $safeKeyUnsafeValue ); // Safe
$iqb->rows( $safeKeyUnsafeValue ); // Unsafe

$unsafeKeyUnsafeValue = [
	$_GET['a'] => $_GET['b']
];
$iqb->row( $unsafeKeyUnsafeValue ); // Unsafe
$iqb->rows( $unsafeKeyUnsafeValue ); // Unsafe

$unsafeKeySafeValue = [
	$_GET['a'] => 'foo'
];
$iqb->row( $unsafeKeySafeValue ); // Unsafe
$iqb->rows( $unsafeKeySafeValue ); // Safe (although it would crash because the value is not an array)



class MyValueObject {
	public $someProp;

	public function __construct( $val ) {
		$this->someProp = $val;
	}
}

function queryValueObject( MyValueObject $obj ) {
	$iqb = new InsertQueryBuilder();
	$iqb->row( [
		'some_field' => $obj->someProp // This is always safe because the prop is used as a value
	] );
}

function buildUnsafeObject() {
	$obj = new MyValueObject( $_GET['a'] ); // Safe, because the row() call above is safe.
}
