<?php

use Phan\Language\Context;
use Phan\Language\FQSEN\FullyQualifiedMethodName;
use Phan\Language\FQSEN\FullyQualifiedFunctionName;
use Phan\Language\FQSEN\FullyQualifiedFunctionLikeName;
use Phan\Language\FQSEN;
use Phan\Language\Element\Method;
use Phan\Language\Type\CallableType;
use Phan\Plugin;
use Phan\CodeBase;
use ast\Node;

/**
 * MediaWiki specific node visitor
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
 */
class MWVisitor extends TaintednessBaseVisitor {

	/**
	 * Constructor to enforce plugin is instance of MediaWikiSecurityCheckPlugin
	 *
	 * @param CodeBase $code_base
	 * @param Context $context
	 * @param MediaWikiSecurityCheckPlugin $plugin
	 */
	public function __construct(
		CodeBase $code_base,
		Context $context,
		MediaWikiSecurityCheckPlugin $plugin
	) {
		parent::__construct( $code_base, $context, $plugin );
		// Ensure phan knows plugin is right type
		$this->plugin = $plugin;
	}

	/**
	 * @param Node $node
	 */
	public function visit( Node $node ) {
	}

	/**
	 * Check static calls for hook registration
	 *
	 * Forwards to method call handler.
	 * @param Node $node
	 */
	public function visitStaticCall( Node $node ) {
		$this->visitMethodCall( $node );
	}

	/**
	 * Try and recognize hook registration
	 *
	 * Also handles static calls
	 * @param Node $node
	 */
	public function visitMethodCall( Node $node ) {
		try {
			$ctx = $this->getCtxN( $node );
			$methodName = $node->children['method'];
			$method = $ctx->getMethod(
				$methodName,
				$node->kind === \ast\AST_STATIC_CALL
			);
			// Should this be getDefiningFQSEN() instead?
			$methodName = (string)$method->getFQSEN();
			// $this->debug( __METHOD__, "Checking to see if we should register $methodName" );
			switch ( $methodName ) {
				case '\Parser::setFunctionHook':
				case '\Parser::setHook':
				case '\Parser::setTransparentTagHook':
					$type = $this->getHookTypeForRegistrationMethod( $methodName );
					// $this->debug( __METHOD__, "registering $methodName as $type" );
					$this->handleParserHookRegistration( $node, $type );
					break;
				case '\Hooks::register':
					$this->handleNormalHookRegistration( $node );
					break;
				case '\Hooks::run':
					$this->triggerHook( $node );
					break;
				default:
					$this->doSelectWrapperSpecialHandling( $node, $method );
			}
		} catch ( Exception $e ) {
			// ignore
		}
	}

	/**
	 * Special casing for complex format of IDatabase::select
	 *
	 * This handled the $options, and $join_cond. Other args are
	 * handled through normal means
	 *
	 * @param Node $node Either an AST_METHOD_CALL or AST_STATIC_CALL
	 * @param Method $method
	 */
	private function doSelectWrapperSpecialHandling( Node $node, Method $method ) {
		$clazz = $method->getClass( $this->code_base );

		$implementIDB = false;
		do {
			$interfaceList = $clazz->getInterfaceFQSENList();
			foreach ( $interfaceList as $interface ) {
				if ( (string)$interface === '\\Wikimedia\\Rdbms\\IDatabase' ) {
					$implementIDB = true;
					break 2;
				}
			}
		// @codingStandardsIgnoreStart MediaWiki.ControlStructures.AssignmentInControlStructures.AssignmentInControlStructures
		} while (
			$clazz->hasParentType() &&
			( $clazz = $clazz->getParentClass( $this->code_base ) )
		);
		// @codingStandardsIgnoreEnd

		if ( !$implementIDB ) {
			return;
		}

		$relevantMethods = [
			'select',
			'selectField',
			'selectFieldValues',
			'selectSQLText',
			'selectRowCount',
			'selectRow'
		];

		if ( !in_array( $method->getName(), $relevantMethods ) ) {
			return;
		}

		$args = $node->children['args']->children;
		if ( isset( $args[4] ) ) {
			$this->checkSQLOptions( $args[4] );
		}
		if ( isset( $args[5] ) ) {
			$this->checkJoinCond( $args[5] );
		}
	}

