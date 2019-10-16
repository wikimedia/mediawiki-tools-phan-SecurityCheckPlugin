<?php

$evil = (object)$_GET['baz'];
$evilCopy = clone $evil;

echo $evil;
echo $evilCopy;
