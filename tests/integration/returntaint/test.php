<?php

/**
 * @return-taint onlysafefor_html
 */
function foo() {
	return "foo";
}

/**
 * @return-taint onlysafefor_htmlnoent
 */
function bar() {
	return "foo2";
}

// Should be safe
echo foo();
// Should give double escape warning.
echo htmlspecialchars( foo() );
// Should give an other warning.
require foo();

// Should be safe
echo bar();
echo htmlspecialchars( bar() );
// Should give an other warning.
require bar();
