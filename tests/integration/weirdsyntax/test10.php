<?php

// Not really weird syntax, but caused a crash. Taken from phan's 010_traversable_array_spread.php

function test_array_spread( $x ) {
	var_export( [ ...$x ] ); // This would emit: ASTReverter.php:258 [8] Undefined index: value
}
