<?php

// Reduced from upstream 003_pipe_operator.php

( static function() {
	$arr = "test"
			|> strtoupper(...)
			|> str_split(...);

	'@phan-debug-var $arr'; // Must report that it's an array
} )();