	/**
	 * Dispatch a hook (i.e. Handle Hooks::run)
	 *
	 * @param Node $node The Hooks::run AST_STATIC_CALL
	 */
	private function triggerHook( Node $node ) {
		$args = [];
		$argList = $node->children['args']->children;
		if ( count( $argList ) === 0 ) {
			$this->debug( __METHOD__, "Too few args to Hooks::run" );
			return;
		}
		if ( !is_string( $argList[0] ) ) {
			$this->debug( __METHOD__, "Cannot determine hook name" );
			return;
		}
		$hookName = $argList[0];
		if (
			count( $argList ) < 2
			|| $argList[1]->kind !== \ast\AST_ARRAY
		) {
			// @todo There are definitely cases where this
			// will prevent us from running hooks
			// e.g. EditPageGetPreviewContent
			$this->debug( __METHOD__, "Could not run hook $hookName due to complex args" );
			return;
		}
		foreach ( $argList[1]->children as $arg ) {
			if ( $arg->children['key'] !== null ) {
				$this->debug( __METHOD__, "named arg in hook $hookName?" );
				continue;
			}
			$args[] = $arg;
		}

		$subscribers = $this->plugin->getHookSubscribers( $hookName );
		foreach ( $subscribers as $subscriber ) {
			if ( $subscriber instanceof FullyQualifiedMethodName ) {
				$func = $this->code_base->getMethodByFQSEN( $subscriber );
			} else {
				assert( $subscriber instanceof FullyQualifiedFunctionName );
				$func = $this->code_base->getFunctionByFQSEN( $subscriber );
			}
			$taint = $this->getTaintOfFunction( $func );
			// $this->debug( __METHOD__, "Dispatching $hookName to $subscriber" );
			// This is hacky, but try to ensure that the associated line
			// number for any issues is in the extension, and not the
			// line where the Hooks::register() is in MW core.
			// FIXME: In the case of reference parameters, this is
			// still reporting things being in MW core instead of extension.
			$oldContext = $this->overrideContext;
			$fContext = $func->getContext();
			$newContext = clone $this->context;
			$newContext = $newContext->withFile( $fContext->getFile() )
				->withLineNumberStart( $fContext->getLineNumberStart() );
			$this->overrideContext = $newContext;

			$this->handleMethodCall( $func, $subscriber, $taint, $args );

			$this->overrideContext = $oldContext;
		}
	}

	/**
	 * @param string $method The method name of the registration function
	 * @return string The name of the hook that gets registered
	 */
	private function getHookTypeForRegistrationMethod( string $method ) {
		switch ( $method ) {
		case '\Parser::setFunctionHook':
			return '!ParserFunctionHook';
		case '\Parser::setHook':
		case '\Parser::setTransparentTagHook':
			return '!ParserHook';
		default:
			throw new Exception( "$method not a hook registerer" );
		}
	}

	/**
	 * Handle registering a normal hook from Hooks::register (Not from $wgHooks)
	 *
	 * @param Node $node The node representing the AST_STATIC_CALL
	 */
	private function handleNormalHookRegistration( Node $node ) {
		assert( $node->kind === \ast\AST_STATIC_CALL );
		$params = $node->children['args']->children;
		if ( count( $params ) < 2 ) {
			$this->debug( __METHOD__, "Could not understand Hooks::register" );
			return;
		}
		$hookName = $params[0];
		if ( !is_string( $params[0] ) ) {
			$this->debug( __METHOD__, "Could not register hook. Name is complex" );
			return;
		}
		$cb = $this->getCallableFromHookRegistration( $params[1], $hookName );
		if ( $cb ) {
			$this->registerHook( $hookName, $cb );
		} else {
			$this->debug( __METHOD__, "Could not register $hookName hook due to complex callback" );
		}
	}

	/**
	 * When someone calls $parser->setFunctionHook() or setTagHook()
	 *
	 * @note Causes phan to error out if given non-existent class
	 * @param Node $node The AST_METHOD_CALL node
	 * @param string $hookType The name of the hook
	 */
	private function handleParserHookRegistration( Node $node, string $hookType ) {
		$args = $node->children['args']->children;
		if ( count( $args ) < 2 ) {
			return;
		}
		$callback = $this->getFQSENFromCallable( $args[1] );
		if ( $callback ) {
			$this->registerHook( $hookType, $callback );
		}
	}

