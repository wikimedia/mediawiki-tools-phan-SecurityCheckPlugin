<?php

echo empty( $_GET['x'] );

$evil = $_GET['y'];
$safe = empty( $evil );
echo $safe;
echo empty( $notexistent );
