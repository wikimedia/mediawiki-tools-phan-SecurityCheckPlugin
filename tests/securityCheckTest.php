<?php

/**
 * Regression tests to check that the plugin keeps working as intended
 * phpcs:disable MediaWiki.Commenting.MissingCovers.MissingCovers
 * phpcs:disable MediaWiki.Usage.ForbiddenFunctions.shell_exec
 */
class SecurityCheckTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @param string $folderName
	 * @param string $cfgFile
	 * @return string|null
	 */
	private function runPhan( string $folderName, string $cfgFile ) : ?string {
		// Ensure that we're in the main project folder
		chdir( __DIR__ . '/../' );
		putenv( "SECURITY_CHECK_EXT_PATH=" . __DIR__ . "/$folderName" );
		$cmd = "php vendor/phan/phan/phan" .
			" --project-root-directory \"tests/\"" .
			" --config-file \"$cfgFile\"" .
			" -l \"$folderName\"" .
			' --no-progress-bar';

		return shell_exec( $cmd );
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
