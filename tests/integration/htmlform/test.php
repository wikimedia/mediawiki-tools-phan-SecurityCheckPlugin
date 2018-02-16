<?php

$unsafe1 = [
	'type' => 'info',
	'default' => $_GET['evil'],
	'raw' => true,
];

$safe1 = [
	'type' => 'info',
	'default' => htmlspecialchars( $_GET['evil'] ),
	'raw' => true,
];

$safe2 = [
	'type' => 'info',
	'default' => $_GET['evil'],
	'raw' => false,
];

$safe3 = [
	'type' => 'info',
	'default' => $_GET['evil'],
];

$unsafe2 = [
	'type' => 'check',
	'label-raw' => "Something " . $_GET['evil'],
	'name' => 'wpFoo'
];

$unsafe3 = [
	'type' => 'check',
	'label' => htmlspecialchars( "Something " . $_GET['double-esc'] ),
	'name' => 'wpFoo'
];

$safe4 = [
	'type' => 'check',
	'label' => "Something " . $_GET['evil'],
	'name' => 'wpFoo'
];

$safe5 = [
	'type' => 'nothtmlformtype',
	'label-raw' => "Something " . $_GET['evil'],
	'name' => 'wpFoo'
];

$safe6 = [
	'class' => 'HTMLNotAFormField',
	'label-raw' => "Something " . $_GET['evil'],
	'name' => 'wpFoo'
];

$unsafe4 = [
	'class' => 'HTMLCheckField',
	'label-raw' => "Something " . $_GET['evil'],
	'name' => 'wpFoo'
];

$safe7 = [
	'type' => 'check',
	'default' => $_GET['evil'],
];

$unsafe5 = [
	'class' => 'HTMLInfoField',
	'default' => $_GET['evil'],
	'raw' => true,
];

$unsafe6 = [
	'class' => HTMLInfoField::class,
	'default' => $_GET['evil'],
	'raw' => true,
];

$safe8 = [
	'class' => 'SomeOtherClass',
	'label-raw' => "Something " . $_GET['evil'],
	'name' => 'wpFoo'
];

$safe9 = [
	'type' => 'select',
	'options' => [
		htmlspecialchars( $_GET['evil'] ) => 'the good value',
		$_GET['evil'] => 'the evil value'
	]
];

$unsafe7 = [
	'type' => 'radio',
	'options' => [
		htmlspecialchars( $_GET['evil'] ) => 'the good value',
		$_GET['evil'] => 'the evil value'
	]
];

$unsafe8 = [
	'class' => HTMLRadioField::class,
	'options' => [
		htmlspecialchars( $_GET['evil'] ) => 'the good value',
		$_GET['evil'] => 'the evil value'
	]
];

$unsafe9 = [
	'type' => 'multiselect',
	'options' => [
		htmlspecialchars( $_GET['evil'] ) => 'the good value',
		$_GET['evil'] => 'the evil value'
	]
];

$unsafe10 = [
	'class' => 'HTMLMultiSelectField',
	'options' => [
		htmlspecialchars( $_GET['evil'] ) => 'the good value',
		$_GET['evil'] => 'the evil value'
	]
];
