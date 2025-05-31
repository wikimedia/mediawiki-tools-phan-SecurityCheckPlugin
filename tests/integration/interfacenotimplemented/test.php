<?php

interface InterfaceNotImplemented {
	// Because no implementations of the interface exist, we can't infer the taintedness of the below method.
	// Crucially, it must not be assumed to be unsafe.
	public function getVal( string $x ): string;
}

function getValFromInterface( InterfaceNotImplemented $interface ): ?string {
	// The return value isn't necessarily unsafe, even if the argument is unsafe. So, this function should not be
	// marked as unsafe.
	return $interface->getVal( $_GET['a'] );
}

/**
 * @param-taint $x exec_sql
 */
function doSqlExec( string $x ) {
}

$interfaceVal = getValFromInterface();
doSqlExec( $interfaceVal ); // Safe.


