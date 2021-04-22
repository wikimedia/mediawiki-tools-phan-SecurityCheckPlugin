<?php

use Phan\CLIBuilder;
use Phan\Output\Printer\PlainTextPrinter;
use Phan\Phan;
use SecurityCheckPlugin\MediaWikiHooksHelper;
use SecurityCheckPlugin\SecurityCheckPlugin;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Regression tests to check that the plugin keeps working as intended
 * phpcs:disable MediaWiki.Commenting.MissingCovers.MissingCovers
 */
class SecurityCheckTest extends \PHPUnit\Framework\TestCase {
	/**
	 * Taken from phan's BaseTest class
	 * @inheritDoc
	 */
	protected $backupStaticAttributesBlacklist = [
		'Phan\Language\Type' => [
			'canonical_object_map',
			'internal_fn_cache',
		],
		'Phan\Language\Type\LiteralFloatType' => [
			'nullable_float_type',
			'non_nullable_float_type',
		],
		'Phan\Language\Type\LiteralIntType' => [
			'nullable_int_type',
			'non_nullable_int_type',
		],
		'Phan\Language\Type\LiteralStringType' => [
			'nullable_string_type',
			'non_nullable_string_type',
		],
		'Phan\Language\UnionType' => [
			'empty_instance',
		],
		'SecurityCheckPlugin\SecurityCheckPlugin' => [
			'pluginInstance'
		]
	];

	private const TESTS_WITH_MINIMUM_PHP_VERSION = [
		'arrowfunc' => 70400,
		'assignop' => 70400,
		'constructorpromotion' => 80000,
		'match' => 80000,
		'namedargs' => 80000,
		'nullsafemethod' => 80000,
		'nullsafeprop' => 80000,
		'typedprops' => 70400,
	];

	/**
	 * @inheritDoc
	 */
	public function tearDown() : void {
		MediaWikiHooksHelper::getInstance()->clearCache();
	}

	/**
	 * @param string $folderName
	 * @param string $cfgFile
	 * @param bool $usePolyfill Whether to force the polyfill parser
	 * @return string|null
	 */
	private function runPhan( string $folderName, string $cfgFile, bool $usePolyfill = false ) : ?string {
		putenv( "SECURITY_CHECK_EXT_PATH=" . __DIR__ . "/$folderName" );
		// Useful when debugging weird test failures
		// putenv( 'SECCHECK_DEBUG=-' );
		$codeBase = require __DIR__ . '/../vendor/phan/phan/src/codebase.php';
		$cliBuilder = new CLIBuilder();
		$cliBuilder->setOption( 'project-root-directory', __DIR__ );
		$cliBuilder->setOption( 'config-file', "./$cfgFile" );
		$cliBuilder->setOption( 'directory', "./$folderName" );
		$cliBuilder->setOption( 'no-progress-bar', true );
		if ( $usePolyfill ) {
			$cliBuilder->setOption( 'force-polyfill-parser', true );
		}
		$cli = $cliBuilder->build();

		$stream = new BufferedOutput();
		$printer = new PlainTextPrinter();
		$printer->configureOutput( $stream );
		Phan::setPrinter( $printer );

		Phan::analyzeFileList( $codeBase, function () use ( $cli ) {
			return $cli->getFileList();
		} );

		return $stream->fetch();
	}

	/**
	 * @param string $folder
	 * @return Generator
	 */
	private function extractTestCases( string $folder ) {
		$iterator = new DirectoryIterator( __DIR__ . "/$folder" );

		foreach ( $iterator as $dir ) {
			if ( $dir->isDot() ) {
				continue;
			}
			$folder = $dir->getPathname();
			$testName = basename( $folder );
			$expected = file_get_contents( $folder . '/expectedResults.txt' );

			yield $testName => [ $testName, $expected ];
		}
	}

	/**
	 * @param string $name Test name, and name of the folder
	 * @param string $expected Expected seccheck output for the directory
	 * @dataProvider provideIntegrationTests
	 */
	public function testIntegration( $name, $expected ) {
		$this->checkSkipTest( $name );
		$res = $this->runPhan( "integration/$name", 'integration-test-config.php' );
		$this->assertEquals( $expected, $res );
	}

