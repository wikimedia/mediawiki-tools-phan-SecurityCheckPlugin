<?php
/*
 * Copyright Brian Wolff 2017. Released under the GPL version 2 or later.
 */
require_once "SecurityCheckPlugin.php";

class MediaWikiSecurityCheckPlugin extends SecurityCheckPlugin {

	protected function getCustomFuncTaints() : array {
		return [
			// '\Message::__construct' => SecurityCheckPlugin::YES_TAINT,
			// '\wfMessage' => SecurityCheckPlugin::YES_TAINT,
			'\Message::plain' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\Message::text' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\Message::parseAsBlock' => [ 'overall' => SecurityCheckPlugin::NO_TAINT, ],
			'\Message::parse' => [ 'overall' => SecurityCheckPlugin::NO_TAINT, ],
			'\Message::escaped' => [ 'overall' => SecurityCheckPlugin::NO_TAINT, ],
			'\Message::rawParams' => [
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				// meh, not sure how right the overall is.
				'overall' => SecurityCheckPlugin::HTML_TAINT
			],
			// FIXME Doesn't handle array args right.
			'\wfShellExec' => [
				SecurityCheckPlugin::SHELL_EXEC_TAINT,
				'overall' => self::YES_TAINT
			],
			'\wfShellExecWithStderr' => [
				SecurityCheckPlugin::SHELL_EXEC_TAINT,
				'overall' => self::YES_TAINT
			],
			'\wfEscapeShellArg' => [
				self::YES_TAINT & ~self::SHELL_TAINT,
				self::YES_TAINT & ~self::SHELL_TAINT,
				self::YES_TAINT & ~self::SHELL_TAINT,
				self::YES_TAINT & ~self::SHELL_TAINT,
				self::YES_TAINT & ~self::SHELL_TAINT,
				self::YES_TAINT & ~self::SHELL_TAINT,
				self::YES_TAINT & ~self::SHELL_TAINT,
				self::YES_TAINT & ~self::SHELL_TAINT,
				self::YES_TAINT & ~self::SHELL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Html::rawElement' => [
				SecurityCheckPlugin::HTML_TAINT,
				SecurityCheckPlugin::NO_TAINT,
				SecurityCheckPlugin::HTML_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT
			],
			'\Html::element' => [
				SecurityCheckPlugin::HTML_TAINT,
				SecurityCheckPlugin::NO_TAINT,
				SecurityCheckPlugin::NO_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT
			],
			'\Xml::tags' => [
				SecurityCheckPlugin::HTML_TAINT,
				SecurityCheckPlugin::NO_TAINT,
				SecurityCheckPlugin::HTML_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT
			],
			'\Xml::element' => [
				SecurityCheckPlugin::HTML_TAINT,
				SecurityCheckPlugin::NO_TAINT,
				SecurityCheckPlugin::NO_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT
			],
			'\OutputPage::addHeadItem' => [
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\OutputPage::addHTML' => [
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT,
			],
			'\OutputPage::prependHTML' => [
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT,
			],
			'\OutputPage::addInlineStyle' => [
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT,
			],
			'\OutputPage::parse' => [ 'overall' => SecurityCheckPlugin::NO_TAINT, ],
			'\Parser::parse' => [
				self::YES_TAINT & ~self::HTML_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT,
			],
			'\WebRequest::getGPCVal' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getRawVal' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getVal' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getArray' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getIntArray' => [ 'overall' => SecurityCheckPlugin::NO_TAINT, ],
			'\WebRequest::getInt' => [ 'overall' => SecurityCheckPlugin::NO_TAINT, ],
			'\WebRequest::getIntOrNull' => [ 'overall' => SecurityCheckPlugin::NO_TAINT, ],
			'\WebRequest::getFloat' => [ 'overall' => SecurityCheckPlugin::NO_TAINT, ],
			'\WebRequest::getBool' => [ 'overall' => SecurityCheckPlugin::NO_TAINT, ],
			'\WebRequest::getFuzzyBool' => [ 'overall' => SecurityCheckPlugin::NO_TAINT, ],
			'\WebRequest::getCheck' => [ 'overall' => SecurityCheckPlugin::NO_TAINT, ],
			'\WebRequest::getText' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getValues' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getValueNames' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getQueryValues' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getRawQueryString' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getRawPostString' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getRawInput' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getCookie' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getGlobalRequestURL' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getRequestURL' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getFullRequestURL' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getAllHeaders' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getHeader' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'\WebRequest::getAcceptLang' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
			'OOUI\HtmlSnippet::__construct' => [
				self::HTML_EXEC_TAINT & self::YES_TAINT,
				'overall' => self::NO_TAINT
			],
			'OOUI\FieldLayout::__construct' => [
				'overall' => self::NO_TAINT
			],
			'OOUI\TextInputWidget::__construct' => [
				'overall' => self::NO_TAINT
			],
			'OOUI\ButtonInputWidget::__construct' => [
				'overall' => self::NO_TAINT
			],
			'HtmlArmor::__construct' => [
				self::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
		];
	}

}

return new MediaWikiSecurityCheckPlugin;
