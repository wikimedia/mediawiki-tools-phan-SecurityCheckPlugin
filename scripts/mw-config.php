<?php

$baseCfg = require __DIR__ . '/base-config.php';

/**
 * This is based on MW's phan config.php.
 */
$coreCfg = [
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

	// A list of plugin files to execute
	'plugins' => [
		__DIR__ . '/../MediaWikiSecurityCheckPlugin.php',
	],
];

return $coreCfg + $baseCfg;
