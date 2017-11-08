<?php

use Phan\AST\AnalysisVisitor;
use Phan\AST\ContextNode;
use Phan\CodeBase;
use Phan\Language\Context;
use Phan\Language\Element\Clazz;
use Phan\Language\Element\Func;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\Method;
use Phan\Language\Element\Variable;
use Phan\Language\Element\Parameter;
use Phan\Language\UnionType;
use Phan\Language\FQSEN\FullyQualifiedFunctionLikeName;
use Phan\Plugin;
use Phan\Plugin\PluginImplementation;
use ast\Node;
use ast\Node\Decl;
use Phan\Debug;
use Phan\Language\Scope\FunctionLikeScope;
use Phan\Language\Scope\BranchScope;

class PreTaintednessVisitor extends TaintednessBaseVisitor {

	public function visit( Node $node ) {
		// no-op
	}

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
			Debug::printNode( $node );
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
		} catch( Exception $e ) {
			// getVariable can throw an IssueException if var doesn't exist.
			$this->debug( __METHOD__, "Exception " . get_class( $e ) . $e->getMessage() . "" );
		}

		// The foreach as a block cannot be tainted.
		return SecurityCheckPlugin::NO_TAINT;
	}

	public function visitFuncDecl( Decl $node ) {
		return $this->visitMethod( $node );
	}

	/**
	 * Also handles FuncDecl
	 */
	public function visitMethod( Decl $node ) {
		//var_dump( __METHOD__ ); Debug::printNode( $node );
		$method = $this->context->getFunctionLikeInScope( $this->code_base );

		$params = $node->children['params']->children;
		$varObjs = [];
		foreach( $params as $i => $param ) {
			$scope = $this->context->getScope();
			if ( !$scope->hasVariableWithName( $param->children['name'] ) ) {
				// Well uh-oh.
				$this->debug( __METHOD__, "Missing variable for param \$" . $param->children['name'] );
				continue;
			}
			$varObj = $scope->getVariableByName( $param->children['name'] );
			$paramTypeTaint = $this->getTaintByReturnType( $varObj->getUnionType() );
			if ( $paramTypeTaint === SecurityCheckPlugin::NO_TAINT ) {
				// The param is an integer or something, so skip.
				$this->setTaintedness( $varObj, $paramTypeTaint );
				continue;
			}

			// Its going to depend on whether anyone calls the method
			// with something dangerous.
			$this->setTaintedness( $varObj, SecurityCheckPlugin::PRESERVE_TAINT );
			$this->linkParamAndFunc( $varObj, $method, $i );
		}
	}
}
