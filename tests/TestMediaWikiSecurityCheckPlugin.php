<?php

use SecurityCheckPlugin\FunctionTaintedness;
use SecurityCheckPlugin\Taintedness;

require_once __DIR__ . '/../MediaWikiSecurityCheckPlugin.php';

/**
 * "Fake" plugin used for integration tests.
 * @todo Make this not extend MediaWikiSecurityCheckPlugin and use it only to test
 * getCustomFuncTaints(), moving MW-specific tests to MediaWiki core.
 */
class TestMediaWikiSecurityCheckPlugin extends MediaWikiSecurityCheckPlugin {
	protected function getCustomFuncTaints(): array {
		$selectWrapper = [
			self::SQL_EXEC_TAINT,
			self::SQL_EXEC_TAINT,
			self::SQL_NUMKEY_EXEC_TAINT,
			self::SQL_EXEC_TAINT,
			self::NO_TAINT,
			self::NO_TAINT,
			'overall' => self::YES_TAINT
		];

		$sqlExecTaint = new Taintedness( self::SQL_EXEC_TAINT );
		$insertTaint = FunctionTaintedness::emptySingleton();
		// table name
		$insertTaint = $insertTaint->withParamSinkTaint( 0, $sqlExecTaint, self::NO_OVERRIDE );
		// Insert values. The keys names are unsafe. The argument can be either a single row or an array of rows.
		// Note, here we are assuming the single row case. The multiple rows case is handled in modifyParamSinkTaint.
		$sqlExecKeysTaint = Taintedness::newFromShape( [], null, self::SQL_EXEC_TAINT );
		$insertTaint = $insertTaint->withParamSinkTaint( 1, $sqlExecKeysTaint, self::NO_OVERRIDE );
		// method name
		$insertTaint = $insertTaint->withParamSinkTaint( 2, $sqlExecTaint, self::NO_OVERRIDE );
		// options. They are not escaped
		$insertTaint = $insertTaint->withParamSinkTaint( 3, $sqlExecTaint, self::NO_OVERRIDE );

		$insertQBRowTaint = FunctionTaintedness::emptySingleton();
		$insertQBRowTaint = $insertQBRowTaint->withParamSinkTaint( 0, clone $sqlExecKeysTaint, self::NO_OVERRIDE );

		$insertQBRowsTaint = FunctionTaintedness::emptySingleton();
		$multiRowsTaint = Taintedness::newFromShape( [], clone $sqlExecKeysTaint );
		$insertQBRowsTaint = $insertQBRowsTaint->withParamSinkTaint( 0, $multiRowsTaint, self::NO_OVERRIDE );

		$htmlExecKeysTaint = Taintedness::newFromShape( [], null, self::HTML_EXEC_TAINT );

		$sinkKeysTaint = FunctionTaintedness::emptySingleton();
		$sinkKeysTaint = $sinkKeysTaint->withParamSinkTaint( 0, $htmlExecKeysTaint, self::NO_OVERRIDE );

		$sinkKeysOfUnknownDimTaint = FunctionTaintedness::emptySingleton();
		$htmlExecKeysOfUnknownTaint = Taintedness::newFromShape( [], clone $htmlExecKeysTaint );
		$sinkKeysOfUnknownDimTaint = $sinkKeysOfUnknownDimTaint
			->withParamSinkTaint( 0, $htmlExecKeysOfUnknownTaint, self::NO_OVERRIDE );

		return [
			'\Wikimedia\Rdbms\Database::query' => [
				self::SQL_EXEC_TAINT,
				'overall' => self::YES_TAINT
			],
			'\Wikimedia\Rdbms\IReadableDatabase::select' => $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::select' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::select' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectSQLText' => [
					'overall' => self::YES_TAINT & ~self::SQL_TAINT
				] + $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectRow' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::insert' => $insertTaint,
			'\Wikimedia\Rdbms\IDatabase::makeList' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\InsertQueryBuilder::row' => $insertQBRowTaint,
			'\Wikimedia\Rdbms\InsertQueryBuilder::rows' => $insertQBRowsTaint,
			'\Html::rawElement' => [
				self::YES_TAINT,
				self::ESCAPES_HTML,
				self::YES_TAINT,
				'overall' => self::ESCAPED_TAINT
			],
			'\Html::element' => [
				self::YES_TAINT,
				self::ESCAPES_HTML,
				self::ESCAPES_HTML,
				'overall' => self::ESCAPED_TAINT
			],
			'\MediaWiki\Message\Message::text' => [ 'overall' => self::YES_TAINT ],
			'\MediaWiki\Message\Message::parse' => [ 'overall' => self::ESCAPED_TAINT ],
			'\MediaWiki\Message\Message::__toString' => [ 'overall' => self::ESCAPED_TAINT ],
			'\HtmlArmor::__construct' => [
				self::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\StripState::addItem' => [
				self::NO_TAINT,
				self::NO_TAINT,
				self::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\wfShellExec' => [
				self::SHELL_EXEC_TAINT | self::ARRAY_OK,
				'overall' => self::YES_TAINT
			],

			// Misc testing stuff
			'\TestSinkShape::sinkKeys' => $sinkKeysTaint,
			'\TestSinkShape::sinkAll' => [
				self::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\TestSinkShape::sinkKeysOfUnknown' => $sinkKeysOfUnknownDimTaint,

			'\HardcodedEscapedToString::__toString' => [ 'overall' => self::ESCAPED_TAINT ],
			'\HardcodedVariadicExec::doTest' => [
				self::HTML_EXEC_TAINT | self::VARIADIC_PARAM,
				'overall' => self::NO_TAINT
			],
		];
	}
}

return new TestMediaWikiSecurityCheckPlugin;
