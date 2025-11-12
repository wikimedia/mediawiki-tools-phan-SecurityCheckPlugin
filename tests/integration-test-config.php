<?php

use Phan\Config;

return [
	'progress_bar' => false,
	'whitelist_issue_types' => [
		'SecurityCheck-XSS',
		'SecurityCheck-SQLInjection',
		'SecurityCheck-ShellInjection',
		'SecurityCheck-PHPSerializeInjection',
		'SecurityCheck-DoubleEscaped',
		'SecurityCheck-CUSTOM1',
		'SecurityCheck-CUSTOM2',
		'SecurityCheck-RCE',
		'SecurityCheck-PathTraversal',
		'SecurityCheck-ReDoS',
		'SecurityCheck-LikelyFalsePositive',
		'SecurityCheckDebugTaintedness',
		'SecurityCheckInvalidAnnotation',
		// These are to make testing easier
		'PhanDebugAnnotation',
		// Uncomment when needed, cannot be left in place because it might be emitted
		// for a given PHP version but not for others.
		// 'PhanSyntaxError',
	],
	// Used by the 'htmlform' test
	'enable_class_alias_support' => true,
	'plugins' => [
		Config::projectPath( 'TestMediaWikiSecurityCheckPlugin.php' )
	],
];
