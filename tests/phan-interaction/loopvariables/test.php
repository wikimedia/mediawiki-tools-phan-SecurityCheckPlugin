<?php

// Taken from phan's 0675_unused_loop_variable_2.php"

call_user_func (function () {
	foreach ([1,2,3] as $i) {
		if (rand() % 2) {
			$c = $i;
		}
		'@phan-debug-var $c';
		$c = $c + 1;  // With taint-check enabled, PhanPossiblyUndeclaredVariable wasn't emitted
	}
});
