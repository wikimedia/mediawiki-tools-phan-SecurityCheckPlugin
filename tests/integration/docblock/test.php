<?php

// unsafe
echo getUnsafeHTML();

// safe
echo escapeHTML( getUnsafeHTML() );

// unsafe (double escape)
echo escapeHTML( escapeHTML( 'foo' ) );

// safe
doQuery( getUnsafeHTML() );
// unsafe
doQuery( getUserInput() );

// unsafe
wfShellExec2( getUserInput() );
// safe
wfShellExec2( [ getUserInput() ] );
// safe
wfShellExec2( getUnsafeHTML() );

// Safe
doQuery( getSomeSQL() );
// unsafe
echo getSomeSQL();
// unsafe
wfShellExec2( 'grep "' . getSomeSQL() . '" foo' );

// safe
safeOutput( getUserInput() );
// safe
echo getSafeString();
// safe
echo invalidTaint();

// unsafe
multiTaint( getUserInput() );
multiTaint( getSomeSQL() );
multiTaint( escapeHTML( getSomeSQL() ) );
multiTaint( getUnsafeHTML() );
$a = 'foo';
passByRef( getUserInput(), $a );
$a = getUnsafeHTML();
