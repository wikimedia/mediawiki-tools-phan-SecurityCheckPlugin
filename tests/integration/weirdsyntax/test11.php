<?php

// Copied from phan's 008_invalid_node.php

call_user_func(function () {
	global $argv;
	[
		$a,
		...$b
	] = [1, 2, 3];
	list(
		$prog,
		$command,
        ...$b
    ) = $argv;
});
