<?php

namespace SecurityCheckPlugin;

use ast\Node;
use Exception;
use Phan\Debug;
use Phan\PluginV3\BeforeLoopBodyAnalysisVisitor;

class TaintednessLoopVisitor extends BeforeLoopBodyAnalysisVisitor {
	use TaintednessBaseVisitor;

	/**
	 * Visit a foreach loop
	 *
	 * We do that in this visitor so that we can handle the loop condition prior to
	 * determine the taint of the loop variable, prior to evaluating the loop body.
	 * See https://github.com/phan/phan/issues/3936
	 *
	 * @param Node $node
	 */
	public function visitForeach( Node $node ) : void {
		// TODO: Could we do something better here detecting the array
		// type
		$expr = $node->children['expr'];
		$lhsTaintedness = $this->getTaintedness( $expr );

		$value = $node->children['value'];
		if ( $value->kind === \ast\AST_REF ) {
			// TODO, this doesn't propagate the taint to the outer scope
			// (FWIW, phan doesn't do much better with types, https://github.com/phan/phan/issues/4017)
			$value = $value->children['var'];
		}

		$handledNodes = [ \ast\AST_VAR, \ast\AST_PROP, \ast\AST_STATIC_PROP ];
		if ( in_array( $value->kind, $handledNodes, true ) ) {
			try {
				$valueObj = $value->kind === \ast\AST_VAR
					? $this->getCtxN( $value )->getVariable()
					: $this->getCtxN( $value )->getProperty( $value->kind === \ast\AST_STATIC_PROP );
			} catch ( Exception $e ) {
				$valueObj = null;
				$this->debug( __METHOD__, "Cannot get foreach value " . $this->getDebugInfo( $e ) );
			}
			if ( $valueObj !== null ) {
				$this->setTaintedness( $valueObj, $lhsTaintedness, $value->kind === \ast\AST_VAR );
				$this->mergeTaintDependencies( $valueObj, $expr );
				$this->mergeTaintError( $valueObj, $expr );
			}
		} else {
			$this->debug( __METHOD__, "FIXME foreach complex value not handled: " . Debug::nodeToString( $value ) );
		}

		$key = $node->children['key'] ?? null;
		if ( $key instanceof Node ) {
			if ( in_array( $key->kind, $handledNodes, true ) ) {
				try {
					$keyObj = $key->kind === \ast\AST_VAR
						? $this->getCtxN( $key )->getVariable()
						: $this->getCtxN( $key )->getProperty( $key->kind === \ast\AST_STATIC_PROP );
				} catch ( Exception $e ) {
					$keyObj = null;
					$this->debug( __METHOD__, "Cannot get foreach key " . $this->getDebugInfo( $e ) );
				}

				if ( $keyObj !== null ) {
					// TODO This will probably have a lot of false positives with
					// arrays containing only numeric keys.
					$this->setTaintedness( $keyObj, $lhsTaintedness, $key->kind === \ast\AST_VAR );
					$this->mergeTaintDependencies( $keyObj, $expr );
					$this->mergeTaintError( $keyObj, $expr );
				}
			} else {
				$this->debug( __METHOD__, "FIXME foreach complex key not handled: " . Debug::nodeToString( $key ) );
			}
		}
	}
}
