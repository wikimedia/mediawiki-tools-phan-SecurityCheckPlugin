<?php

use Phan\AST\Parser;
use Phan\AST\Visitor\Element;
use Phan\CLIBuilder;
use Phan\CodeBase;
use Phan\Language\Type;
use Phan\Output\Printer\PlainTextPrinter;
use Phan\Phan;
use Phan\Plugin\ConfigPluginSet;
use SecurityCheckPlugin\MediaWikiHooksHelper;
use SecurityCheckPlugin\SecurityCheckPlugin;
use SecurityCheckPlugin\TaintednessVisitor;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Regression tests to check that the plugin keeps working as intended
 * @coversNothing
 */
class SecurityCheckTest extends \PHPUnit\Framework\TestCase {
	private ?CodeBase $codeBase = null;

	/**
	 * Taken from phan's BaseTest class
	 * @inheritDoc
	 */
	protected $backupStaticAttributesExcludeList = [
		'Phan\AST\PhanAnnotationAdder' => [
			'closures_for_kind',
		],
		'Phan\AST\ASTReverter' => [
			'closure_map',
			'noop',
		],
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
		],
		ConfigPluginSet::class => [
			'mergeVariableInfoClosure'
		],
	];

	private const TESTS_WITH_MINIMUM_PHP_VERSION = [
		'arrowfunc' => 70400,
		'assignop' => 70400,
		'constructorpromotion' => 80000,
		'exit-84' => 80400,
		'match' => 80000,
		'namedargs' => 80000,
		'nullsafemethod' => 80000,
		'nullsafeprop' => 80000,
		'throwexpression' => 80000,
		'typedprops' => 70400,
	];

	private const TESTS_SKIPPED_WITH_MINIMUM_PHP_VERSION = [
		'exit-pre84' => 80400,
	];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		gc_disable();
	}

	/**
	 * Copied from phan's {@see \Phan\Tests\CodeBaseAwareTest}
	 */
	public function setUp(): void {
		static $code_base = null;
		if ( !$code_base ) {
			global $internal_class_name_list;
			global $internal_interface_name_list;
			global $internal_trait_name_list;
			global $internal_function_name_list;
			if ( !isset( $internal_class_name_list ) ) {
				require_once __DIR__ . '/../vendor/phan/phan/src/codebase.php';
			}

			$code_base = new CodeBase(
				$internal_class_name_list,
				$internal_interface_name_list,
				$internal_trait_name_list,
				CodeBase::getPHPInternalConstantNameList(),
				$internal_function_name_list
			);
		}

		Type::clearAllMemoizations();
		$this->codeBase = $code_base->shallowClone();
	}

	/**
	 * @inheritDoc
	 */
	public function tearDown(): void {
		MediaWikiHooksHelper::getInstance()->clearCache();
	}

	/**
	 * @param string $folderName
	 * @param string $cfgFile
	 * @param bool $usePolyfill Whether to force the polyfill parser
	 * @param bool $analyzeTwice
	 * @return string|null
	 */
	private function runPhan(
		string $folderName,
		string $cfgFile,
		bool $usePolyfill = false,
		bool $analyzeTwice = false
	): ?string {
		if ( !$usePolyfill && !extension_loaded( 'ast' ) ) {
			$this->markTestSkipped( 'This test requires PHP extension \'ast\' loaded' );
		}
		putenv( "SECURITY_CHECK_EXT_PATH=" . __DIR__ . "/$folderName" );
		// Useful when debugging weird test failures
		// putenv( 'SECCHECK_DEBUG=-' );
		$cliBuilder = new CLIBuilder();
		$cliBuilder->setOption( 'project-root-directory', __DIR__ );
		$cliBuilder->setOption( 'config-file', "./$cfgFile" );
		$cliBuilder->setOption( 'directory', "./$folderName" );
		$cliBuilder->setOption( 'no-progress-bar', true );
		if ( $usePolyfill ) {
			$cliBuilder->setOption( 'force-polyfill-parser', true );
		}
		if ( $analyzeTwice ) {
			$cliBuilder->setOption( 'analyze-twice', true );
		}
		$cli = $cliBuilder->build();

		$stream = new BufferedOutput();
		$printer = new PlainTextPrinter();
		$printer->configureOutput( $stream );
		Phan::setPrinter( $printer );

		Phan::analyzeFileList( $this->codeBase, static function () use ( $cli ) {
			// Replace \\ by / for windows machine
			return str_replace( '\\', '/', $cli->getFileList() );
		} );

		// Do a "\r\n" -> "\n" and "\r" -> "\n" transformation for windows machine
		return str_replace( [ "\r\n", "\r" ], "\n", $stream->fetch() );
	}

	/**
	 * @param string $folder
	 * @return Generator
	 */
	private static function extractTestCases( string $folder ) {
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
		if ( $name === 'namedargs' || $name === 'exit-84' ) {
			$this->markTestSkipped( 'Analyzing named arguments with the polyfill parser is not yet supported' );
		}
		$this->checkSkipTest( $name );
		$res = $this->runPhan( "integration/$name", 'integration-test-config.php', true );
		$this->assertEquals( $expected, $res );
	}

	/**
	 * @param string $name Test name, and name of the folder
	 * @param string $expected Expected seccheck output for the directory
	 * @dataProvider provideAnalyzeTwiceTests
	 */
	public function testAnalyzeTwice( $name, $expected ) {
		$this->checkSkipTest( $name );
		// Note: $expected is from the analyze-twice dir, but the source files are in integration/
		$res = $this->runPhan( "integration/$name", 'integration-test-config.php', false, true );
		$this->assertEquals( $expected, $res );
	}

	/**
	 * @param string $name Test name, and name of the folder
	 * @param string $expected Expected seccheck output for the directory
	 * @dataProvider provideAnalyzeTwiceTests
	 */
	public function testAnalyzeTwice_Polyfill( $name, $expected ) {
		$this->checkSkipTest( $name );
		// Note: $expected is from the analyze-twice dir, but the source files are in integration/
		$res = $this->runPhan( "integration/$name", 'integration-test-config.php', true, true );
		$this->assertEquals( $expected, $res );
	}

	/**
	 * @param string $testName
	 */
	private function checkSkipTest( string $testName ): void {
		if ( isset( self::TESTS_WITH_MINIMUM_PHP_VERSION[$testName] ) ) {
			$version = self::TESTS_WITH_MINIMUM_PHP_VERSION[$testName];
			if ( PHP_VERSION_ID < $version ) {
				$this->markTestSkipped( "This test requires PHP >= $version" );
			}
		}
		if ( isset( self::TESTS_SKIPPED_WITH_MINIMUM_PHP_VERSION[$testName] ) ) {
			$version = self::TESTS_SKIPPED_WITH_MINIMUM_PHP_VERSION[$testName];
			if ( PHP_VERSION_ID >= $version ) {
				$this->markTestSkipped( "This test requires PHP < $version" );
			}
		}
	}

	/**
	 * Data provider for testIntegration
	 *
	 * @return Generator
	 */
	public static function provideIntegrationTests() {
		return self::extractTestCases( 'integration' );
	}

	/**
	 * @return Generator
	 */
	public static function provideAnalyzeTwiceTests() {
		return self::extractTestCases( 'analyze-twice' );
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

	/**
	 * @todo Temporary method until numkey propagation is fixed
	 * @param string $name Test name, and name of the folder
	 * @param string $expected Expected seccheck output for the directory
	 * @dataProvider provideNumkeyTests
	 */
	public function testNumkey_Polyfill( $name, $expected ) {
		putenv( 'SECCHECK_NUMKEY_SPERIMENTAL=1' );
		$res = $this->runPhan( "numkey/$name", 'integration-test-config.php', true );
		$this->assertEquals( $expected, $res );
	}

	public static function provideNumkeyTests(): Generator {
		return self::extractTestCases( 'numkey' );
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
	public static function providePhanInteractionTests() {
		return self::extractTestCases( 'phan-interaction' );
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

	/**
	 * @dataProvider provideInapplicableNodesWithoutVisitor
	 */
	public function testInapplicableNodesWithoutVisitor( int $kind ): void {
		$methodName = Element::VISIT_LOOKUP_TABLE[$kind];
		$reflMethod = new ReflectionMethod( TaintednessVisitor::class, $methodName );
		$this->assertNotEquals( TaintednessVisitor::class, $reflMethod->class );
	}

	public static function provideInapplicableNodesWithoutVisitor(): Generator {
		foreach ( TaintednessVisitor::INAPPLICABLE_NODES_WITHOUT_VISITOR as $kind => $_ ) {
			yield Parser::getKindName( $kind ) => [ $kind ];
		}
	}

	/**
	 * @dataProvider provideInapplicableNodesWithVisitor
	 */
	public function testInapplicableNodesWithVisitor( int $kind ): void {
		$methodName = Element::VISIT_LOOKUP_TABLE[$kind];
		$reflMethod = new ReflectionMethod( TaintednessVisitor::class, $methodName );
		$this->assertSame( TaintednessVisitor::class, $reflMethod->class );
	}

	public static function provideInapplicableNodesWithVisitor(): Generator {
		foreach ( TaintednessVisitor::INAPPLICABLE_NODES_WITH_VISITOR as $kind => $_ ) {
			yield Parser::getKindName( $kind ) => [ $kind ];
		}
	}
}
