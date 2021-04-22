<?php

// All safe, see T272492

echo md5( $_GET['a'] );
$safe = crc32( $_GET['foo'] );
`$safe`;
require sha1( $_POST['goat'] );
