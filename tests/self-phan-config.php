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
	// Taint-check heavily sets dynamic properties on phan objects
	'allow_missing_properties' => true,
	'null_casts_as_any_type' => false,
	'scalar_implicit_cast' => false,
	'dead_code_detection' => true,
	'processes' => 1,
	'suppress_issue_types' => [
		// As noted in phan's own cfg file: "The types of ast\Node->children are all possibly unset"
		'PhanTypePossiblyInvalidDimOffset',
		// Used for function taintedness
		'PhanPluginMixedKeyNoKey'
	],
	'plugins' => [
		'UnusedSuppressionPlugin',
		'DuplicateExpressionPlugin',
		'UnknownElementTypePlugin',
		'LoopVariableReusePlugin',
		'RedundantAssignmentPlugin',
		'StrictLiteralComparisonPlugin',
		'PhanSelfCheckPlugin',
		'UnreachableCodePlugin',
		'DuplicateArrayKeyPlugin',
		'StrictComparisonPlugin',
		'SimplifyExpressionPlugin',
		'RemoveDebugStatementPlugin'
	],
	'redundant_condition_detection' => true
];
