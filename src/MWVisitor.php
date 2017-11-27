<?php

use Phan\Language\Context;
use Phan\Language\FQSEN\FullyQualifiedMethodName;
use Phan\Language\FQSEN\FullyQualifiedFunctionName;
use Phan\Language\FQSEN\FullyQualifiedFunctionLikeName;
use Phan\Language\FQSEN;
use Phan\Language\Type\CallableType;
use Phan\Plugin;
use Phan\CodeBase;
use ast\Node;

/**
 * MediaWiki specific node visitor
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
			}
		} catch ( Exception $e ) {
			// ignore
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
			$this->debug( __METHOD__, "registering $cb as handling $hookName" );
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
			if ( $callback->isClosure() ) {
				// For closures we have to reanalyze the parent
				// function, as we can't reanalyze the closure, and
				// we definitely need to since the closure would
				// have already been analyzed at this point since
				// we are operating in post-order.
				$func = $this->context->getFunctionLikeInScope( $this->code_base );
			} elseif ( $callback instanceof FullyQualifiedMethodName ) {
				$func = $this->code_base->getMethodByFQSEN( $callback );
			} else {
				assert( $callback instanceof FullyQualifiedFunctionName );
				$func = $this->code_base->getFunctionByFQSEN( $callback );
			}
			// Make sure we reanalyze the hook function now that
			// we know what it is, in case its already been
			// analyzed.
			$func->analyze(
				$func->getContext(),
				$this->code_base
			);
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
		$hookType = $this->plugin->isSpecialHookSubscriber( $funcFQSEN );
		switch ( $hookType ) {
		case '!ParserFunctionHook':
			$this->visitReturnOfFunctionHook( $node->children['expr'], $funcFQSEN );
			break;
		case '!ParserHook':
			$ret = $node->children['expr'];
			$taintedness = $this->getTaintedness( $ret );
			if ( !$this->isSafeAssignment(
				SecurityCheckPlugin::HTML_EXEC_TAINT,
				$taintedness
			) ) {
				$this->plugin->emitIssue(
					$this->code_base,
					$this->context,
					'SecurityCheckTaintedOutput',
					"Outputting evil HTML from Parser tag hook $funcFQSEN"
						. $this->getOriginalTaintLine( $ret )
				);
			}
			break;
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
		if ( !$this->isSafeAssignment( SecurityCheckPlugin::HTML_EXEC_TAINT, $taintedness ) ) {
			$this->plugin->emitIssue(
				$this->code_base,
				$this->context,
				'SecurityCheckTaintedOutput',
				"Outputting evil HTML from Parser function hook $funcName"
					. $this->getOriginalTaintLine( $node->children[0] )
			);
		}
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

		// FIXME: This doesn't support syntax like:
		// $wgHooks['foo'][] = new HookHandler;
		// which is valid.
		if ( $node->kind === \ast\AST_VAR && is_string( $node->children['name'] ) ) {
			return $this->getCallbackForVar( $node, 'on' . $hookName );
		} elseif (
			$node->kind === \ast\AST_NEW &&
			is_string( $node->children['class']->children['name'] )
		) {
			$className = $node->children['class']->children['name'];
			return FullyQualifiedMethodName::fromStringInContext(
				$className . '::' . 'on' . $hookName,
				$this->context
			);
		}

		if ( $node->kind === \ast\AST_ARRAY ) {
			if ( count( $node->children ) === 0 ) {
				return null;
			}
			$firstChild = $node->children[0];
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
			if ( !is_string( $node->children[1] ) ) {
				return null;
			}
			$methodName = $node->children[1];
			if ( $firstChild->kind === \ast\AST_VAR && is_string( $firstChild->children['name'] ) ) {
				return $this->getCallbackForVar( $node, $methodName );

			} elseif (
				$firstChild->kind === \ast\AST_NEW &&
				is_string( $firstChild->children['class']->children['name'] )
			) {
				// FIXME does this work right with namespaces
				$className = $firstChild->children['class']->children['name'];
				return FullyQualifiedMethodName::fromFullyQualifiedString(
					$className . '::' . $methodName
				);
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
}
