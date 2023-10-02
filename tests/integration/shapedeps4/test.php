<?php

// Echo all

function echoAll_WholeParam( $par ) {
	echoAll( $par );
}
echoAll_WholeParam( $_GET['a'] ); // Unsafe

function echoAll_ParamInFooKey( $par ) {
	echoAll( [ 'foo' => $par ] );
}
echoAll_ParamInFooKey( $_GET['a'] ); // Unsafe

function echoAll_ParamInBarKey( $par ) {
	echoAll( [ 'bar' => $par ] );
}
echoAll_ParamInBarKey( $_GET['a'] ); // Unsafe

function echoAll_ParamInUnknownKey( $par ) {
	echoAll( [ rand() => $par ] );
}
echoAll_ParamInUnknownKey( $_GET['a'] ); // Unsafe

function echoAll_ParamAsKey( $par ) {
	echoAll( [ $par => 'foo' ] );
}
echoAll_ParamAsKey( $_GET['a'] ); // Unsafe

// Echo foo key

function echoFooKey_WholeParam( $par ) {
	echoFooKey( $par );
}
echoFooKey_WholeParam( $_GET['a'] ); // Unsafe

function echoFooKey_ParamInFooKey( $par ) {
	echoFooKey( [ 'foo' => $par ] );
}
echoFooKey_ParamInFooKey( $_GET['a'] ); // Unsafe

function echoFooKey_ParamInBarKey( $par ) {
	echoFooKey( [ 'bar' => $par ] );
}
echoFooKey_ParamInBarKey( $_GET['a'] ); // TODO Safe

function echoFooKey_ParamInUnknownKey( $par ) {
	echoFooKey( [ rand() => $par ] );
}
echoFooKey_ParamInUnknownKey( $_GET['a'] ); // Unsafe

function echoFooKey_ParamAsKey( $par ) {
	echoFooKey( [ $par => 'foo' ] );
}
echoFooKey_ParamAsKey( $_GET['a'] ); // Safe

// Echo unknown

function echoUnknown_WholeParam( $par ) {
	echoUnknown( $par );
}
echoUnknown_WholeParam( $_GET['a'] ); // Unsafe

function echoUnknown_ParamInFooKey( $par ) {
	echoUnknown( [ 'foo' => $par ] );
}
echoUnknown_ParamInFooKey( $_GET['a'] ); // Unsafe

function echoUnknown_ParamInBarKey( $par ) {
	echoUnknown( [ 'bar' => $par ] );
}
echoUnknown_ParamInBarKey( $_GET['a'] ); // Unsafe

function echoUnknown_ParamInUnknownKey( $par ) {
	echoUnknown( [ rand() => $par ] );
}
echoUnknown_ParamInUnknownKey( $_GET['a'] ); // Unsafe

function echoUnknown_ParamAsKey( $par ) {
	echoUnknown( [ $par => 'foo' ] );
}
echoUnknown_ParamAsKey( $_GET['a'] ); // Safe

// Echo keys

function echoKeys_WholeParam( $par ) {
	echoKeys( $par );
}
echoKeys_WholeParam( $_GET['a'] ); // Unsafe

function echoKeys_ParamInFooKey( $par ) {
	echoKeys( [ 'foo' => $par ] );
}
echoKeys_ParamInFooKey( $_GET['a'] ); // Safe

function echoKeys_ParamInBarKey( $par ) {
	echoKeys( [ 'bar' => $par ] );
}
echoKeys_ParamInBarKey( $_GET['a'] ); // Safe

function echoKeys_ParamInUnknownKey( $par ) {
	echoKeys( [ rand() => $par ] );
}
echoKeys_ParamInUnknownKey( $_GET['a'] ); // Safe

function echoKeys_ParamAsKey( $par ) {
	echoKeys( [ $par => 'foo' ] );
}
echoKeys_ParamAsKey( $_GET['a'] ); // Unsafe

// Echo all and foo key

function echoAllAndFooKey_WholeParam( $par ) {
	echoAllAndFooKey( $par );
}
echoAllAndFooKey_WholeParam( $_GET['a'] ); // Unsafe

function echoAllAndFooKey_ParamInFooKey( $par ) {
	echoAllAndFooKey( [ 'foo' => $par ] );
}
echoAllAndFooKey_ParamInFooKey( $_GET['a'] ); // Unsafe

function echoAllAndFooKey_ParamInBarKey( $par ) {
	echoAllAndFooKey( [ 'bar' => $par ] );
}
echoAllAndFooKey_ParamInBarKey( $_GET['a'] ); // Unsafe

function echoAllAndFooKey_ParamInUnknownKey( $par ) {
	echoAllAndFooKey( [ rand() => $par ] );
}
echoAllAndFooKey_ParamInUnknownKey( $_GET['a'] ); // Unsafe

function echoAllAndFooKey_ParamAsKey( $par ) {
	echoAllAndFooKey( [ $par => 'foo' ] );
}
echoAllAndFooKey_ParamAsKey( $_GET['a'] ); // Unsafe

// Echo all and unknown

function echoAllAndUnknown_WholeParam( $par ) {
	echoAllAndUnknown( $par );
}
echoAllAndUnknown_WholeParam( $_GET['a'] ); // Unsafe

function echoAllAndUnknown_ParamInFooKey( $par ) {
	echoAllAndUnknown( [ 'foo' => $par ] );
}
echoAllAndUnknown_ParamInFooKey( $_GET['a'] ); // Unsafe