	/**
	 * @param string $name Test name, and name of the folder
	 * @param string $expected Expected seccheck output for the directory
	 * @dataProvider provideIntegrationTests
	 */
	public function testIntegration_Polyfill( $name, $expected ) {
		if ( $name === 'namedargs' ) {
			$this->markTestSkipped( 'Analyzing named arguments with the polyfill parser is not yet supported' );
		}
		$this->checkSkipTest( $name );
		$res = $this->runPhan( "integration/$name", 'integration-test-config.php', true );
		$this->assertEquals( $expected, $res );
	}

	/**
	 * @param string $testName
	 */
	private function checkSkipTest( string $testName ) : void {
		if ( isset( self::TESTS_WITH_MINIMUM_PHP_VERSION[$testName] ) ) {
			$version = self::TESTS_WITH_MINIMUM_PHP_VERSION[$testName];
			if ( PHP_VERSION_ID < $version ) {
				$this->markTestSkipped( "This test requires PHP >= $version" );
			}
		}
	}

	/**
	 * Data provider for testIntegration
	 *
	 * @return Generator
	 */
	public function provideIntegrationTests() {
		return $this->extractTestCases( 'integration' );
	}

	/**
	 * @todo Temporary method until numkey propagation is fixed
	 * @param string $name Test name, and name of the folder
	 * @param string $expected Expected seccheck output for the directory
	 * @dataProvider provideNumkeyTests
	 */
	public function testNumkey( $name, $expected ) {
		putenv( 'SECCHECK_NUMKEY_SPERIMENTAL=1' );
		$res = $this->runPhan( "numkey/$name", 'integration-test-config.php' );
		$this->assertEquals( $expected, $res );
	}

	public function provideNumkeyTests() : Generator {
		return $this->extractTestCases( 'numkey' );
	}

	/**
	 * @param string $name Test name, and name of the folder
	 * @param string $expected Expected seccheck output for the directory
	 * @dataProvider providePhanInteractionTests
	 */
	public function testPhanInteraction( string $name, string $expected ) {
		$res = $this->runPhan( "phan-interaction/$name", 'phan-interaction-test-config.php' );
		$this->assertEquals( $expected, $res );
	}

	/**
	 * @param string $name Test name, and name of the folder
	 * @param string $expected Expected seccheck output for the directory
	 * @dataProvider providePhanInteractionTests
	 */
	public function testPhanInteraction_Polyfill( string $name, string $expected ) {
		$res = $this->runPhan( "phan-interaction/$name", 'phan-interaction-test-config.php', true );
		$this->assertEquals( $expected, $res );
	}

	/**
	 * Data provider for testPhanInteraction
	 *
	 * @return Generator
	 */
	public function providePhanInteractionTests() {
		return $this->extractTestCases( 'phan-interaction' );
	}

	/**
	 * Ensure that SecurityCheckPlugin::ALL_TAINT_FLAGS really has all possible flags
	 */
	public function testAllTaintFlagsReallyHasAllFlags() {
		$cl = new ReflectionClass( SecurityCheckPlugin::class );
		$excludedConsts = [ 'ALL_TAINT_FLAGS' ];
		$actual = 0;
		foreach ( $cl->getConstants() as $name => $val ) {
			if ( is_int( $val ) && !in_array( $name, $excludedConsts, true ) ) {
				$actual |= $val;
			}
		}
		$this->assertSame(
			SecurityCheckPlugin::ALL_TAINT_FLAGS,
			$actual,
			'ALL_TAINT_FLAGS should include all flags'
		);
	}

	/**
	 * Ensure that any two SecurityCheckPlugin::*_TAINT constants have different values
	 */
	public function testTaintValuesAreDifferent() {
		$cl = new ReflectionClass( SecurityCheckPlugin::class );
		$constants = [];
		foreach ( $cl->getConstants() as $name => $val ) {
			if ( substr( $name, -strlen( '_TAINT' ) ) === '_TAINT' ) {
				$constants[$name] = $val;
			}
		}
		foreach ( $constants as $name => $value ) {
			$firstOcc = array_search( $value, $constants );
			$this->assertSame( $name, $firstOcc, 'Same taint value is used twice' );
		}
	}
}
