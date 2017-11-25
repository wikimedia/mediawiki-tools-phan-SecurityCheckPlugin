<?php

use Phan\Language\Context;
use Phan\Language\FQSEN\FullyQualifiedMethodName;
use Phan\Language\FQSEN\FullyQualifiedFunctionName;
use Phan\Language\FQSEN;
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
	 * Try and recognize
	 * @param Node $node
	 */
	public function visitMethodCall( Node $node ) {
		try {
			$ctx = $this->getCtxN( $node );
			$methodName = $node->children['method'];
			$method = $ctx->getMethod(
				$methodName,
				false /* not a static call */
			);
			// Should this be getDefiningFQSEN() instead?
			switch ( (string)$method->getFQSEN() ) {
				case '\Parser::setFunctionHook':
					$this->handleFuncRegistration( $node );
					break;
			}
		} catch ( Exception $e ) {
			// ignore
		}
	}

	/**
	 * When someone calls $parser->setFunctionHook()
	 *
	 * @note Causes phan to error out if given non-existent class
	 * @param Node $node
	 */
	private function handleFuncRegistration( Node $node ) {
		$args = $node->children['args']->children;
		if ( count( $args ) < 2 ) {
			return;
		}
		$callback = $this->getFQSENFromCallable( $args[1] );
		if ( $callback ) {
			$alreadyRegistered = $this->plugin->registerHook( '!ParserFunctionHook', $callback );
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
		$hookType = $this->plugin->isSpecialHookSubscriber( $funcFQSEN );
		switch ( $hookType ) {
		case '!ParserFunctionHook':
			$this->visitReturnOfFunctionHook( $node->children['expr'], $funcFQSEN );
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
}
