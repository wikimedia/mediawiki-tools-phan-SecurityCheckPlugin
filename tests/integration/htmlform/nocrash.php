<?php

$obj = (object)[ 'prop' => 42 ];

$nonStringType = [
	'type' => $obj->prop,
];

$nonStringClass = [
	'class' => $obj->prop,
];
