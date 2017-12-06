<?php

// If xdebug is enabled, we need to increase the nesting level for phan
ini_set( 'xdebug.max_nesting_level', 1000 );

/**
 * This is based on MW's phan config.php.
 */
return [
	/**
	 * A list of individual files to include in analysis
	 * with a path relative to the root directory of the
	 * project. directory_list won't find .inc files so
	 * we augment it here.
	 */
	'file_list' => array_merge(
		function_exists( 'register_postsend_function' ) ? [] : [ 'tests/phan/stubs/hhvm.php' ],
		function_exists( 'wikidiff2_do_diff' ) ? [] : [ 'tests/phan/stubs/wikidiff.php' ],
		function_exists( 'tideways_enable' ) ? [] : [ 'tests/phan/stubs/tideways.php' ],
		class_exists( PEAR::class ) ? [] : [ 'tests/phan/stubs/mail.php' ],
		class_exists( Memcached::class ) ? [] : [ 'tests/phan/stubs/memcached.php' ],
		[
			'maintenance/7zip.inc',
			'maintenance/backup.inc',
			'maintenance/backupPrefetch.inc',
			'maintenance/cleanupTable.inc',
			'maintenance/CodeCleanerGlobalsPass.inc',
			'maintenance/commandLine.inc',
			'maintenance/importImages.inc',
			'maintenance/sqlite.inc',
			'maintenance/userDupes.inc',
			'maintenance/userOptions.inc',
			'maintenance/language/checkLanguage.inc',
			'maintenance/language/languages.inc',
		]
	),

	'directory_list' => [
		'includes/',
		'languages/',
		'maintenance/',
		'mw-config/',
		'resources/',
		'skins/',
		'vendor/',
	],

	/**
	 * A file list that defines files that will be excluded
	 * from parsing and analysis and will not be read at all.
	 *
	 * This is useful for excluding hopelessly unanalyzable
	 * files that can't be removed for whatever reason.
	 */
	'exclude_file_list' => [],

	/**
	 * A list of directories holding code that we want
	 * to parse, but not analyze. Also works for individual
	 * files.
	 */
	"exclude_analysis_directory_list" => [
		'vendor/',
		'tests/phan/stubs/',
		// The referenced classes are not available in vendor, only when
		// included from composer.
		'includes/composer/',
		'maintenance/language/',
		'includes/libs/jsminplus.php',
		'skins/',
	],

	/**
	 * Backwards Compatibility Checking. This is slow
	 * and expensive, but you should consider running
	 * it before upgrading your version of PHP to a
	 * new version that has backward compatibility
	 * breaks.
	 */
	'backward_compatibility_checks' => false,

	/**
	 * A set of fully qualified class-names for which
	 * a call to parent::__construct() is required
	 */
	'parent_constructor_required' => [
	],

	'quick_mode' => false,

	'should_visit_all_nodes' => true,

	'analyze_signature_compatibility' => false,

	/**
	 * Do not emit false positives
	 */
	"minimum_severity" => 1,
	'allow_missing_properties' => false,
	'null_casts_as_any_type' => true,
	'scalar_implicit_cast' => true,
	'ignore_undeclared_variables_in_global_scope' => true,
	'dead_code_detection' => false,
	'dead_code_detection_prefer_false_negative' => true,
	'read_type_annotations' => true,
	'disable_suppression' => false,
	'dump_ast' => false,
	'dump_signatures_file' => null,
	'expand_file_list' => false,
	// Include a progress bar in the output
	'progress_bar' => true,
	'progress_bar_sample_rate' => 0.005,

	/**
	 * The number of processes to fork off during the analysis
	 * phase.
	 */
	'processes' => 1,

	/** We use the whitelist instead */
	'suppress_issue_types' => [],

	/**
	 * If empty, no filter against issues types will be applied.
	 * If this white-list is non-empty, only issues within the list
	 * will be emitted by Phan.
	 */
	'whitelist_issue_types' => [
		'SecurityCheckMulti',
		'SecurityCheck-XSS',
		'SecurityCheck-SQLInjection',
		'SecurityCheck-ShellInjection',
		'SecurityCheck-CUSTOM1',
		'SecurityCheck-CUSTOM2',
		'SecurityCheck-OTHER',
		// Rely on severity setting to blacklist false positive.
		'SecurityCheck-LikelyFalsePositive',
	],

	/**
	 * Override to hardcode existence and types of (non-builtin) globals in the global scope.
	 * Class names must be prefixed with '\\'.
	 * (E.g. ['_FOO' => '\\FooClass', 'page' => '\\PageClass', 'userId' => 'int'])
	 */
	'globals_type_map' => [
		// 'IP' => 'string',
	],

	// Emit issue messages with markdown formatting
	'markdown_issue_messages' => false,

	/**
	 * Enable or disable support for generic templated
	 * class types.
	 */
	'generic_types_enabled' => true,

	// A list of plugin files to execute
	'plugins' => [
		__DIR__ . '/../MediaWikiSecurityCheckPlugin.php',
	],
];
