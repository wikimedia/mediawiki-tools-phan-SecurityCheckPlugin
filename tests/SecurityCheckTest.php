<?php

use Phan\CLIBuilder;
use Phan\Output\Printer\PlainTextPrinter;
use Phan\Phan;
use SecurityCheckPlugin\MediaWikiHooksHelper;
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
		'SecurityCheckPlugin' => [
			'pluginInstance'
		]
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
	 * @return string|null
	 */
	private function runPhan( string $folderName, string $cfgFile ) : ?string {
		putenv( "SECURITY_CHECK_EXT_PATH=" . __DIR__ . "/$folderName" );
		$codeBase = require __DIR__ . '/../vendor/phan/phan/src/codebase.php';
		$cliBuilder = new CLIBuilder();
		$cliBuilder->setOption( 'project-root-directory', __DIR__ );
		$cliBuilder->setOption( 'config-file', "./$cfgFile" );
		$cliBuilder->setOption( 'directory', "./$folderName" );
		$cliBuilder->setOption( 'no-progress-bar', true );
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
		$res = $this->runPhan( "integration/$name", 'integration-test-config.php' );
		$this->assertEquals( $expected, $res );
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
	 * @param string $name Test name, and name of the folder
	 * @param string $expected Expected seccheck output for the directory
	 * @dataProvider providePhanInteractionTests
	 */
	public function testPhanInteraction( string $name, string $expected ) {
		$res = $this->runPhan( "phan-interaction/$name", 'phan-interaction-test-config.php' );
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
}
