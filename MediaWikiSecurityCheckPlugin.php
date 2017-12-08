<?php
/**
 * Static analysis tool for MediaWiki extensions.
 *
 * To use, add this file to your phan plugins list.
 *
 * Copyright (C) 2017  Brian Wolff <bawolff@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */
require_once __DIR__ . "/src/SecurityCheckPlugin.php";
require_once __DIR__ . "/src/MWVisitor.php";
require_once __DIR__ . "/src/MWPreVisitor.php";

use Phan\CodeBase;
use Phan\Config;
use Phan\Language\Context;
use Phan\Language\FQSEN\FullyQualifiedFunctionLikeName;
use Phan\Language\FQSEN\FullyQualifiedClassName;
use Phan\Language\FQSEN\FullyQualifiedFunctionName as FQSENFunc;
use Phan\Language\FQSEN\FullyQualifiedMethodName as FQSENMethod;
use ast\Node;

class MediaWikiSecurityCheckPlugin extends SecurityCheckPlugin {

	/**
	 * @var Array A mapping from hook names to FQSEN that implement it
	 */
	protected $hookSubscribers = [];

	/**
	 * Override so we can check for hook registration
	 *
	 * @param CodeBase $code_base
	 * @param Context $context
	 * @param Node $node
	 * @param Node $parentNode
	 */
	public function analyzeNode(
		CodeBase $code_base,
		Context $context,
		Node $node,
		Node $parentNode = null
	) {
		parent::analyzeNode( $code_base, $context, $node, $parentNode );

		$visitor = new MWVisitor( $code_base, $context, $this );
		$visitor( $node );
	}

	/**
	 * Called on every node in the ast, but in pre-order
	 *
	 * @param CodeBase $code_base
	 * @param Context $context
	 * @param Node $node
	 */
	public function preAnalyzeNode( CodeBase $code_base, Context $context, Node $node ) {
		parent::preAnalyzeNode( $code_base, $context, $node );
		( new MWPreVisitor( $code_base, $context, $this ) )( $node );
	}