	private function registerHook( string $hookType, FullyQualifiedFunctionLikeName $callback ) {
		$alreadyRegistered = $this->plugin->registerHook( $hookType, $callback );
		$this->debug( __METHOD__, "registering $callback for hook $hookType" );
		if ( !$alreadyRegistered ) {
			// If this is the first time seeing this, re-analyze the
			// node, just in case we had already passed it by.
			$func = null;
			if ( $callback->isClosure() ) {
				// For closures we have to reanalyze the parent
				// function, as we can't reanalyze the closure, and
				// we definitely need to since the closure would
				// have already been analyzed at this point since
				// we are operating in post-order.
				if ( $this->context->isInFunctionLikeScope() ) {
					$func = $this->context->getFunctionLikeInScope( $this->code_base );
				}
			} elseif ( $callback instanceof FullyQualifiedMethodName ) {
				$func = $this->code_base->getMethodByFQSEN( $callback );
			} else {
				assert( $callback instanceof FullyQualifiedFunctionName );
				$func = $this->code_base->getFunctionByFQSEN( $callback );
			}
			// Make sure we reanalyze the hook function now that
			// we know what it is, in case its already been
			// analyzed.
			if ( $func ) {
				$func->analyze(
					$func->getContext(),
					$this->code_base
				);
			}
		}
	}

	/**
	 * For special hooks, check their return value
	 *
	 * e.g. A tag hook's return value is output as html.
	 * @param Node $node
	 */
	public function visitReturn( Node $node ) {
		if (
			!$this->context->isInFunctionLikeScope()
			|| !$node->children['expr'] instanceof Node
		) {
			return;
		}
		$funcFQSEN = $this->context->getFunctionLikeFQSEN();

		if ( strpos( (string)$funcFQSEN, '::getQueryInfo' ) !== false ) {
			$this->handleGetQueryInfoReturn( $node->children['expr'] );
		}

		$hookType = $this->plugin->isSpecialHookSubscriber( $funcFQSEN );
		switch ( $hookType ) {
		case '!ParserFunctionHook':
			$this->visitReturnOfFunctionHook( $node->children['expr'], $funcFQSEN );
			break;
		case '!ParserHook':
			$ret = $node->children['expr'];
			$taintedness = $this->getTaintedness( $ret );
			$this->maybeEmitIssue(
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				$taintedness,
				"Outputting user controlled HTML from Parser tag hook $funcFQSEN"
					. $this->getOriginalTaintLine( $ret )
			);
			break;
		}
	}

	/**
	 * Methods named getQueryInfo() in MediaWiki usually
	 * return an array that is later fed to select
	 *
	 * @note This will only work where the return
	 *  statement is an array literal.
	 * @param Node|Mixed $node Node from ast tree
	 * @suppress PhanTypeMismatchForeach
	 */
	private function handleGetQueryInfoReturn( $node ) {
		if (
			!( $node instanceof Node ) ||
			$node->kind !== \ast\AST_ARRAY
		) {
			return;
		}
		// The argument order is
		// $table, $vars, $conds = '', $fname = __METHOD__,
		// $options = [], $join_conds = []
		$keysToArg = [
			'tables' => 0,
			'fields' => 1,
			'conds' => 2,
			'options' => 4,
			'join_conds' => 5,
		];
		$args = [ '', '', '', '' ];
		foreach ( $node->children as $child ) {
			assert( $child->kind === \ast\AST_ARRAY_ELEM );
			if ( !isset( $keysToArg[$child->children['key']] ) ) {
				continue;
			}
			$args[$keysToArg[$child->children['key']]] = $child->children['value'];
		}
		$selectFQSEN = FullyQualifiedMethodName::fromFullyQualifiedString(
			'\Wikimedia\Rdbms\IDatabase::select'
		);
		$select = $this->code_base->getMethodByFQSEN( $selectFQSEN );
		$taint = $this->getTaintOfFunction( $select );
		$this->handleMethodCall( $select, $selectFQSEN, $taint, $args );
		if ( isset( $args[4] ) ) {
			$this->checkSQLOptions( $args[4] );
		}
		if ( isset( $args[5] ) ) {
			$this->checkJoinCond( $args[5] );
		}
	}

