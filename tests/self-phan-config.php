<?php

use Phan\Config;

return [
	'file_list' => [
		Config::projectPath( 'GenericSecurityCheckPlugin.php' ),
		Config::projectPath( 'MediaWikiSecurityCheckPlugin.php' ),
	],
	'directory_list' => [
		Config::projectPath( 'src' ),
		Config::projectPath( 'vendor' )
	],
	'exclude_file_regex' => '@vendor/(?!phan|symfony/(polyfill|$))@',
	"exclude_analysis_directory_list" => [
		Config::projectPath( 'vendor' )
	],
	'null_casts_as_any_type' => false,
	'scalar_implicit_cast' => false,
	'dead_code_detection' => true,
	'processes' => 1,
	'suppress_issue_types' => [
		// As noted in phan's own cfg file: "The types of ast\Node->children are all possibly unset"
		'PhanTypePossiblyInvalidDimOffset',
		// Used for function taintedness
		'PhanPluginMixedKeyNoKey',
	],
	'plugins' => [
		'AlwaysReturnPlugin',
		'AddNeverReturnTypePlugin',
		'DuplicateArrayKeyPlugin',
		'DuplicateExpressionPlugin',
		'LoopVariableReusePlugin',
		'PhanSelfCheckPlugin',
		'PHPDocRedundantPlugin',
		'PHPDocToRealTypesPlugin',
		'RedundantAssignmentPlugin',
		'RemoveDebugStatementPlugin',
		'SimplifyExpressionPlugin',
		'StrictComparisonPlugin',
		'StrictLiteralComparisonPlugin',
		'UnknownElementTypePlugin',
		'UnreachableCodePlugin',
		'UnusedSuppressionPlugin',
		'UseReturnValuePlugin',
	],
	'redundant_condition_detection' => true,
	'warn_about_undocumented_throw_statements' => true,
	'warn_about_undocumented_exceptions_thrown_by_invoked_functions' => true,
	'exception_classes_with_optional_throws_phpdoc' => [
		// Taken from phan's own config.php
		'LogicException',
		'RuntimeException',
		'AssertionError',
		'Phan\Exception\IssueException',
		// We create a few known FQSENs manually in MW mode.
		'Phan\Exception\FQSENException',
	],
];
