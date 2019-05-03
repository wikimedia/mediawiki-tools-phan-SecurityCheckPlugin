<?php

$x = $_GET['foo'];

echo $x++;

echo $x;

$y = $_GET['bar'];

echo --$y;

echo $y;

echo $_GET['bar']--;

echo ++$_GET['foo'];