	/**
	 * Check the options parameter to IDatabase::select
	 *
	 * This only works if its specified as an array literal.
	 *
	 * Relavent options:
	 *  GROUP BY is put directly in the query (array get's imploded)
	 *  HAVING is treated like a WHERE clause
	 *  ORDER BY is put directly in the query (array get's imploded)
	 *  USE INDEX is directly put in string (both array and string version)
	 *  IGNORE INDEX ditto
	 * @param Node|mixed $node The node from the AST tree
	 */
	private function checkSQLOptions( $node ) {
		if ( !( $node instanceof Node ) || $node->kind !== \ast\AST_ARRAY ) {
			return;
		}
		$relevant = [
			'GROUP BY',
			'ORDER BY',
			'HAVING',
			'USE INDEX',
			'IGNORE INDEX',
		];
		foreach ( $node->children as $arrayElm ) {
			assert( $arrayElm->kind === \ast\AST_ARRAY_ELEM );
			$val = $arrayElm->children['value'];
			$key = $arrayElm->children['key'];
			$taintType = ( $key === 'HAVING' && $this->nodeIsArray( $val ) ) ?
				SecurityCheckPlugin::SQL_NUMKEY_EXEC_TAINT :
				SecurityCheckPlugin::SQL_EXEC_TAINT;

			if ( in_array( $key, $relevant ) ) {
				$ctx = clone $this->context;
				$this->overrideContext = $ctx->withLineNumberStart(
					$val->lineno ?? $ctx->getLineNumberStart()
				);
				$this->maybeEmitIssue(
					$taintType,
					$this->getTaintedness( $val ),
					"$key clause is user controlled"
						. $this->getOriginalTaintLine( $val )
				);
				$this->overrideContext = null;
			}
		}
	}

	/**
	 * Check a join_cond structure.
	 *
	 * Syntax is like
	 *
	 *  [ 'aliasOfTable' => [ 'JOIN TYPE', $onConditions ], ... ]
	 *  join type is usually something safe like INNER JOIN, but it is not
	 *  validated or escaped. $onConditions is the same form as a WHERE clause.
	 *
	 * @param Node|mixed $node
	 */
	private function checkJoinCond( $node ) {
		if ( !( $node instanceof Node ) || $node->kind !== \ast\AST_ARRAY ) {
			return;
		}
		foreach ( $node->children as $table ) {
			assert( $table->kind === \ast\AST_ARRAY_ELEM );

			$tableName = is_string( $table->children['key'] ) ?
				$table->children['key'] :
				'[UNKNOWN TABLE]';
			$joinInfo = $table->children['value'];
			if (
				$joinInfo instanceof Node
				&& $joinInfo->kind === \ast\AST_ARRAY
			) {
				if (
					count( $joinInfo->children ) === 0 ||
					$joinInfo->children[0]->children['key'] !== null
				) {
					$this->debug( __METHOD__, "join info has named key??" );
					continue;
				}
				$joinType = $joinInfo->children[0]->children['value'];
				// join type does not get escaped.
				$this->maybeEmitIssue(
					SecurityCheckPlugin::SQL_EXEC_TAINT,
					$this->getTaintedness( $joinType ),
					"join type for $tableName is user controlled"
						. $this->getOriginalTaintLine( $joinType )
				);
				// On to the join ON conditions.
				if (
					count( $joinInfo->children ) === 1 ||
					$joinInfo->children[1]->children['key'] !== null
				) {
					$this->debug( __METHOD__, "join info has named key??" );
					continue;
				}
				$onCond = $joinInfo->children[1]->children['value'];
				$ctx = clone $this->context;
				$this->overrideContext = $ctx->withLineNumberStart(
					$onCond->lineno ?? $ctx->getLineNumberStart()
				);
				$this->maybeEmitIssue(
					SecurityCheckPlugin::SQL_NUMKEY_EXEC_TAINT,
					$this->getTaintedness( $onCond ),
					"The ON conditions are not properly escaped for the join to `$tableName`"
						. $this->getOriginalTaintLine( $onCond )
				);
				$this->overrideContext = null;
			}
		}
	}

