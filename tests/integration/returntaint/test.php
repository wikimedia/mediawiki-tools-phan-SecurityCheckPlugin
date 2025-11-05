<?php

/**
 * @return-taint onlysafefor_html
 */
function returnOnlySafeForHTML() {
	return "foo";
}

/**
 * @return-taint onlysafefor_htmlnoent
 */
function returnOnlySafeForHTMLNoent() {
	return "foo2";
}

// Should be safe
echo returnOnlySafeForHTML();
// Should give double escape warning.
echo htmlspecialchars( returnOnlySafeForHTML() );
// Should give an other warning.
require returnOnlySafeForHTML();

// Should be safe
echo returnOnlySafeForHTMLNoent();
echo htmlspecialchars( returnOnlySafeForHTMLNoent() );
// Should give an other warning.
require returnOnlySafeForHTMLNoent();
