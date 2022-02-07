<?php

// Extracted from phan's 207_invalid_offset.php

$arr = ['string' => 'z', '' => 'x', 0 => []];
function expect_string(string $x) { echo $x; }
expect_string($arr[STDIN]); // Ensure it won't use `resource` as offset, see https://github.com/phan/phan/issues/4659