	/**
	 * Check to see if isHTML => true and is tainted.
	 *
	 * @param Node $node The expr child of the return. NOT the return itself
	 * @suppress PhanTypeMismatchForeach
	 */
	private function visitReturnOfFunctionHook( Node $node, FQSEN $funcName ) {
		if (
			!( $node instanceof Node ) ||
			$node->kind !== \ast\AST_ARRAY ||
			count( $node->children ) < 2
		) {
			return;
		}
		$isHTML = false;
		foreach ( $node->children as $child ) {
			assert(
				$child instanceof Node
				&& $child->kind === \ast\AST_ARRAY_ELEM
			);

			if (
				$child->children['key'] === 'isHTML' &&
				$child->children['value'] instanceof Node &&
				$child->children['value']->kind === \ast\AST_CONST &&
				$child->children['value']->children['name'] instanceof Node &&
				$child->children['value']->children['name']->children['name'] === 'true'
			) {
				$isHTML = true;
				break;
			}
		}
		if ( !$isHTML ) {
			return;
		}
		$taintedness = $this->getTaintedness( $node->children[0] );
		$this->maybeEmitIssue(
			SecurityCheckPlugin::HTML_EXEC_TAINT,
			$taintedness,
			"Outputting user controlled HTML from Parser function hook $funcName"
				. $this->getOriginalTaintLine( $node->children[0] )
		);
	}

	/**
	 * Given a MediaWiki hook registration, find the callback
	 *
	 * @note This is a different format than Parser hooks use.
	 *
	 * Valid examples of callbacks:
	 *  "wfSomeFunction"
	 *  "SomeClass::SomeStaticMethod"
	 *  A Closure
	 *  $instanceOfSomeObject  (With implied method name based on hook name)
	 *  new SomeClass
	 *  [ <one of the above>, $extraArgsForCallback, ...]
	 *  [ [<one of the above>], $extraArgsForCallback, ...]
	 *  [ $instanceOfObj, 'methodName', $optionalArgForCallback, ... ]
	 *  [ [ $instanceOfObj, 'methodName' ], $optionalArgForCallback, ...]
	 *
	 * Oddly enough, [ 'NameOfClass', 'NameOfStaticMethod' ] does not appear
	 * to be valid, despite that being a valid callable.
	 *
	 * @param Node|string $node
	 * @param string $hookName
	 * @return FullyQualifiedFunctionLikeName|null The corresponding FQSEN
	 */
	private function getCallableFromHookRegistration( $node, $hookName ) {
		// "wfSomething", "Class::Method", closure
		if ( !is_object( $node ) || $node->kind === \ast\AST_CLOSURE ) {
			return $this->getFQSENFromCallable( $node );
		}

		if ( $node->kind === \ast\AST_VAR && is_string( $node->children['name'] ) ) {
			return $this->getCallbackForVar( $node, 'on' . $hookName );
		} elseif (
			$node->kind === \ast\AST_NEW &&
			is_string( $node->children['class']->children['name'] )
		) {
			$className = $node->children['class']->children['name'];
			$cb = FullyQualifiedMethodName::fromStringInContext(
				$className . '::' . 'on' . $hookName,
				$this->context
			);
			if ( $this->code_base->hasMethodWithFQSEN( $cb ) ) {
				return $cb;
			} else {
				// @todo Should almost emit a non-security issue for this
				$this->debug( __METHOD__, "Missing hook handle $cb" );
			}
		}

		if ( $node->kind === \ast\AST_ARRAY ) {
			if ( count( $node->children ) === 0 ) {
				return null;
			}
			$firstChild = $node->children[0]->children['value'];
			if (
				( $firstChild instanceof Node
				&& $firstChild->kind === \ast\AST_ARRAY ) ||
				!( $firstChild instanceof Node ) ||
				count( $node->children ) === 1
			) {
				// One of:
				// [ [ <callback> ], $optionalArgs, ... ]
				// [ 'SomeClass::method', $optionalArgs, ... ]
				// [ <callback> ]
				// Important to note, this is safe because the
				// [ 'SomeClass', 'MethodToCallStatically' ]
				// syntax isn't supported by hooks.
				return $this->getCallableFromHookRegistration( $firstChild, $hookName );
			}
			// Remaining case is: [ $someObject, 'methodToCall', 'arg', ... ]
			$methodName = $node->children[1]->children['value'];
			if ( !is_string( $methodName ) ) {
				return null;
			}
			if ( $firstChild->kind === \ast\AST_VAR && is_string( $firstChild->children['name'] ) ) {
				return $this->getCallbackForVar( $node, $methodName );

			} elseif (
				$firstChild->kind === \ast\AST_NEW &&
				is_string( $firstChild->children['class']->children['name'] )
			) {
				// FIXME does this work right with namespaces
				$className = $firstChild->children['class']->children['name'];
				$cb = FullyQualifiedMethodName::fromStringInContext(
					$className . '::' . $methodName,
					$this->context
				);
				if ( $this->code_base->hasMethodWithFQSEN( $cb ) ) {
					return $cb;
				} else {
					// @todo Should almost emit a non-security issue for this
					$this->debug( __METHOD__, "Missing hook handle $cb" );
				}
			}
		}
		return null;
	}

