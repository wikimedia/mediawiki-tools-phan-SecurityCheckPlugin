<?php

$baseCfg = require __DIR__ . '/base-config.php';

$IP = getenv( 'MW_INSTALL_PATH' ) !== false
	// Replace \\ by / for windows users to let exclude work correctly
	? str_replace( '\\', '/', getenv( 'MW_INSTALL_PATH' ) )
	: '../../';

/**
 * This is based on MW's phan config.php.
 */
$MWExtConfig = [
	/**
	 * A list of individual files to include in analysis
	 * with a path relative to the root directory of the
	 * project. directory_list won't find .inc files so
	 * we augment it here.
	 */
	'file_list' => array_merge(
		function_exists( 'register_postsend_function' ) ? [] : [ $IP . 'tests/phan/stubs/hhvm.php' ],
		function_exists( 'wikidiff2_do_diff' ) ? [] : [ $IP . 'tests/phan/stubs/wikidiff.php' ],
		function_exists( 'tideways_enable' ) ? [] : [ $IP . 'tests/phan/stubs/tideways.php' ],
		class_exists( PEAR::class ) ? [] : [ $IP . 'tests/phan/stubs/mail.php' ],
		class_exists( Memcached::class ) ? [] : [ $IP . 'tests/phan/stubs/memcached.php' ],
		[
			$IP . 'maintenance/7zip.inc',
			$IP . 'maintenance/backup.inc',
			$IP . 'maintenance/backupPrefetch.inc',
			$IP . 'maintenance/cleanupTable.inc',
			$IP . 'maintenance/CodeCleanerGlobalsPass.inc',
			$IP . 'maintenance/commandLine.inc',
			$IP . 'maintenance/importImages.inc',
			$IP . 'maintenance/sqlite.inc',
			$IP . 'maintenance/userDupes.inc',
			$IP . 'maintenance/userOptions.inc',
			$IP . 'maintenance/language/checkLanguage.inc',
			$IP . 'maintenance/language/languages.inc',
		]
	),

	'directory_list' => [
		$IP . 'includes/',
		$IP . 'languages/',
		$IP . 'maintenance/',
		$IP . 'mw-config/',
		$IP . 'resources/',
		$IP . 'skins/',
		$IP . 'vendor/',
		'.'
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
		$IP . 'vendor/',
		$IP . 'tests/phan/stubs/',
		// The referenced classes are not available in vendor, only when
		// included from composer.
		$IP . 'includes/composer/',
		$IP . 'maintenance/language/',
		$IP . 'includes/libs/jsminplus.php',
		'vendor'
	],

	// A list of plugin files to execute
	'plugins' => [
		__DIR__ . '/../MediaWikiSecurityCheckPlugin.php',
	],
];

unset( $IP );
return $MWExtConfig + $baseCfg;
