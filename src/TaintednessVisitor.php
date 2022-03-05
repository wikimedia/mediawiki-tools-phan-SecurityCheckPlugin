<?php declare( strict_types=1 );
/**
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

namespace SecurityCheckPlugin;

use ast\Node;
use Phan\Analysis\BlockExitStatusChecker;
use Phan\AST\ContextNode;
use Phan\Debug;
use Phan\Exception\CodeBaseException;
use Phan\Exception\IssueException;
use Phan\Exception\NodeException;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\GlobalVariable;
use Phan\Language\FQSEN\FullyQualifiedClassName;
use Phan\Language\FQSEN\FullyQualifiedFunctionName;
use Phan\Language\Type\FunctionLikeDeclarationType;
use Phan\PluginV3\PluginAwarePostAnalysisVisitor;

/**
 * This class visits all the nodes in the ast. It has two jobs:
 *
 * 1) Return the taint value of the current node we are visiting.
 * 2) In the event of an assignment (and similar things) propagate
 *  the taint value from the left hand side to the right hand side.
 *
 * For the moment, the taint values are stored in a "taintedness"
 * property of various phan TypedElement objects. This is probably
 * not the best solution for where to store the data, but its what
 * this does for now.
 *
 * This also maintains some other properties, such as where the error
 * originates, and dependencies in certain cases.
 *
 * @phan-file-suppress PhanUnusedPublicMethodParameter Many methods don't use $node
 */
class TaintednessVisitor extends PluginAwarePostAnalysisVisitor {
	use TaintednessBaseVisitor;

	/**
	 * Node kinds whose taintedness is not well-defined and for which we don't need a visit* method.
	 */
	public const INAPPLICABLE_NODES_WITHOUT_VISITOR = [
		\ast\AST_ARG_LIST => true,
		\ast\AST_TYPE => true,
		\ast\AST_NULLABLE_TYPE => true,
		\ast\AST_PARAM_LIST => true,
		// Params are handled in PreTaintednessVisitor
		\ast\AST_PARAM => true,
		\ast\AST_CLASS => true,
		\ast\AST_USE_ELEM => true,
		\ast\AST_STMT_LIST => true,
		\ast\AST_CLASS_CONST_DECL => true,
		\ast\AST_CLASS_CONST_GROUP => true,
		\ast\AST_CONST_DECL => true,
		\ast\AST_IF => true,
		\ast\AST_IF_ELEM => true,
		\ast\AST_PROP_DECL => true,
		\ast\AST_CONST_ELEM => true,
		\ast\AST_USE => true,
		\ast\AST_USE_TRAIT => true,
		\ast\AST_BREAK => true,
		\ast\AST_CONTINUE => true,
		\ast\AST_GOTO => true,
		\ast\AST_CATCH => true,
		\ast\AST_NAMESPACE => true,
		\ast\AST_SWITCH => true,
		\ast\AST_SWITCH_CASE => true,
		\ast\AST_SWITCH_LIST => true,
		\ast\AST_WHILE => true,
		\ast\AST_DO_WHILE => true,
		\ast\AST_FOR => true,
		// Handled in TaintednessLoopVisitor
		\ast\AST_FOREACH => true,
		\ast\AST_EXPR_LIST => true,
		\ast\AST_TRY => true,
		// Array elems are handled directly in visitArray
		\ast\AST_ARRAY_ELEM => true,
		// Initializing the prop is done in preorder
		\ast\AST_PROP_ELEM => true,
		\ast\AST_PROP_GROUP => true,
		// Variables are already handled in visitVar
		\ast\AST_CLOSURE_VAR => true,
		\ast\AST_CLOSURE_USES => true,
	];

	/**
	 * Node kinds whose taintedness is not well-defined, but for which we still need a visit* method.
	 * Trying to get the taintedness of these nodes will still result in an error.
	 */
	public const INAPPLICABLE_NODES_WITH_VISITOR = [
		\ast\AST_GLOBAL => true,
		\ast\AST_RETURN => true,
		\ast\AST_STATIC => true,
		\ast\AST_FUNC_DECL => true,
		\ast\AST_METHOD => true,
	];

	/**
	 * Map of node kinds whose taintedness is not well-defined, e.g. because that node
	 * cannot be used as an expression. Note that it's safe to use array plus here.
	 */
	private const INAPPLICABLE_NODES = self::INAPPLICABLE_NODES_WITHOUT_VISITOR + self::INAPPLICABLE_NODES_WITH_VISITOR;

	/** @var TaintednessWithError|null */
	private $curTaintWithError;

	/**
	 * @param Node $node
	 * @return TaintednessWithError
	 */
	public function analyzeNodeAndGetTaintedness( Node $node ): TaintednessWithError {
		assert(
			!isset( self::INAPPLICABLE_NODES[$node->kind] ),
			'Should not try to get taintedness of inapplicable nodes (got ' . Debug::nodeName( $node ) . ')'
		);
		$this->__invoke( $node );
		$this->setCachedData( $node );
		return $this->curTaintWithError;
	}

