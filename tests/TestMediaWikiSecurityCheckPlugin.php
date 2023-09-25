<?php

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
			'\Wikimedia\Rdbms\Database::insert' => [
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT,
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IDatabase::makeList' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				self::NO_TAINT,
				'overall' => self::NO_TAINT
			],
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
		];
	}
}

return new TestMediaWikiSecurityCheckPlugin;
