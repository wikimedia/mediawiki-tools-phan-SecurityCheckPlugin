<?php

require( "get.php" );

$a = GetFetcher::make( 'foo' )->get();

echo $a;
