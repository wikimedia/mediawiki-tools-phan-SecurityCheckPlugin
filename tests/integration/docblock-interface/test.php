<?php

$obj = new DocblockClass();

// unsafe
echo $obj->getUnsafeHTML();

// safe
echo $obj->escapeHTML( $obj->getUnsafeHTML() );

// unsafe (double escape)
echo $obj->escapeHTML( $obj->escapeHTML( 'foo' ) );

// safe
$obj->doQuery( $obj->getUnsafeHTML() );
// unsafe
$obj->doQuery( $obj->getUserInput() );

// unsafe
$obj->wfShellExec2( $obj->getUserInput() );
// safe
$obj->wfShellExec2( [ $obj->getUserInput() ] );
// safe
$obj->wfShellExec2( $obj->getUnsafeHTML() );

// Safe
$obj->doQuery( $obj->getSomeSQL() );
// unsafe
echo $obj->getSomeSQL();
// unsafe
$obj->wfShellExec2( 'grep "' . $obj->getSomeSQL() . '" foo' );

// safe
$obj->safeOutput( $obj->getUserInput() );
// safe
echo $obj->getSafeString();
// safe
echo $obj->invalidTaint();

// unsafe
$obj->multiTaint( $obj->getUserInput() );
$obj->multiTaint( $obj->getSomeSQL() );
$obj->multiTaint( $obj->escapeHTML( $obj->getSomeSQL() ) ); // TODO Ideally caused by DocblockInterface line 6
$obj->multiTaint( $obj->getUnsafeHTML() );
$a = 'foo';
$obj->passByRef( $obj->getUserInput(), $a );
$a = $obj->getUnsafeHTML();
