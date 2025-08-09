<?php

/**
 * @param string $key
 */
function annotatedAsString( $key ) {
	$keyFromDocComment = [ $key => $_GET['bar'] ];
	'@phan-debug-var-taintedness $keyFromDocComment';
	execNumkey( $keyFromDocComment ); // We consider this safe, although $key might actually be an int
}

function typehintedAsString( string $key ) {
	$keyFromTypeDeclaration = [ $key => $_GET['bar'] ];
	'@phan-debug-var-taintedness $keyFromTypeDeclaration';
	execNumkey( $keyFromTypeDeclaration ); // We consider this safe, although the key might be int if $key is the canonical representation of an int
}

/**
 * @return string
 */
function returnsStringDoc() {
	return $GLOBALS['unknown'];
}

function returnsStringReal(): string {
	return $GLOBALS['unknown'];
}

$keyFromReturnDocComment = [ returnsStringDoc() => $_GET['bar'] ];
'@phan-debug-var-taintedness $keyFromReturnDocComment';
execNumkey( $keyFromReturnDocComment ); // We consider this safe, although the key might actually be an int
$keyFromReturnTypeDeclaration = [ returnsStringReal() => $_GET['bar'] ];
'@phan-debug-var-taintedness $keyFromReturnTypeDeclaration';
execNumkey( $keyFromReturnTypeDeclaration ); // We consider this safe, although the key could be autocast to int
