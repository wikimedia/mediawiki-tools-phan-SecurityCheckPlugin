<?php

$val = $_GET['baz'];
$safe = $val + 1;
echo $safe;

$unknown1 = $_GET['unknown1'];
$unsafe = $unknown1 + $_GET['unknown2'];
echo $unsafe;

$arr = (array)$_GET['array'];
$unsafe2 = $arr + [ 'safe' => true ];
echo implode( '-', $unsafe2 );
