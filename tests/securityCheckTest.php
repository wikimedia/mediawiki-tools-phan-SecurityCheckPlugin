<?php

/**
 * Regression tests to check that the plugin keeps working as intended
 * phpcs:disable MediaWiki.Commenting.MissingCovers.MissingCovers
 * phpcs:disable MediaWiki.Usage.ForbiddenFunctions.shell_exec
 */
class SecurityCheckTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @param string $name Test name, and name of the folder
	 * @param string $expected Expected seccheck output for the directory
	 * @dataProvider provideScenarios
	 */
	public function testScenarios( $name, $expected ) {
		if ( $name === 'msg2' ) {
			// The hardcoded overall for Message::(parse|escaped) makes the plugin
			// treat the message as escaped, even if rawParams was called
			$this->markTestSkipped( 'FIXME make pass and re-enable' );
		}
		// Ensure that we're in the main project folder
		chdir( __DIR__ . '/../' );
		putenv( "SECURITY_CHECK_EXT_PATH=" . __DIR__ . "/integration/$name" );
		$cmd = "php vendor/phan/phan/phan" .
			" --project-root-directory \"tests/\"" .
			" --config-file \"integration-test-config.php\"" .
			" -l \"integration/$name\"";

		$res = shell_exec( $cmd );
		$this->assertEquals( $expected, $res );
	}

	/**
	 * Data provider for testScenarios
	 *
	 * @return Generator
	 */
	public function provideScenarios() {
		$iterator = new DirectoryIterator( __DIR__ . '/integration' );

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
}
