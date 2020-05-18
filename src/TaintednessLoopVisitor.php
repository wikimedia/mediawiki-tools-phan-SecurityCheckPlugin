<?php

use ast\Node;
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
	public function visitForeach( Node $node ) {
		// TODO: Could we do something better here detecting the array
		// type
		$lhsTaintedness = $this->getTaintedness( $node->children['expr'] );

		$value = $node->children['value'];
		if ( $value->kind === \ast\AST_REF ) {
			// FIXME, this doesn't fully handle the ref case.
			// taint probably won't be propagated to outer scope.
			$value = $value->children['var'];
		}

		if ( $value->kind !== \ast\AST_VAR ) {
			$this->debug( __METHOD__, "FIXME foreach complex case not handled" );
			// Debug::printNode( $node );
			return;
		}

		try {
			$variableObj = $this->getCtxN( $value )->getVariable();
			$this->setTaintedness( $variableObj, $lhsTaintedness );

			if ( isset( $node->children['key'] ) ) {
				// This will probably have a lot of false positives with
				// arrays containing only numeric keys.
				assert( $node->children['key']->kind === \ast\AST_VAR );
				$variableObj = $this->getCtxN( $node->children['key'] )->getVariable();
				$this->setTaintedness( $variableObj, $lhsTaintedness );
			}
		} catch ( Exception $e ) {
			$this->debug( __METHOD__, "Exception " . $this->getDebugInfo( $e ) );
		}
	}
}