	/**
	 * @inheritDoc
	 */
	protected function getCustomFuncTaints() : array {
		$selectWrapper = [
				self::SQL_EXEC_TAINT,
				// List of fields. MW does not escape things like COUNT(*)
				self::SQL_EXEC_TAINT,
				// Where conditions
				self::SQL_NUMKEY_EXEC_TAINT,
				// the function name doesn't seem to be escaped
				self::SQL_EXEC_TAINT,
				// OPTIONS. Its complicated. HAVING is like WHERE
				// This is treated as special case
				self::NO_TAINT,
				// Join conditions. This is treated as special case
				self::NO_TAINT,
				// What should DB results be considered?
				'overall' => self::YES_TAINT
		];

		return [
			// Note, at the moment, this checks where the function
			// is implemented, so you can't use IDatabase.
			'\Wikimedia\Rdbms\Database::query' => [
				self::SQL_EXEC_TAINT,
				// What should DB results be considered?
				'overall' => self::YES_TAINT
			],
			'\Wikimedia\Rdbms\IDatabase::query' => [
				self::SQL_EXEC_TAINT,
				// What should DB results be considered?
				'overall' => self::YES_TAINT
			],
			'\Wikimedia\Rdbms\IMaintainableDatabase::query' => [
				self::SQL_EXEC_TAINT,
				// What should DB results be considered?
				'overall' => self::YES_TAINT
			],
			'\Wikimedia\Rdbms\DBConnRef::query' => [
				self::SQL_EXEC_TAINT,
				// What should DB results be considered?
				'overall' => self::YES_TAINT
			],
			'\Wikimedia\Rdbms\IDatabase::select' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::select' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::select' => $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::select' => $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::selectField' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::selectField' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectField' => $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::selectField' => $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::selectFieldValues' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::selectFieldValues' => $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::selectFieldValues' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectFieldValues' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::selectSQLText' => [
					'overall' => self::YES_TAINT & ~self::SQL_TAINT
				] + $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::selectSQLText' => [
					'overall' => self::YES_TAINT & ~self::SQL_TAINT
				] + $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::selectSQLText' => [
					'overall' => self::YES_TAINT & ~self::SQL_TAINT
				] + $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectSQLText' => [
					'overall' => self::YES_TAINT & ~self::SQL_TAINT
				] + $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::selectRowCount' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::selectRowCount' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectRowCount' => $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::selectRowCount' => $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::selectRow' => $selectWrapper,
			'\Wikimedia\Rdbms\IMaintainableDatabase::selectRow' => $selectWrapper,
			'\Wikimedia\Rdbms\Database::selectRow' => $selectWrapper,
			'\Wikimedia\Rdbms\DBConnRef::selectRow' => $selectWrapper,
			'\Wikimedia\Rdbms\IDatabase::delete' => [
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IMaintainableDatabase::delete' => [
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\Database::delete' => [
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DBConnRef::delete' => [
				self::SQL_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IDatabase::insert' => [
				self::SQL_EXEC_TAINT, // table name
				// FIXME This doesn't correctly work
				// when inserting multiple things at once.
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT, // method name
				self::SQL_EXEC_TAINT, // options. They are not escaped
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IMaintainableDatabase::insert' => [
				self::SQL_EXEC_TAINT, // table name
				// FIXME This doesn't correctly work
				// when inserting multiple things at once.
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT, // method name
				self::SQL_EXEC_TAINT, // options. They are not escaped
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\Database::insert' => [
				self::SQL_EXEC_TAINT, // table name
				// Insert values. The keys names are unsafe.
				// Unclear how well this works for the multi case.
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT, // method name
				self::SQL_EXEC_TAINT, // options. They are not escaped
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DBConnRef::insert' => [
				self::SQL_EXEC_TAINT, // table name
				// Insert values. The keys names are unsafe.
				// Unclear how well this works for the multi case.
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT, // method name
				self::SQL_EXEC_TAINT, // options. They are not escaped
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IDatabase::update' => [
				self::SQL_EXEC_TAINT, // table name
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT, // method name
				self::NO_TAINT, // options. They are validated
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\IMaintainableDatabase::update' => [
				self::SQL_EXEC_TAINT, // table name
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT, // method name
				self::NO_TAINT, // options. They are validated
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\Database::update' => [
				self::SQL_EXEC_TAINT, // table name
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT, // method name
				self::NO_TAINT, // options. They are validated
				'overall' => self::NO_TAINT
			],
			'\Wikimedia\Rdbms\DBConnRef::update' => [
				self::SQL_EXEC_TAINT, // table name
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_NUMKEY_EXEC_TAINT,
				self::SQL_EXEC_TAINT, // method name
				self::NO_TAINT, // options. They are validated
				'overall' => self::NO_TAINT
			],
			// This is subpar, as addIdentifierQuotes isn't always
			// the right type of escaping.
			'\Wikimedia\Rdbms\Database::addIdentifierQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\DatabaseMysqlBase::addIdentifierQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\DatabaseMssql::addIdentifierQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\IDatabase::addIdentifierQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\DBConnRef::addIdentifierQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\Database::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\DBConnRef::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\DatabaseMysqlBase::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\DatabaseMssql::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\IDatabase::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\IMaintainableDatabase::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\DatabasePostgres::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\Wikimedia\Rdbms\DatabaseSqlite::addQuotes' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT,
			],
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
			// AddItem should also take care of addGeneral and friends.
			'\StripState::addItem' => [
				self::NO_TAINT, // type
				self::NO_TAINT, // marker
				self::HTML_EXEC_TAINT, // contents
				'overall' => self::NO_TAINT
			],
			// FIXME Doesn't handle array args right.
			'\wfShellExec' => [
				self::SHELL_EXEC_TAINT | self::ARRAY_OK,
				'overall' => self::YES_TAINT
			],
			'\wfShellExecWithStderr' => [
				self::SHELL_EXEC_TAINT | self::ARRAY_OK,
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
			'MediaWiki\Shell\Shell::escape' => [
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
			'MediaWiki\Shell\Command::unsafeParams' => [
				self::SHELL_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			'MediaWiki\Shell\Result::getStdout' => [
				// This is a bit unclear. Most of the time
				// you should probably be escaping the results
				// of a shell command, but not all the time.
				'overall' => self::YES_TAINT
			],
			'MediaWiki\Shell\Result::getStderr' => [
				// This is a bit unclear. Most of the time
				// you should probably be escaping the results
				// of a shell command, but not all the time.
				'overall' => self::YES_TAINT
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
			'\Xml::encodeJsVar' => [
				self::YES_TAINT & ~self::HTML_TAINT,
				self::NO_TAINT, /* pretty */
				'overall' => self::NO_TAINT
			],
			'\Xml::encodeJsCall' => [
				self::YES_TAINT, /* func name. unescaped */
				self::YES_TAINT & ~self::HTML_TAINT, /* args */
				self::NO_TAINT, /* pretty */
				'overall' => self::NO_TAINT
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
			'\Parser::recursiveTagParse' => [
				self::YES_TAINT & ~self::HTML_TAINT,
				self::NO_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT,
			],
			'\Parser::recursiveTagParseFully' => [
				self::YES_TAINT & ~self::HTML_TAINT,
				self::NO_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT,
			],
			'\ParserOutput::getText' => [
				self::NO_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT,
			],
			'\Sanitizer::removeHTMLtags' => [
				self::YES_TAINT & ~self::HTML_TAINT, /* text */
				self::SHELL_EXEC_TAINT, /* attribute callback */
				self::NO_TAINT, /* callback args */
				self::YES_TAINT, /* extra tags */
				self::NO_TAINT, /* remove tags */
				'overall' => SecurityCheckPlugin::NO_TAINT
			],
			'\Sanitizer::escapeHtmlAllowEntities' => [
				self::YES_TAINT & ~self::HTML_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT
			],
			'\Sanitizer::safeEncodeAttribute' => [
				self::YES_TAINT & ~self::HTML_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT
			],
			'\Sanitizer::encodeAttribute' => [
				self::YES_TAINT & ~self::HTML_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT
			],
			'\WebRequest::getGPCVal' => [ 'overall' => SecurityCheckPlugin::YES_TAINT, ],
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
			'OOUI\HorizontalLayout::__construct' => [
				'overall' => self::NO_TAINT
			],
			'HtmlArmor::__construct' => [
				self::HTML_EXEC_TAINT,
				'overall' => self::NO_TAINT
			],
			// Due to limitations in how we handle list()
			// elements, hard code CommentStore stuff.
			'CommentStore::insert' => [
				'overall' => self::NO_TAINT
			],
			'CommentStore::getJoin' => [
				'overall' => self::NO_TAINT
			],
			'CommentStore::insertWithTempTable' => [
				'overall' => self::NO_TAINT
			],
			// TODO FIXME, Why couldn't it figure out
			// that this is safe on its own?
			// It seems that it has issue with
			// the url query parameters.
			'Linker::linkKnown' => [
				self::NO_TAINT, /* target */
				self::YES_TAINT, /* raw html text */
				// The array keys for this aren't escaped (!)
				self::NO_TAINT, /* customAttribs */
				self::NO_TAINT, /* query */
				self::NO_TAINT, /* options. All are safe */
				'overall' => self::NO_TAINT
			],
			'MediaWiki\Linker\LinkRenderer::buildAElement' => [
				self::NO_TAINT, /* target */
				self::NO_TAINT, /* text (using HtmlArmor) */
				// The array keys for this aren't escaped (!)
				self::NO_TAINT, /* attribs */
				self::NO_TAINT, /* known */
				'overall' => self::NO_TAINT
			],
			'MediaWiki\Linker\LinkRenderer::makeLink' => [
				self::NO_TAINT, /* target */
				self::NO_TAINT, /* text (using HtmlArmor) */
				// The array keys for this aren't escaped (!)
				self::NO_TAINT, /* attribs */
				self::NO_TAINT, /* query */
				'overall' => self::NO_TAINT
			],
		];
	}

	/**
	 * Add a hook implementation to our list.
	 *
	 * This also handles parser hooks which aren't normal hooks.
	 * Non-normal hooks start their name with a "!"
	 *
	 * @param string $hookName Name of hook
	 * @param FullyQualifiedFunctionLikeName $fqsen The implementing method
	 * @return bool true if already registered, false otherwise
	 */
	public function registerHook( string $hookName, FullyQualifiedFunctionLikeName $fqsen ) {
		if ( !isset( $this->hookSubscribers[$hookName] ) ) {
			$this->hookSubscribers[$hookName] = [];
		}
		foreach ( $this->hookSubscribers[$hookName] as $subscribe ) {
			if ( (string)$subscribe === (string)$fqsen ) {
				// dupe
				return true;
			}
		}
		$this->hookSubscribers[$hookName][] = $fqsen;
		return false;
	}

	/**
	 * Register hooks from extension.json
	 *
	 * Assumes extension.json is in project root directory
	 * unless SECURITY_CHECK_EXT_PATH is set
	 */
	protected function loadExtensionJson() {
		static $done;
		if ( $done ) {
			return;
		}
		$done = true;
		$envPath = getenv( 'SECURITY_CHECK_EXT_PATH' );
		if ( $envPath ) {
			$jsonPath = $envPath . '/' . 'extension.json';
		} else {
			$jsonPath = Config::projectPath( 'extension.json' );
		}
		if ( file_exists( $jsonPath ) ) {
			$json = json_decode( file_get_contents( $jsonPath ), true );
			if ( !is_array( $json ) ) {
				return;
			}
			if ( isset( $json['Hooks'] ) && is_array( $json['Hooks'] ) ) {
				foreach ( $json['Hooks'] as $hookName => $cbList ) {
					foreach ( (array)$cbList as $cb ) {
						// All callbacks here are simple
						// "someFunction" or "Class::SomeMethod"
						if ( strpos( $cb, '::' ) === false ) {
							$callback = FQSENFunc::fromFullyQualifiedString(
								$cb
							);
						} else {
							$callback = FQSENMethod::fromFullyQualifiedString(
								$cb
							);
						}
						$this->registerHook( $hookName, $callback );
					}
				}
			}
		}
	}
	/**
	 * Get a list of subscribers for hook
	 *
	 * @param string $hookName Hook in question. Hooks starting with ! are special.
	 * @return FullyQualifiedFunctionLikeName[]
	 */
	public function getHookSubscribers( string $hookName ) : array {
		$this->loadExtensionJson();
		if ( isset( $this->hookSubscribers[$hookName] ) ) {
			return $this->hookSubscribers[$hookName];
		}
		return [];
	}

	/**
	 * Is a particular function implementing a special hook.
	 *
	 * @note This assumes that any given func will only implement
	 *   one hook
	 * @param FullyQualifiedFunctionLikeName $fqsen The function to check
	 * @return string The hook it is implementing
	 */
	public function isSpecialHookSubscriber( FullyQualifiedFunctionLikeName $fqsen ) {
		$this->loadExtensionJson();
		$specialHooks = [
			'!ParserFunctionHook',
			'!ParserHook'
		];

		// @todo This is probably not the most efficient thing.
		foreach ( $specialHooks as $hook ) {
			if ( !isset( $this->hookSubscribers[$hook] ) ) {
				continue;
			}
			foreach ( $this->hookSubscribers[$hook] as $implFQSEN ) {
				if ( (string)$implFQSEN === (string)$fqsen ) {
					return $hook;
				}
			}
		}
	}

	/**
	 * Mark XSS's that happen in a Maintinance subclass as false a positive
	 *
	 * @param int $lhsTaint The dangerous taints to be output (e.g. LHS of assignment)
	 * @param int $rhsTaint The taint of the expression
	 * @param string &$msg The issue description
	 * @param Context $context
	 * @param CodeBase $code_base
	 * @return bool Is this a false positive?
	 */
	public function isFalsePositive(
		int $lhsTaint,
		int $rhsTaint,
		string &$msg,
		Context $context,
		CodeBase $code_base
	) : bool {
		if (
			( $lhsTaint & $rhsTaint ) === self::HTML_TAINT &&
			$context->isInClassScope()
		) {
			$class = $context->getClassInScope( $code_base );
			$maintFQSEN = FullyQualifiedClassName::fromFullyQualifiedString(
				'\\Maintenance'
			);
			if ( !$code_base->hasClassWithFQSEN( $maintFQSEN ) ) {
				return false;
			}
			$maint = $code_base->getClassByFQSEN( $maintFQSEN );
			$isMaint = $class->isSubclassOf( $code_base, $maint );
			if ( $isMaint ) {
				$msg .= ' [Likely false positive because in a subclass ' .
					'of Maintenance, thus probably CLI]';
				return true;
			}
		}
		return false;
	}

}

return new MediaWikiSecurityCheckPlugin;
