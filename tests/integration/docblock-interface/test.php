<?php

$foo = new Foo;

// unsafe
echo $foo->getUnsafeHTML();

// safe
echo $foo->escapeHTML( $foo->getUnsafeHTML() );

// unsafe (double escape)
echo $foo->escapeHTML( $foo->escapeHTML( 'foo' ) );

// safe
$foo->doQuery( $foo->getUnsafeHTML() );
// unsafe
$foo->doQuery( $foo->getUserInput() );

// unsafe
$foo->wfShellExec2( $foo->getUserInput() );
// safe
$foo->wfShellExec2( [ $foo->getUserInput() ] );
// safe
$foo->wfShellExec2( $foo->getUnsafeHTML() );

// Safe
$foo->doQuery( $foo->getSomeSQL() );
// unsafe
echo $foo->getSomeSQL();
// unsafe
$foo->wfShellExec2( 'grep "' . $foo->getSomeSQL() . '" foo' );

// safe
$foo->safeOutput( $foo->getUserInput() );
// safe
echo $foo->getSafeString();
// safe
echo $foo->invalidTaint();

// unsafe
$foo->multiTaint( $foo->getUserInput() );
$foo->multiTaint( $foo->getSomeSQL() );
$foo->multiTaint( $foo->escapeHTML( $foo->getSomeSQL() ) );
$foo->multiTaint( $foo->getUnsafeHTML() );
$a = 'foo';
$foo->passByRef( $foo->getUserInput(), $a );
$a = $foo->getUnsafeHTML();