function echoAllAndUnknown_ParamInBarKey( $par ) {
	echoAllAndUnknown( [ 'bar' => $par ] );
}
echoAllAndUnknown_ParamInBarKey( $_GET['a'] ); // Unsafe

function echoAllAndUnknown_ParamInUnknownKey( $par ) {
	echoAllAndUnknown( [ rand() => $par ] );
}
echoAllAndUnknown_ParamInUnknownKey( $_GET['a'] ); // Unsafe

function echoAllAndUnknown_ParamAsKey( $par ) {
	echoAllAndUnknown( [ $par => 'foo' ] );
}
echoAllAndUnknown_ParamAsKey( $_GET['a'] ); // Unsafe

// Echo all and keys

function echoAllAndKeys_WholeParam( $par ) {
	echoAllAndKeys( $par );
}
echoAllAndKeys_WholeParam( $_GET['a'] ); // Unsafe

function echoAllAndKeys_ParamInFooKey( $par ) {
	echoAllAndKeys( [ 'foo' => $par ] );
}
echoAllAndKeys_ParamInFooKey( $_GET['a'] ); // Unsafe

function echoAllAndKeys_ParamInBarKey( $par ) {
	echoAllAndKeys( [ 'bar' => $par ] );
}
echoAllAndKeys_ParamInBarKey( $_GET['a'] ); // Unsafe

function echoAllAndKeys_ParamInUnknownKey( $par ) {
	echoAllAndKeys( [ rand() => $par ] );
}
echoAllAndKeys_ParamInUnknownKey( $_GET['a'] ); // Unsafe

function echoAllAndKeys_ParamAsKey( $par ) {
	echoAllAndKeys( [ $par => 'foo' ] );
}
echoAllAndKeys_ParamAsKey( $_GET['a'] ); // Unsafe

// Echo foo key and unknown

function echoFooKeyAndUnknown_WholeParam( $par ) {
	echoFooKeyAndUnknown( $par );
}
echoFooKeyAndUnknown_WholeParam( $_GET['a'] ); // Unsafe

function echoFooKeyAndUnknown_ParamInFooKey( $par ) {
	echoFooKeyAndUnknown( [ 'foo' => $par ] );
}
echoFooKeyAndUnknown_ParamInFooKey( $_GET['a'] ); // Unsafe

function echoFooKeyAndUnknown_ParamInBarKey( $par ) {
	echoFooKeyAndUnknown( [ 'bar' => $par ] );
}
echoFooKeyAndUnknown_ParamInBarKey( $_GET['a'] ); // Unsafe

function echoFooKeyAndUnknown_ParamInUnknownKey( $par ) {
	echoFooKeyAndUnknown( [ rand() => $par ] );
}
echoFooKeyAndUnknown_ParamInUnknownKey( $_GET['a'] ); // Unsafe

function echoFooKeyAndUnknown_ParamAsKey( $par ) {
	echoFooKeyAndUnknown( [ $par => 'foo' ] );
}
echoFooKeyAndUnknown_ParamAsKey( $_GET['a'] ); // Safe

// Echo foo key and keys

function echoFooKeyAndKeys_WholeParam( $par ) {
	echoFooKeyAndKeys( $par );
}
echoFooKeyAndKeys_WholeParam( $_GET['a'] ); // Unsafe

function echoFooKeyAndKeys_ParamInFooKey( $par ) {
	echoFooKeyAndKeys( [ 'foo' => $par ] );
}
echoFooKeyAndKeys_ParamInFooKey( $_GET['a'] ); // Unsafe

function echoFooKeyAndKeys_ParamInBarKey( $par ) {
	echoFooKeyAndKeys( [ 'bar' => $par ] );
}
echoFooKeyAndKeys_ParamInBarKey( $_GET['a'] ); // TODO Safe

function echoFooKeyAndKeys_ParamInUnknownKey( $par ) {
	echoFooKeyAndKeys( [ rand() => $par ] );
}
echoFooKeyAndKeys_ParamInUnknownKey( $_GET['a'] ); // Unsafe

function echoFooKeyAndKeys_ParamAsKey( $par ) {
	echoFooKeyAndKeys( [ $par => 'foo' ] );
}
echoFooKeyAndKeys_ParamAsKey( $_GET['a'] ); // Unsafe

// Echo unknown and keys

function echoUnknownAndKeys_WholeParam( $par ) {
	echoUnknownAndKeys( $par );
}
echoUnknownAndKeys_WholeParam( $_GET['a'] ); // Unsafe

function echoUnknownAndKeys_ParamInFooKey( $par ) {
	echoUnknownAndKeys( [ 'foo' => $par ] );
}
echoUnknownAndKeys_ParamInFooKey( $_GET['a'] ); // Unsafe

function echoUnknownAndKeys_ParamInBarKey( $par ) {
	echoUnknownAndKeys( [ 'bar' => $par ] );
}
echoUnknownAndKeys_ParamInBarKey( $_GET['a'] ); // Unsafe

function echoUnknownAndKeys_ParamInUnknownKey( $par ) {
	echoUnknownAndKeys( [ rand() => $par ] );
}
echoUnknownAndKeys_ParamInUnknownKey( $_GET['a'] ); // Unsafe

function echoUnknownAndKeys_ParamAsKey( $par ) {
	echoUnknownAndKeys( [ $par => 'foo' ] );
}
echoUnknownAndKeys_ParamAsKey( $_GET['a'] ); // Unsafe