	/**
	 * Given an AST_VAR node, figure out what it represents as callback
	 *
	 * @note This doesn't handle classes implementing __invoke, but its
	 *  unclear if hooks support that either.
	 * @param Node $node The variable
	 * @param string $defaultMethod If the var is an object, what method to use
	 * @return FullyQualifiedFunctionLikeName|null The corresponding FQSEN
	 */
	private function getCallbackForVar( Node $node, $defaultMethod = '' ) {
		$cnode = $this->getCtxN( $node );
		$var = $cnode->getVariable();
		$types = $var->getUnionType()->getTypeSet();
		foreach ( $types as $type ) {
			if ( $type instanceof CallableType ) {
				return $this->getFQSENFromCallable( $node );
			}
			if ( $type->isNativeType() ) {
				return null;
			}
			if ( $defaultMethod ) {
				return FullyQualifiedMethodName::fromFullyQualifiedString(
					$type->asFQSEN() . '::' . $defaultMethod
				);
			}
		}
		return null;
	}

	/**
	 * Check for $wgHooks registration
	 *
	 * @param Node $node
	 * @note This assumes $wgHooks is always the global
	 *   even if there is no globals declaration.
	 */
	public function visitAssign( Node $node ) {
		$var = $node->children['var'];
		assert( $var instanceof Node );
		$cb = null;
		$hookName = null;
		$cbExpr = $node->children['expr'];
		// The $wgHooks['foo'][] case
		if (
			$var->kind === \ast\AST_DIM &&
			$var->children['dim'] === null &&
			$var->children['expr'] instanceof Node &&
			$var->children['expr']->kind === \ast\AST_DIM &&
			$var->children['expr']->children['expr'] instanceof Node &&
			is_string( $var->children['expr']->children['dim'] ) &&
			/* The $wgHooks['SomeHook'][] case */
			( ( $var->children['expr']->children['expr']->kind === \ast\AST_VAR &&
			$var->children['expr']->children['expr']->children['name'] === 'wgHooks' ) ||
			/* The $_GLOBALS['wgHooks']['SomeHook'][] case */
			( $var->children['expr']->children['expr']->kind === \ast\AST_DIM &&
			$var->children['expr']->children['expr']->children['expr'] instanceof Node &&
			$var->children['expr']->children['expr']->children['expr']->kind === \ast\AST_VAR &&
			$var->children['expr']->children['expr']->children['expr']->children['name'] === '_GLOBALS' ) )
		) {
			$hookName = $var->children['expr']->children['dim'];
		}

		if ( $hookName !== null ) {
			$cb = $this->getCallableFromHookRegistration( $cbExpr, $hookName );
			if ( $cb ) {
				$this->registerHook( $hookName, $cb );
			} else {
				$this->debug( __METHOD__, "Could not register hook " .
					"$hookName due to complex callback"
				);
			}
		}
	}
}
