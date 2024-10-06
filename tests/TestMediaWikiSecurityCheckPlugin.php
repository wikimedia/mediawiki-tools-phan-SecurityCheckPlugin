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

		$insertTaint = new FunctionTaintedness( Taintedness::newSafe() );
		// table name
		$insertTaint->setParamSinkTaint( 0, new Taintedness( self::SQL_EXEC_TAINT ) );
		$insertTaint->addParamFlags( 0, self::NO_OVERRIDE );
		// Insert values. The keys names are unsafe. The argument can be either a single row or an array of rows.
		// Note, here we are assuming the single row case. The multiple rows case is handled in modifyParamSinkTaint.
		$sqlExecKeysTaint = Taintedness::safeSingleton()->withAddedKeysTaintedness( self::SQL_EXEC_TAINT );
		$insertTaint->setParamSinkTaint( 1, $sqlExecKeysTaint );
		$insertTaint->addParamFlags( 1, self::NO_OVERRIDE );
		// method name
		$insertTaint->setParamSinkTaint( 2, new Taintedness( self::SQL_EXEC_TAINT ) );
		$insertTaint->addParamFlags( 2, self::NO_OVERRIDE );
		// options. They are not escaped
		$insertTaint->setParamSinkTaint( 3, new Taintedness( self::SQL_EXEC_TAINT ) );
		$insertTaint->addParamFlags( 3, self::NO_OVERRIDE );

		$insertQBRowTaint = new FunctionTaintedness( Taintedness::newSafe() );
		$insertQBRowTaint->setParamSinkTaint( 0, clone $sqlExecKeysTaint );
		$insertQBRowTaint->addParamFlags( 0, self::NO_OVERRIDE );

		$insertQBRowsTaint = new FunctionTaintedness( Taintedness::newSafe() );
		$multiRowsTaint = Taintedness::safeSingleton()->withAddedOffsetTaintedness( null, clone $sqlExecKeysTaint );
		$insertQBRowsTaint->setParamSinkTaint( 0, $multiRowsTaint );
		$insertQBRowsTaint->addParamFlags( 0, self::NO_OVERRIDE );

		$htmlExecKeysTaint = Taintedness::safeSingleton()->withAddedKeysTaintedness( self::HTML_EXEC_TAINT );

		$sinkKeysTaint = new FunctionTaintedness( Taintedness::newSafe() );
		$sinkKeysTaint->setParamSinkTaint( 0, $htmlExecKeysTaint );
		$sinkKeysTaint->addParamFlags( 0, self::NO_OVERRIDE );

		$sinkKeysOfUnknownDimTaint = new FunctionTaintedness( Taintedness::newSafe() );
		$htmlExecKeysOfUnknownTaint = Taintedness::safeSingleton()
			->withAddedOffsetTaintedness( null, clone $htmlExecKeysTaint );
		$sinkKeysOfUnknownDimTaint->setParamSinkTaint( 0, $htmlExecKeysOfUnknownTaint );
		$sinkKeysOfUnknownDimTaint->addParamFlags( 0, self::NO_OVERRIDE );

		return [
			'\Wikimedia\Rdbms\Database::query' => [
				self::SQL_EXEC_TAINT,
				'overall' => self::YES_TAINT
			],
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
			'\Message::plain' => [ 'overall' => self::YES_TAINT ],
			'\Message::text' => [ 'overall' => self::YES_TAINT ],
			'\Message::parseAsBlock' => [ 'overall' => self::ESCAPED_TAINT ],
			'\Message::parse' => [ 'overall' => self::ESCAPED_TAINT ],
			'\Message::__toString' => [ 'overall' => self::ESCAPED_TAINT ],
			'\Message::escaped' => [ 'overall' => self::ESCAPED_TAINT ],
			'\Message::rawParams' => [
				self::HTML_EXEC_TAINT | self::VARIADIC_PARAM,
				'overall' => self::HTML_TAINT
			],
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
		];
	}
}

return new TestMediaWikiSecurityCheckPlugin;