	/**
	 * Cache taintedness data in an AST node. Ideally we'd want this to happen at the end of __invoke, but phan
	 * calls visit* methods by name, so that doesn't work.
	 * Caching a node *may* improve the speed, but *will* increase the memory usage, so only do that for nodes
	 * whose taintedness:
	 *  - Is not trivial to compute, and
	 *  - Might be needed from another node (via getTaintednessNode)
	 * @param Node $node
	 */
	private function setCachedData( Node $node ): void {
		// @phan-suppress-next-line PhanUndeclaredProperty
		$node->taint = $this->curTaintWithError;
	}

	/**
	 * Sets $this->curTaint to UNKNOWN. Shorthand to filter the usages of curTaint.
	 */
	private function setCurTaintUnknown(): void {
		$this->curTaintWithError = new TaintednessWithError(
			Taintedness::newUnknown(),
			new CausedByLines(),
			MethodLinks::newEmpty()
		);
	}

	private function setCurTaintSafe(): void {
		$this->curTaintWithError = new TaintednessWithError(
			Taintedness::newSafe(),
			new CausedByLines(),
			MethodLinks::newEmpty()
		);
	}

	/**
	 * Generic visitor when we haven't defined a more specific one.
	 *
	 * @param Node $node
	 */
	public function visit( Node $node ): void {
		// This method will be called on all nodes for which
		// there is no implementation of its kind visitor.

		if ( isset( self::INAPPLICABLE_NODES_WITHOUT_VISITOR[$node->kind] ) ) {
			return;
		}

		// To see what kinds of nodes are passing through here,
		// you can run `Debug::printNode($node)`.
		# Debug::printNode( $node );
		$this->debug( __METHOD__, "unhandled case " . Debug::nodeName( $node ) );
		$this->setCurTaintUnknown();
	}

