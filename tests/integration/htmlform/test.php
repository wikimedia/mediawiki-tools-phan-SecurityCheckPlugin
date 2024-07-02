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

$unsafe4a = [
	'class' => 'MediaWiki\HTMLForm\Field\HTMLCheckField',
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

$unsafe5a = [
	'class' => 'MediaWiki\HTMLForm\Field\HTMLInfoField',
	'default' => $_GET['evil'],
	'raw' => true,
];

$unsafe6 = [
	'class' => HTMLInfoField::class,
	'default' => $_GET['evil'],
	'raw' => true,
];

$unsafe6a = [
	'class' => MediaWiki\HTMLForm\Field\HTMLInfoField::class,
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

$unsafe8a = [
	'class' => MediaWiki\HTMLForm\Field\HTMLRadioField::class,
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

$unsafe10a = [
	'class' => 'MediaWiki\HTMLForm\Field\HTMLMultiSelectField',
	'options' => [
		htmlspecialchars( $_GET['evil'] ) => 'the good value',
		$_GET['evil'] => 'the evil value'
	]
];

$evilOptions = [
	htmlspecialchars( $_GET['evil'] ) => 'the good value',
	$_GET['evil'] => 'the evil value'
];

$unsafe11 = [
	'class' => HTMLRadioField::class,
	'options' => $evilOptions
];

$unsafe11a = [
	'class' => MediaWiki\HTMLForm\Field\HTMLRadioField::class,
	'options' => $evilOptions
];

$safe10 = [
	'class' => HTMLRadioField::class,
	'options' => [
		htmlspecialchars( $_GET['evil'] ) => 'the good value',
		'foo' => $_GET['ok']
	]
];

$safe10a = [
	'class' => MediaWiki\HTMLForm\Field\HTMLRadioField::class,
	'options' => [
		htmlspecialchars( $_GET['evil'] ) => 'the good value',
		'foo' => $_GET['ok']
	]
];

$unsafe12 = [
	'type' => 'info',
	'rawrow' => true,
	'default' => $_GET['evil']
];

$fieldName = 'radio';
$unsafe13 = [
	'type' => $fieldName, // Unsafe
	'options' => $_GET['x']
];

$typeKey = 'type';
$optionsKey = 'options';
$unsafe13 = [
	$typeKey => $fieldName, // Unsafe
	$optionsKey => $_GET['x']
];

$unsafe14 = [
	'type' => 'text',
	'label-message' => 'some-field-msg',
	'help' => $_GET['x'] // Unsafe
];