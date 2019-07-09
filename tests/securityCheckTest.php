<?php

/**
 * Regression tests to check that the plugin keeps working as intended
 * phpcs:disable MediaWiki.Commenting.MissingCovers.MissingCovers
 * phpcs:disable MediaWiki.Usage.ForbiddenFunctions.shell_exec
 * @fixme Make this work in CI!!!!!
 */
class SecurityCheckTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @param string $dirPath Path to the directory to analyze
	 * @param string $expected Expected seccheck output for the directory
	 * @dataProvider provideScenarios
	 */
	public function testScenarios( $dirPath, $expected ) {
		putenv( "SECURITY_CHECK_EXT_PATH=$dirPath" );
		$here = __DIR__;
		$cmd = "php $here/../vendor/phan/phan/phan" .
			" --project-root-directory \"$here\"" .
			" --config-file \"$here/integration-test-config.php\"" .
			" -l \"$dirPath\"";

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
			$expected = glob( $folder . '/*.txt' )[0];
			yield basename( $folder ) => [ $folder, file_get_contents( $expected ) ];
		}
	}
}