	/**
	 * @param Node $node
	 */
	public function visitClosure( Node $node ): void {
		// We cannot use getFunctionLikeInScope for closures
		$closureFQSEN = FullyQualifiedFunctionName::fromClosureInContext( $this->context, $node );

		if ( $this->code_base->hasFunctionWithFQSEN( $closureFQSEN ) ) {
			$func = $this->code_base->getFunctionByFQSEN( $closureFQSEN );
			$this->analyzeFunctionLike( $func );
		} else {
			$this->debug( __METHOD__, 'closure doesn\'t exist' );
		}
		$this->setCurTaintSafe();
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitFuncDecl( Node $node ): void {
		$func = $this->context->getFunctionLikeInScope( $this->code_base );
		$this->analyzeFunctionLike( $func );
	}

	/**
	 * Visit a method declaration
	 *
	 * @param Node $node
	 */
	public function visitMethod( Node $node ): void {
		$method = $this->context->getFunctionLikeInScope( $this->code_base );
		$this->analyzeFunctionLike( $method );
	}

	/**
	 * @param Node $node
	 */
	public function visitArrowFunc( Node $node ): void {
		$this->visitClosure( $node );
	}

	/**
	 * Handles methods, functions and closures.
	 *
	 * @param FunctionInterface $func The func to analyze
	 */
	private function analyzeFunctionLike( FunctionInterface $func ): void {
		if ( self::getFuncTaint( $func ) === null ) {
			// If we still have no data, presumably the function doesn't return anything, so mark as safe.
			if ( $func->hasReturn() || $func->hasYield() ) {
				$this->debug( __METHOD__, "TODO: $func returns something but has no taint after analysis" );
			}

			// NOTE: If the method stores its arg to a class prop, and that class prop gets output later,
			// the exec status of this won't be detected until the output is analyzed, we might miss some issues
			// in the inbetween period.
			self::doSetFuncTaint( $func, new FunctionTaintedness( Taintedness::newSafe() ) );
		}
	}

	/**
	 * FooBar::class, presumably safe since class names cannot have special chars.
	 *
	 * @param Node $node
	 */
	public function visitClassName( Node $node ): void {
		$this->setCurTaintSafe();
		$this->setCachedData( $node );
	}

	/**
	 * @note AST_THROW is an expression since PHP 8
	 * @param Node $node
	 */
	public function visitThrow( Node $node ): void {
		$this->setCurTaintSafe();
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitUnset( Node $node ): void {
		$this->setCurTaintSafe();
	}

	/**
	 * @param Node $node
	 */
	public function visitClone( Node $node ): void {
		$this->curTaintWithError = clone $this->getTaintedness( $node->children['expr'] );
		$this->setCachedData( $node );
	}

	/**
	 * Assignment operators are: .=, +=, -=, /=, %=, *=, **=, ??=, |=, &=, ^=, <<=, >>=
	 * @param Node $node
	 */
	public function visitAssignOp( Node $node ): void {
		$lhs = $node->children['var'];
		if ( !$lhs instanceof Node ) {
			// Syntax error, don't crash
			$this->setCurTaintSafe();
			return;
		}
		$rhs = $node->children['expr'];
		$lhsTaintedness = $this->getTaintedness( $lhs );
		$rhsTaintedness = $this->getTaintedness( $rhs );

		if ( property_exists( $node, 'assignTaintMask' ) ) {
			// @phan-suppress-next-line PhanUndeclaredProperty
			$mask = $node->assignTaintMask;
			// TODO Should we consume the value, since it depends on the union types?
		} else {
			$this->debug( __METHOD__, 'FIXME no preorder visit?' );
			$mask = SecurityCheckPlugin::ALL_TAINT_FLAGS;
		}

		// Expand rhs to include implicit lhs ophand.
		$allRHSTaint = $this->getBinOpTaint(
			$lhsTaintedness->getTaintedness(),
			$rhsTaintedness->getTaintedness(),
			$node->flags,
			$mask
		);
		$allError = $lhsTaintedness->getError()->asMergedWith( $rhsTaintedness->getError() );
		$allLinks = $lhsTaintedness->getMethodLinks()->asMergedWith( $rhsTaintedness->getMethodLinks() );

		$curTaint = $this->doVisitAssign(
			$lhs,
			$rhs,
			$allRHSTaint,
			$allError,
			$allLinks,
			$rhsTaintedness->getTaintedness(),
			$rhsTaintedness->getMethodLinks()
		);
		// TODO Links and error?
		$this->curTaintWithError = new TaintednessWithError( $curTaint, new CausedByLines(), MethodLinks::newEmpty() );
		$this->setCachedData( $node );
	}

	/**
	 * `static $var = 'foo'` Handle it as an assignment of a safe value, to initialize the taintedness
	 * on $var. Ideally, we'd want to retain any taintedness on this object, but it's currently impossible
	 * (upstream has the same limitation with union types).
	 *
	 * @param Node $node
	 */
	public function visitStatic( Node $node ): void {
		$var = $this->getCtxN( $node->children['var'] )->getVariable();
		$this->ensureTaintednessIsSet( $var );
	}

	/**
	 * @param Node $node
	 */
	public function visitAssignRef( Node $node ): void {
		$this->visitAssign( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitAssign( Node $node ): void {
		$lhs = $node->children['var'];
		if ( !$lhs instanceof Node ) {
			// Syntax error, don't crash
			$this->setCurTaintSafe();
			return;
		}
		$rhs = $node->children['expr'];

		$rhsTaintedness = $this->getTaintedness( $rhs );

		$curTaint = $this->doVisitAssign(
			$lhs,
			$rhs,
			clone $rhsTaintedness->getTaintedness(),
			$rhsTaintedness->getError(),
			$rhsTaintedness->getMethodLinks(),
			$rhsTaintedness->getTaintedness(),
			$rhsTaintedness->getMethodLinks()
		);
		// TODO Links and error?
		$this->curTaintWithError = new TaintednessWithError( $curTaint, new CausedByLines(), new MethodLinks() );
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $lhs
	 * @param Node|mixed $rhs
	 * @param Taintedness $rhsTaint
	 * @param CausedByLines $rhsError
	 * @param MethodLinks $rhsLinks
	 * @param Taintedness $errorTaint
	 * @param MethodLinks $errorLinks
	 * @return Taintedness
	 */
	private function doVisitAssign(
		Node $lhs,
		$rhs,
		Taintedness $rhsTaint,
		CausedByLines $rhsError,
		MethodLinks $rhsLinks,
		Taintedness $errorTaint,
		MethodLinks $errorLinks
	): Taintedness {
		$vis = new TaintednessAssignVisitor(
			$this->code_base,
			$this->context,
			clone $rhsTaint,
			$rhsError,
			$rhsLinks,
			$errorTaint,
			$errorLinks,
			function () use ( $rhs ): bool {
				return $this->nodeIsArray( $rhs );
			}
		);
		$vis( $lhs );
		return $rhsTaint;
	}

	/**
	 * @param Node $node
	 */
	public function visitBinaryOp( Node $node ): void {
		$lhs = $node->children['left'];
		$rhs = $node->children['right'];
		$mask = $this->getBinOpTaintMask( $node, $lhs, $rhs );
		if ( $mask === SecurityCheckPlugin::NO_TAINT ) {
			// If the operation is safe, don't waste time analyzing children.This might also create bugs
			// like the test undeclaredvar2
			$this->setCurTaintSafe();
			$this->setCachedData( $node );
			return;
		}
		$leftTaint = $this->getTaintedness( $lhs );
		$rightTaint = $this->getTaintedness( $rhs );
		$curTaint = $this->getBinOpTaint(
			$leftTaint->getTaintedness(),
			$rightTaint->getTaintedness(),
			$node->flags,
			$mask
		);
		$this->curTaintWithError = new TaintednessWithError(
			$curTaint,
			$leftTaint->getError()->asMergedWith( $rightTaint->getError() ),
			$leftTaint->getMethodLinks()->asMergedWith( $rightTaint->getMethodLinks() )
		);
		$this->setCachedData( $node );
	}

	/**
	 * Get the taintedness of a binop, depending on the op type, applying the given flags
	 * @param Taintedness $leftTaint
	 * @param Taintedness $rightTaint
	 * @param int $op Represented by a flags in \ast\flags
	 * @param int $mask
	 * @return Taintedness
	 */
	private function getBinOpTaint(
		Taintedness $leftTaint,
		Taintedness $rightTaint,
		int $op,
		int $mask
	): Taintedness {
		if ( $op === \ast\flags\BINARY_ADD && $mask !== SecurityCheckPlugin::NO_TAINT ) {
			// HACK: This means that a node can be array, so assume array plus
			$combinedTaint = $leftTaint->asArrayPlusWith( $rightTaint );
		} else {
			$combinedTaint = $leftTaint->asMergedWith( $rightTaint )->asCollapsed()->withOnly( $mask );
		}
		return $combinedTaint;
	}

	/**
	 * @param Node $node
	 */
	public function visitDim( Node $node ): void {
		$varNode = $node->children['expr'];
		if ( !$varNode instanceof Node ) {
			// Accessing offset of a string literal
			$this->setCurTaintSafe();
			$this->setCachedData( $node );
			return;
		}
		$nodeTaint = $this->getTaintednessNode( $varNode );
		if ( $node->children['dim'] === null ) {
			// This should only happen in assignments: $x[] = 'foo'. Just return
			// the taint of the whole object.
			$this->curTaintWithError = clone $nodeTaint;
			$this->setCachedData( $node );
			return;
		}
		$offset = $this->resolveOffset( $node->children['dim'] );
		$this->curTaintWithError = new TaintednessWithError(
			clone $nodeTaint->getTaintedness()->getTaintednessForOffsetOrWhole( $offset ),
			$nodeTaint->getError(),
			$nodeTaint->getMethodLinks()->getForDim( $offset )
		);
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitPrint( Node $node ): void {
		$this->visitEcho( $node );
	}

	/**
	 * This is for exit() and die(). If they're passed an argument, they behave the
	 * same as print.
	 * @param Node $node
	 */
	public function visitExit( Node $node ): void {
		$this->visitEcho( $node );
	}

	/**
	 * Visits the backtick operator. Note that shell_exec() has a simple AST_CALL node.
	 * @param Node $node
	 */
	public function visitShellExec( Node $node ): void {
		$this->visitSimpleSinkAndPropagate(
			$node,
			SecurityCheckPlugin::SHELL_EXEC_TAINT,
			'Backtick shell execution operator contains user controlled arg'
		);
		// Its unclear if we should consider this tainted or not
		$this->curTaintWithError = new TaintednessWithError(
			Taintedness::newTainted(),
			new CausedByLines(),
			MethodLinks::newEmpty()
		);
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitIncludeOrEval( Node $node ): void {
		if ( $node->flags === \ast\flags\EXEC_EVAL ) {
			$taintValue = SecurityCheckPlugin::CODE_EXEC_TAINT;
			$msg = 'The code supplied to `eval` is user controlled';
		} else {
			$taintValue = SecurityCheckPlugin::PATH_EXEC_TAINT;
			$msg = 'The included path is user controlled';
		}
		$this->visitSimpleSinkAndPropagate( $node, $taintValue, $msg );
		// Strictly speaking we have no idea if the result
		// of an eval() or require() is safe. But given that we
		// don't know, and at least in the require() case its
		// fairly likely to be safe, no point in complaining.
		$this->setCurTaintSafe();
		$this->setCachedData( $node );
	}

	/**
	 * Also handles exit() and print
	 *
	 * We assume a web based system, where outputting HTML via echo
	 * is bad. This will have false positives in a CLI environment.
	 *
	 * @param Node $node
	 */
	public function visitEcho( Node $node ): void {
		$this->visitSimpleSinkAndPropagate(
			$node,
			SecurityCheckPlugin::HTML_EXEC_TAINT,
			'Echoing expression that was not html escaped'
		);
		$this->setCurTaintSafe();
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 * @param int $sinkTaintInt
	 * @param string $issueMsg
	 */
	private function visitSimpleSinkAndPropagate( Node $node, int $sinkTaintInt, string $issueMsg ): void {
		if ( !isset( $node->children['expr'] ) ) {
			return;
		}
		$expr = $node->children['expr'];
		$exprTaint = $this->getTaintedness( $expr );

		$sinkTaint = new Taintedness( $sinkTaintInt );
		$rhsTaint = $exprTaint->getTaintedness();
		$this->maybeEmitIssue(
			$sinkTaint,
			$rhsTaint,
			"$issueMsg{DETAILS}",
			[ $exprTaint->getError() ]
		);

		if ( $expr instanceof Node && !$rhsTaint->has( Taintedness::flagsAsExecToYesTaint( $sinkTaintInt ) ) ) {
			$this->backpropagateArgTaint( $expr, $sinkTaint );
		}
	}

	/**
	 * @param Node $node
	 */
	public function visitStaticCall( Node $node ): void {
		$this->visitMethodCall( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitNew( Node $node ): void {
		$ctxNode = $this->getCtxN( $node );
		if ( !$node->children['class'] instanceof Node ) {
			// Syntax error, don't crash
			$this->setCurTaintSafe();
			return;
		}

		// We check the __construct() method first, but the
		// final resulting taint is from the __toString()
		// method. This is a little hacky.
		try {
			// First do __construct()
			$constructor = $ctxNode->getMethod(
				'__construct',
				false,
				false,
				true
			);
		} catch ( NodeException | CodeBaseException | IssueException $_ ) {
			$constructor = null;
		}

		if ( $constructor ) {
			$this->handleMethodCall(
				$constructor,
				$constructor->getFQSEN(),
				$node->children['args']->children,
				false
			);
		}

		// Now return __toString()
		try {
			$clazzes = $ctxNode->getClassList(
				false,
				ContextNode::CLASS_LIST_ACCEPT_OBJECT_OR_CLASS_NAME,
				null,
				false
			);
		} catch ( CodeBaseException | IssueException $e ) {
			$this->debug( __METHOD__, 'Cannot get class: ' . $this->getDebugInfo( $e ) );
			$this->setCurTaintUnknown();
			$this->setCachedData( $node );
			return;
		}

		// If we find no __toString(), then presumably the object can't be outputted, so should be safe.
		$this->curTaintWithError = TaintednessWithError::newEmpty();
		foreach ( $clazzes as $clazz ) {
			try {
				$toString = $clazz->getMethodByName( $this->code_base, '__toString' );
			} catch ( CodeBaseException $_ ) {
				// No __toString() in this class
				continue;
			}

			$this->curTaintWithError->mergeWith( $this->handleMethodCall( $toString, $toString->getFQSEN(), [] ) );
		}

		$this->setCachedData( $node );
	}

	/**
	 * Somebody calls a method or function
	 *
	 * This has to figure out:
	 *  Is the return value of the call tainted
	 *  Are any of the arguments tainted
	 *  Does the function do anything scary with its arguments
	 * It also has to maintain quite a bit of book-keeping.
	 *
	 * This also handles (function) call, static call, and new operator
	 * @param Node $node
	 */
	public function visitMethodCall( Node $node ): void {
		$funcs = $this->getFuncsFromNode( $node, __METHOD__ );
		if ( !$funcs ) {
			$this->setCurTaintUnknown();
			$this->setCachedData( $node );
			return;
		}

		$this->analyzeCallNode( $node, $funcs );
	}

	/**
	 * @param Node $node
	 * @param iterable<FunctionInterface> $funcs
	 */
	protected function analyzeCallNode( Node $node, iterable $funcs ): void {
		$args = $node->children['args']->children;
		$this->curTaintWithError = TaintednessWithError::newEmpty();
		foreach ( $funcs as $func ) {
			// No point in analyzing abstract function declarations
			if ( !$func instanceof FunctionLikeDeclarationType ) {
				$this->curTaintWithError->mergeWith( $this->handleMethodCall( $func, $func->getFQSEN(), $args ) );
			}
		}
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitNullsafeMethodCall( Node $node ): void {
		$this->visitMethodCall( $node );
	}

	/**
	 * A function call
	 *
	 * @param Node $node
	 */
	public function visitCall( Node $node ): void {
		$this->visitMethodCall( $node );
	}

	/**
	 * A variable (e.g. $foo)
	 *
	 * This always considers superglobals as tainted
	 *
	 * @param Node $node
	 */
	public function visitVar( Node $node ): void {
		$varName = $this->getCtxN( $node )->getVariableName();
		if ( $varName === '' ) {
			// Something that phan can't understand, e.g. `$$foo` with unknown `$foo`.
			$this->setCurTaintUnknown();
			return;
		}

		$hardcodedTaint = $this->getHardcodedTaintednessForVar( $varName );
		if ( $hardcodedTaint ) {
			$this->curTaintWithError = new TaintednessWithError(
				$hardcodedTaint,
				new CausedByLines(),
				MethodLinks::newEmpty()
			);
			$this->setCachedData( $node );
			return;
		}
		if ( !$this->context->getScope()->hasVariableWithName( $varName ) ) {
			// Probably the var just isn't in scope yet.
			// $this->debug( __METHOD__, "No var with name \$$varName in scope (Setting Unknown taint)" );
			$this->setCurTaintUnknown();
			$this->setCachedData( $node );
			return;
		}
		$variableObj = $this->context->getScope()->getVariableByName( $varName );
		$this->curTaintWithError = new TaintednessWithError(
			$this->getTaintednessPhanObj( $variableObj ),
			self::getCausedByRawCloneOrEmpty( $variableObj ),
			self::getMethodLinksCloneOrEmpty( $variableObj )
		);
		$this->setCachedData( $node );
	}

	/**
	 * If we hardcode taintedness for the given var name, return that taintedness; return null otherwise.
	 * This is currently used for superglobals, since they're always tainted, regardless of whether they're in
	 * the current scope: `function foo() use ($argv)` puts $argv in the local scope, but it retains its
	 * taintedness (see test closure2).
	 *
	 * @param string $varName
	 * @return Taintedness|null
	 */
	private function getHardcodedTaintednessForVar( string $varName ): ?Taintedness {
		switch ( $varName ) {
			case '_GET':
			case '_POST':
			case 'argc':
			case 'argv':
			case 'http_response_header':
			case '_COOKIE':
			case '_REQUEST':
			// It's not entirely clear what $_ENV and $_SESSION should be considered
			case '_ENV':
			case '_SESSION':
			// Hopefully we don't need to specify all keys for $_SERVER...
			case '_SERVER':
				return Taintedness::newTainted();
			case '_FILES':
				$ret = Taintedness::newSafe();
				$ret->addKeysTaintedness( SecurityCheckPlugin::YES_TAINT );
				$elTaint = Taintedness::newFromArray( [
					'name' => Taintedness::newTainted(),
					'type' => Taintedness::newTainted(),
					'tmp_name' => Taintedness::newSafe(),
					'error' => Taintedness::newSafe(),
					'size' => Taintedness::newSafe(),
				] );
				// Use 'null' as fake offset to set unknownDims
				$ret->setOffsetTaintedness( null, $elTaint );
				return $ret;
			case 'GLOBALS':
				// Ideally this would recurse properly, but hopefully nobody is using $GLOBALS in complex ways
				// that wouldn't be covered by this approximation.

				$filesTaintedness = $this->getHardcodedTaintednessForVar( '_FILES' );
				assert( $filesTaintedness !== null );
				return Taintedness::newFromArray( [
					'_GET' => Taintedness::newTainted(),
					'_POST' => Taintedness::newTainted(),
					'_SERVER' => Taintedness::newTainted(),
					'_COOKIE' => Taintedness::newTainted(),
					'_SESSION' => Taintedness::newTainted(),
					'_REQUEST' => Taintedness::newTainted(),
					'_ENV' => Taintedness::newTainted(),
					'_FILES' => $filesTaintedness,
					'GLOBALS' => Taintedness::newTainted()
				] );
			default:
				return null;
		}
	}

	/**
	 * A global declaration. Assume most globals are untainted.
	 *
	 * @param Node $node
	 */
	public function visitGlobal( Node $node ): void {
		assert( isset( $node->children['var'] ) && $node->children['var']->kind === \ast\AST_VAR );

		$varName = $node->children['var']->children['name'];
		if ( !is_string( $varName ) || !$this->context->getScope()->hasVariableWithName( $varName ) ) {
			// Something like global $$indirectReference; or the variable wasn't created somehow
			return;
		}
		// Copy taintedness data from the actual global into the scoped clone
		$gvar = $this->context->getScope()->getVariableByName( $varName );
		if ( !$gvar instanceof GlobalVariable ) {
			// Likely a superglobal, nothing to do.
			return;
		}
		$actualGlobal = $gvar->getElement();
		self::setTaintednessRaw( $gvar, self::getTaintednessRawClone( $actualGlobal ) ?: Taintedness::newSafe() );
		self::setCausedByRaw( $gvar, self::getCausedByRawCloneOrEmpty( $actualGlobal ) );
		self::setMethodLinks( $gvar, self::getMethodLinksCloneOrEmpty( $actualGlobal ) );
	}

	/**
	 * Set the taint of the function based on what's returned
	 *
	 * This attempts to match the return value up to the argument
	 * to figure out which argument might taint the function. This won't
	 * work in complex cases though.
	 *
	 * @param Node $node
	 */
	public function visitReturn( Node $node ): void {
		if ( !$this->context->isInFunctionLikeScope() ) {
			// E.g. a file that can be included.
			$this->setCurTaintUnknown();
			$this->setCachedData( $node );
			return;
		}

		$curFunc = $this->context->getFunctionLikeInScope( $this->code_base );

		$this->setFuncTaintFromReturn( $node, $curFunc );

		if ( $node->children['expr'] instanceof Node ) {
			$collector = new ReturnObjectsCollectVisitor( $this->code_base, $this->context );
			self::addRetObjs( $curFunc, $collector->collectFromNode( $node ) );
		}
	}

	/**
	 * @param Node $node
	 * @param FunctionInterface $func
	 */
	private function setFuncTaintFromReturn( Node $node, FunctionInterface $func ): void {
		assert( $node->kind === \ast\AST_RETURN );
		$retExpr = $node->children['expr'];
		$retTaintednessWithError = $this->getTaintedness( $retExpr );
		// Ensure we don't transmit any EXEC flag.
		$retTaintedness = $retTaintednessWithError->getTaintedness()->withOnly( SecurityCheckPlugin::ALL_TAINT );
		if ( !$retExpr instanceof Node ) {
			assert( $retTaintedness->isSafe() );
			$this->ensureFuncTaintIsSet( $func );
			return;
		}

		$overallFuncTaint = $retTaintedness;
		// Note, it's important that we only use the real type here (e.g. from typehints) and NOT
		// the PHPDoc type, as it may be wrong.
		$retTaintMask = $this->getTaintMaskForType( $func->getRealReturnType() );
		if ( $retTaintMask !== null ) {
			$overallFuncTaint->keepOnly( $retTaintMask->get() );
		}

		$paramTaint = new FunctionTaintedness( $overallFuncTaint );
		$funcError = new FunctionCausedByLines();

		$links = $retTaintednessWithError->getMethodLinks();
		$retError = $retTaintednessWithError->getError();
		// Note, not forCaller, as that doesn't see variadic parameters
		$calleeParamList = $func->getParameterList();
		foreach ( $calleeParamList as $i => $param ) {
			$presTaint = $retTaintMask === null || !$retTaintMask->isSafe()
				? $links->asPreservedTaintednessForFuncParam( $func, $i )
				: PreservedTaintedness::newEmpty();
			$paramError = $retError->asFilteredForFuncAndParam( $func, $i );
			if ( $param->isVariadic() ) {
				$paramTaint->setVariadicParamPreservedTaint( $i, $presTaint );
				$funcError->setVariadicParamPreservedLines( $i, $paramError );
			} else {
				$paramTaint->setParamPreservedTaint( $i, $presTaint );
				$funcError->setParamPreservedLines( $i, $paramError );
			}
		}

		$funcError->setGenericLines( $retError->getLinesForGenericReturn() );
		$this->addFuncTaint( $func, $paramTaint );
		$newFuncTaint = self::getFuncTaint( $func );
		assert( $newFuncTaint !== null );
		$this->maybeAddFuncError( $func, null, $paramTaint, $newFuncTaint, $links );
		// Note: adding the error after setting the taintedness means that the return line comes before
		// the other lines
		$this->mergeFuncError( $func, $funcError, $newFuncTaint );
	}

	/**
	 * @param Node $node
	 */
	public function visitArray( Node $node ): void {
		$curTaint = Taintedness::newSafe();
		$curError = new CausedByLines();
		$links = MethodLinks::newEmpty();
		// Current numeric key in the array
		$curNumKey = 0;
		foreach ( $node->children as $child ) {
			if ( $child === null ) {
				// Happens for list( , $x ) = foo()
				continue;
			}
			if ( $child->kind === \ast\AST_UNPACK ) {
				// PHP 7.4's in-place unpacking.
				// TODO Do something?
				continue;
			}
			assert( $child->kind === \ast\AST_ARRAY_ELEM );
			$key = $child->children['key'];
			$keyTaintAll = $this->getTaintedness( $key );
			$keyTaint = $keyTaintAll->getTaintedness();
			$value = $child->children['value'];
			$valTaintAll = $this->getTaintedness( $value );
			$valTaint = $valTaintAll->getTaintedness();
			$sqlTaint = SecurityCheckPlugin::SQL_TAINT;

			if (
				( $keyTaint->has( $sqlTaint ) ) || (
					( $key === null || $this->nodeCanBeIntKey( $key ) )
					&& $valTaint->has( $sqlTaint )
					&& $this->nodeCanBeString( $value )
				)
			) {
				$curTaint->add( SecurityCheckPlugin::SQL_NUMKEY_TAINT );
			}
			// FIXME This will fail with in-place spread and when some numeric keys are specified
			//  explicitly (at least).
			$offset = $key ?? $curNumKey++;
			$offset = $this->resolveOffset( $offset );
			$curTaint->setOffsetTaintedness( $offset, $valTaint );
			$curTaint->addKeysTaintedness( $keyTaint->get() );
			$curError->mergeWith( $keyTaintAll->getError() );
			$curError->mergeWith( $valTaintAll->getError() );
			$links->mergeWith( $keyTaintAll->getMethodLinks()->asCollapsed() );
			$links->setAtDim( $offset, $valTaintAll->getMethodLinks() );
		}
		$this->curTaintWithError = new TaintednessWithError( $curTaint, $curError, $links );
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitClassConst( Node $node ): void {
		$this->setCurTaintSafe();
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitConst( Node $node ): void {
		// We are going to assume nobody is doing stupid stuff like
		// define( "foo", $_GET['bar'] );
		$this->setCurTaintSafe();
		$this->setCachedData( $node );
	}

	/**
	 * The :: operator (for props)
	 * @param Node $node
	 */
	public function visitStaticProp( Node $node ): void {
		$prop = $this->getPropFromNode( $node );
		if ( !$prop ) {
			$this->setCurTaintUnknown();
			return;
		}
		$this->curTaintWithError = new TaintednessWithError(
			$this->getTaintednessPhanObj( $prop ),
			self::getCausedByRawCloneOrEmpty( $prop ),
			self::getMethodLinksCloneOrEmpty( $prop )
		);
		$this->setCachedData( $node );
	}

	/**
	 * The -> operator (when not a method call)
	 * @param Node $node
	 */
	public function visitProp( Node $node ): void {
		$nodeExpr = $node->children['expr'];
		if ( !$nodeExpr instanceof Node ) {
			// Syntax error.
			$this->setCurTaintSafe();
			return;
		}

		// If the LHS expr can potentially be a stdClass, merge in its taintedness as well.
		// TODO Improve this (should similar to array offsets)
		$foundStdClass = false;
		$exprType = $this->getNodeType( $nodeExpr );
		$stdClassType = FullyQualifiedClassName::getStdClassFQSEN()->asType();
		if ( $exprType && $exprType->hasType( $stdClassType ) ) {
			$exprTaint = clone $this->getTaintedness( $nodeExpr );
			$this->curTaintWithError = new TaintednessWithError(
				$exprTaint->getTaintedness(),
				$exprTaint->getError(),
				// TODO Links?
				MethodLinks::newEmpty()
			);
			$foundStdClass = true;
		}

		$prop = $this->getPropFromNode( $node );
		if ( !$prop ) {
			if ( !$foundStdClass ) {
				$this->setCurTaintUnknown();
			}
			$this->setCachedData( $node );
			return;
		}

		$objTaint = $this->getTaintednessPhanObj( $prop );
		$objError = self::getCausedByRawCloneOrEmpty( $prop );
		$objLinks = self::getMethodLinksCloneOrEmpty( $prop );

		if ( $foundStdClass ) {
			$newTaint = $this->curTaintWithError->getTaintedness()
				->asMergedWith( $objTaint->without( SecurityCheckPlugin::UNKNOWN_TAINT ) );
			$this->curTaintWithError = new TaintednessWithError(
				$newTaint,
				$this->curTaintWithError->getError()->asMergedWith( $objError ),
				$this->curTaintWithError->getMethodLinks()->asMergedWith( $objLinks )
			);
		} else {
			$this->curTaintWithError = new TaintednessWithError( $objTaint, $objError, $objLinks );
		}

		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitNullsafeProp( Node $node ): void {
		$this->visitProp( $node );
	}

	/**
	 * Ternary operator.
	 * @param Node $node
	 */
	public function visitConditional( Node $node ): void {
		if ( $node->children['true'] === null ) {
			// $foo ?: $bar;
			$trueTaint = $this->getTaintedness( $node->children['cond'] );
		} else {
			$trueTaint = $this->getTaintedness( $node->children['true'] );
		}
		$falseTaint = $this->getTaintedness( $node->children['false'] );
		$this->curTaintWithError = $trueTaint->asMergedWith( $falseTaint );
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitName( Node $node ): void {
		$this->setCurTaintSafe();
	}

	/**
	 * This is e.g. for class X implements Name,List
	 *
	 * @param Node $node
	 */
	public function visitNameList( Node $node ): void {
		$this->setCurTaintSafe();
	}

	/**
	 * @param Node $node
	 */
	public function visitUnaryOp( Node $node ): void {
		// ~ and @ are the only two unary ops
		// that can preserve taint (others cast bool or int)
		$unsafe = [
			\ast\flags\UNARY_BITWISE_NOT,
			\ast\flags\UNARY_SILENCE
		];
		if ( in_array( $node->flags, $unsafe, true ) ) {
			$this->curTaintWithError = clone $this->getTaintedness( $node->children['expr'] );
		} else {
			$this->setCurTaintSafe();
		}
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitPostInc( Node $node ): void {
		$this->analyzeIncOrDec( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitPreInc( Node $node ): void {
		$this->analyzeIncOrDec( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitPostDec( Node $node ): void {
		$this->analyzeIncOrDec( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitPreDec( Node $node ): void {
		$this->analyzeIncOrDec( $node );
	}

	/**
	 * Handles all post/pre-increment/decrement operators. They have no effect on the
	 * taintedness of a variable.
	 *
	 * @param Node $node
	 */
	private function analyzeIncOrDec( Node $node ): void {
		$this->curTaintWithError = clone $this->getTaintedness( $node->children['var'] );
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitCast( Node $node ): void {
		// Casting between an array and object maintains
		// taint. Casting an object to a string calls __toString().
		// Future TODO: handle the string case properly.
		$dangerousCasts = [
			\ast\flags\TYPE_STRING,
			\ast\flags\TYPE_ARRAY,
			\ast\flags\TYPE_OBJECT
		];

		if ( !in_array( $node->flags, $dangerousCasts, true ) ) {
			$this->setCurTaintSafe();
		} else {
			$exprTaint = $this->getTaintedness( $node->children['expr'] );
			// Note, casting deletes shapes.
			$this->curTaintWithError = new TaintednessWithError(
				$exprTaint->getTaintedness()->asCollapsed(),
				$exprTaint->getError(),
				$exprTaint->getMethodLinks()->asCollapsed()
			);
		}
		$this->setCachedData( $node );
	}

	/**
	 * The taint is the taint of all the child elements
	 *
	 * @param Node $node
	 */
	public function visitEncapsList( Node $node ): void {
		$this->curTaintWithError = TaintednessWithError::newEmpty();
		foreach ( $node->children as $child ) {
			$this->curTaintWithError->mergeWith( $this->getTaintedness( $child ) );
		}
		$this->setCachedData( $node );
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitIsset( Node $node ): void {
		$this->setCurTaintSafe();
	}

	/**
	 * Visits calls to empty(), which is always safe
	 *
	 * @param Node $node
	 */
	public function visitEmpty( Node $node ): void {
		$this->setCurTaintSafe();
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitMagicConst( Node $node ): void {
		$this->setCurTaintSafe();
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitInstanceOf( Node $node ): void {
		$this->setCurTaintSafe();
	}

	/**
	 * @param Node $node
	 */
	public function visitMatch( Node $node ): void {
		$this->curTaintWithError = TaintednessWithError::newEmpty();
		// Based on UnionTypeVisitor
		foreach ( $node->children['stmts']->children as $armNode ) {
			// It sounds a bit weird to have to call this ourselves, but aight.
			if ( !BlockExitStatusChecker::willUnconditionallyThrowOrReturn( $armNode ) ) {
				// Note, we're straight using the expr to avoid implementing visitMatchArm
				$this->curTaintWithError->mergeWith( $this->getTaintedness( $armNode->children['expr'] ) );
			}
		}

		$this->setCachedData( $node );
	}
}
