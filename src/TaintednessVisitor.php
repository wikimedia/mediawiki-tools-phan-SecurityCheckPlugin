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
use Exception;
use Phan\Analysis\BlockExitStatusChecker;
use Phan\AST\ContextNode;
use Phan\CodeBase;
use Phan\Debug;
use Phan\Exception\CodeBaseException;
use Phan\Exception\IssueException;
use Phan\Exception\NodeException;
use Phan\Language\Context;
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

	/** @var Taintedness|null */
	protected $curTaint;

	/**
	 * @var array
	 * @phan-var list<array{0:Taintedness,1:string}>
	 */
	protected $curError;

	/** @var MethodLinks */
	protected $curLinks;

	/**
	 * @inheritDoc
	 * @param Taintedness|null &$taint
	 * @param array &$taintError
	 * @phan-param list<array{0:Taintedness,1:string}> $taintError
	 */
	public function __construct(
		CodeBase $code_base,
		Context $context,
		Taintedness &$taint = null,
		array &$taintError = [],
		MethodLinks &$methodLinks = null
	) {
		parent::__construct( $code_base, $context );
		$this->curTaint =& $taint;
		$this->curError =& $taintError;
		$methodLinks = $methodLinks ?? new MethodLinks;
		$this->curLinks =& $methodLinks;
	}

	/**
	 * Cache taintedness data in an AST node. Ideally we'd want this to happen at the end of __invoke, but phan
	 * calls visit* methods by name, so that doesn't work.
	 * @param Node $node
	 */
	private function setCachedData( Node $node ): void {
		// @phan-suppress-next-line PhanUndeclaredProperty
		$node->taint = new TaintednessWithError( $this->curTaint, $this->curError, $this->curLinks );
	}

	/**
	 * Sets $this->curTaint to INAPPLICABLE. Shorthand to filter the usages of curTaint.
	 * @note This shouldn't usually be cached, because computing is not expensive (time-wise) but can
	 * take quite a lot of memory.
	 */
	private function setCurTaintInapplicable(): void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * Sets $this->curTaint to UNKNOWN. Shorthand to filter the usages of curTaint.
	 */
	private function setCurTaintUnknown(): void {
		$this->curTaint = Taintedness::newUnknown();
	}

	/**
	 * Generic visitor when we haven't defined a more specific one.
	 *
	 * @param Node $node
	 */
	public function visit( Node $node ): void {
		// This method will be called on all nodes for which
		// there is no implementation of its kind visitor.

		// To see what kinds of nodes are passing through here,
		// you can run `Debug::printNode($node)`.
		# Debug::printNode( $node );
		$this->debug( __METHOD__, "unhandled case " . Debug::nodeName( $node ) );
		$this->setCurTaintUnknown();
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitClosure( Node $node ): void {
		// We cannot use getFunctionLikeInScope for closures
		$closureFQSEN = FullyQualifiedFunctionName::fromClosureInContext( $this->context, $node );

		if ( $this->code_base->hasFunctionWithFQSEN( $closureFQSEN ) ) {
			$func = $this->code_base->getFunctionByFQSEN( $closureFQSEN );
			$this->curTaint = $this->analyzeFunctionLike( $func );
		} else {
			$this->debug( __METHOD__, 'closure doesn\'t exist' );
			$this->setCurTaintInapplicable();
		}
		$this->setCachedData( $node );
	}

	/**
	 * These are the vars passed to closures via use(). Nothing special to do, the variables
	 * themselves are already handled in visitVar.
	 *
	 * @param Node $node
	 */
	public function visitClosureVar( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * The 'use' keyword for closures. The variables inside it are handled in visitClosureVar
	 *
	 * @param Node $node
	 */
	public function visitClosureUses( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitFuncDecl( Node $node ): void {
		$func = $this->context->getFunctionLikeInScope( $this->code_base );
		$this->curTaint = $this->analyzeFunctionLike( $func );
		$this->setCachedData( $node );
	}

	/**
	 * Visit a method declaration
	 *
	 * @param Node $node
	 */
	public function visitMethod( Node $node ): void {
		$method = $this->context->getFunctionLikeInScope( $this->code_base );
		$this->curTaint = $this->analyzeFunctionLike( $method );
		$this->setCachedData( $node );
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
	 * At this point we should have already hit a return statement
	 * so if we haven't yet, mark this function as no taint.
	 *
	 * @param FunctionInterface $func The func to analyze, or null to retrieve
	 *   it from the context.
	 * @return Taintedness
	 */
	private function analyzeFunctionLike( FunctionInterface $func ): Taintedness {
		if (
			self::getFuncTaint( $func ) === null &&
			!SecurityCheckPlugin::$pluginInstance->builtinFuncHasTaint( $func->getFQSEN() ) &&
			$this->getDocBlockTaintOfFunc( $func ) === null &&
			!$func->hasYield() &&
			!$func->hasReturn()
		) {
			// At this point, if func exec's stuff, funcTaint
			// should already be set.

			// So we have a func with no yield, return and no
			// dangerous side effects. Which seems odd, since
			// what's the point, but mark it as safe.

			// FIXME: In the event that the method stores its arg
			// to a class prop, and that class prop gets output later
			// somewhere else - the exec status of this won't be detected
			// until later, so setting this to NO_TAINT here might miss
			// some issues in the inbetween period.
			$this->setFuncTaint( $func, new FunctionTaintedness( Taintedness::newSafe() ) );
		}
		return Taintedness::newInapplicable();
	}

	// No-ops we ignore.
	// separate methods so we can use visit to output debugging
	// for anything we miss.

	/**
	 * @param Node $node
	 */
	public function visitStmtList( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitUseElem( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitType( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitArgList( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitParamList( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @note Params should be handled in PreTaintednessVisitor
	 * @param Node $node
	 */
	public function visitParam( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitClass( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitClassConstDecl( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitConstDecl( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitIf( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitIfElem( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitThrow( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * Actual property declaration is PropElem
	 * @param Node $node
	 */
	public function visitPropDecl( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitConstElem( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitUse( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitUseTrait( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitBreak( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitContinue( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitGoto( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitCatch( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitNamespace( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitSwitch( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitSwitchCase( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitWhile( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitDoWhile( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitFor( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitSwitchList( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * This is e.g. the list of expressions inside the for condition
	 *
	 * @param Node $node
	 */
	public function visitExprList( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitUnset( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitTry( Node $node ): void {
		$this->setCurTaintInapplicable();
	}

	/**
	 * A => B
	 * @param Node $node
	 */
	public function visitArrayElem( Node $node ): void {
		// Key and value are handled in visitArray()
		$this->setCurTaintInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitClone( Node $node ): void {
		// @todo This should first check the __clone method, acknowledge its side effects
		// (probably via handleMethodCall), and *then* return the taintedness of the cloned
		// item. But finding the __clone definition might be hard...
		$val = $this->getTaintedness( $node->children['expr'] );
		$this->curTaint = clone $val->getTaintedness();
		$this->curError = $val->getError();
		$this->curLinks = clone $val->getMethodLinks();
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
			$this->setCurTaintInapplicable();
			return;
		}
		$rhs = $node->children['expr'];
		$lhsTaintedness = $this->getTaintedness( $lhs );
		$rhsTaintedness = $this->getTaintedness( $rhs );

		if ( $node->flags === \ast\flags\BINARY_ADD ) {
			// Sanity: using `+=` should restrict the list of possible LHS nodes
			static $allowedLHS = [ \ast\AST_VAR, \ast\AST_DIM, \ast\AST_PROP, \ast\AST_STATIC_PROP ];
			if ( !in_array( $lhs->kind, $allowedLHS, true ) ) {
				// Probably a syntax error.
				$this->debug( __METHOD__, 'Unexpected LHS: ' . Debug::nodeName( $lhs ) );
				$this->setCurTaintInapplicable();
				return;
			}
		}

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
		// TODO We don't need $allowClearLHSData if we merge caused-by lines and links from the LHS now.
		$allowClearLHSData = false;
		$this->curTaint = $this->doVisitAssign(
			$lhs,
			$rhs,
			$lhsTaintedness,
			$rhsTaintedness,
			$allRHSTaint,
			$allowClearLHSData
		);
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
		$this->setTaintednessOld( $var, Taintedness::newSafe(), false );
		$this->curTaint = Taintedness::newInapplicable();
		$this->setCachedData( $node );
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
			$this->setCurTaintInapplicable();
			return;
		}
		$rhs = $node->children['expr'];

		$lhsTaintedness = $this->getTaintedness( $lhs );
		$rhsTaintedness = $this->getTaintedness( $rhs );
		$allRHSTaint = clone $rhsTaintedness->getTaintedness();
		$allowClearLHSData = true;

		$this->curTaint = $this->doVisitAssign(
			$lhs,
			$rhs,
			$lhsTaintedness,
			$rhsTaintedness,
			$allRHSTaint,
			$allowClearLHSData
		);
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $lhs
	 * @param Node|mixed $rhs
	 * @param TaintednessWithError $lhsTaintednessWithError
	 * @param TaintednessWithError $rhsTaintednessWithError
	 * @param Taintedness $allRHSTaint
	 * @param bool $allowClearLHSData
	 * @return Taintedness
	 */
	private function doVisitAssign(
		Node $lhs,
		$rhs,
		TaintednessWithError $lhsTaintednessWithError,
		TaintednessWithError $rhsTaintednessWithError,
		Taintedness $allRHSTaint,
		bool $allowClearLHSData
	): Taintedness {
		$rhsTaintedness = $rhsTaintednessWithError->getTaintedness();
		if ( $lhs->kind === \ast\AST_DIM ) {
			$this->maybeAddNumkeyOnAssignmentLHS( $lhs, $rhs, $rhsTaintedness, $allRHSTaint );
		}

		// If we're assigning to a variable we know will be output later
		// raise an issue now.
		// We only want to give a warning if we are adding new taint to the
		// variable. If the variable is already tainted, no need to retaint.
		// Otherwise, this could result in a variable basically tainting itself.
		// TODO: Additionally, we maybe consider skipping this when in
		// branch scope and variable is not pass by reference.
		// @fixme Is this really necessary? It doesn't seem helpful for local variables,
		// and it doesn't handle props or globals.
		// TODO: This should probably be moved to setTaintednessForAssignmentNode
		$lhsTaintedness = $lhsTaintednessWithError->getTaintedness();
		$adjustedRHS = $rhsTaintedness->withoutObj( $lhsTaintedness );
		$this->maybeEmitIssue(
			$lhsTaintedness,
			$adjustedRHS,
			"Assigning a tainted value to a variable that later does something unsafe with it{DETAILS}",
			/** @phan-return array{0:string} */
			function () use ( $lhsTaintednessWithError ): array {
				return [ $this->getStringTaintLine( $lhsTaintednessWithError->getError(), null ) ];
			}
		);

		$vis = new TaintednessAssignVisitor(
			$this->code_base,
			$this->context,
			$allRHSTaint,
			$rhsTaintednessWithError,
			$allowClearLHSData
		);
		$vis( $lhs );
		return $allRHSTaint;
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
			$this->curTaint = Taintedness::newSafe();
			$this->setCachedData( $node );
			return;
		}
		$leftTaint = $this->getTaintedness( $lhs );
		$rightTaint = $this->getTaintedness( $rhs );
		$this->curTaint = $this->getBinOpTaint(
			$leftTaint->getTaintedness(),
			$rightTaint->getTaintedness(),
			$node->flags,
			$mask
		);
		$this->curError = self::mergeCausedByLines( $leftTaint->getError(), $rightTaint->getError() );
		$this->curLinks = $leftTaint->getMethodLinks()->asMergedWith( $rightTaint->getMethodLinks() );
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
			$combinedTaint = $leftTaint->withObj( $rightTaint )->asCollapsed()->withOnly( $mask );
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
			$this->curTaint = Taintedness::newSafe();
			$this->setCachedData( $node );
			return;
		}
		$nodeTaint = $this->getTaintednessNode( $varNode );
		if ( $node->children['dim'] === null ) {
			// This should only happen in assignments: $x[] = 'foo'. Just return
			// the taint of the whole object.
			$this->curTaint = clone $nodeTaint->getTaintedness();
			$this->curError = $nodeTaint->getError();
			$this->setCachedData( $node );
			return;
		}
		$offset = $this->resolveOffset( $node->children['dim'] );
		$this->curTaint = clone $nodeTaint->getTaintedness()->getTaintednessForOffsetOrWhole( $offset );
		$this->curError = $nodeTaint->getError();
		$this->curLinks = $nodeTaint->getMethodLinks()->getForDim( $offset );
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
		$this->curTaint = Taintedness::newTainted();
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
		$this->curTaint = Taintedness::newSafe();
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
		$this->curTaint = Taintedness::newSafe();
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
			/** @phan-return array{0:string} */
			function () use ( $exprTaint, $sinkTaint ): array {
				return [ $this->getStringTaintLine( $exprTaint->getError(), $sinkTaint ) ];
			}
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
			$this->setCurTaintInapplicable();
			return;
		}
		if ( $node->children['class']->kind === \ast\AST_NAME ) {
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
				$this->handleMethodCall(
					$constructor,
					$constructor->getFQSEN(),
					$node->children['args']->children,
					false
				);
			} catch ( NodeException | CodeBaseException | IssueException $e ) {
				$this->debug( __METHOD__, 'constructor doesn\'t exist: ' . $this->getDebugInfo( $e ) );
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

			$clazzesCount = count( $clazzes );
			if ( $clazzesCount !== 1 ) {
				// TODO None or too many, we give up for now
				$this->debug( __METHOD__, "got $clazzesCount, giving up" );
				$this->setCurTaintUnknown();
				$this->setCachedData( $node );
				return;
			}
			$clazz = $clazzes[0];
			try {
				$toString = $clazz->getMethodByName( $this->code_base, '__toString' );
			} catch ( CodeBaseException $_ ) {
				// There is no __toString(), then presumably the object can't be outputted, so should be safe.
				// $this->debug( __METHOD__, "no __toString() in $clazz" );
				$this->curTaint = Taintedness::newSafe();
				$this->setCachedData( $node );
				return;
			}

			$this->curTaint = $this->handleMethodCall(
				$toString,
				$toString->getFQSEN(),
				[] // __toString() has no args
			)->getTaintedness();
		} else {
			$this->debug( __METHOD__, "cannot understand new" );
			$this->setCurTaintUnknown();
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

		$args = $node->children['args']->children;
		$this->curTaint = Taintedness::newSafe();
		foreach ( $funcs as $func ) {
			// No point in analyzing abstract function declarations
			if ( !$func instanceof FunctionLikeDeclarationType ) {
				$callTaint = $this->handleMethodCall( $func, $func->getFQSEN(), $args );
				$this->curTaint->mergeWith( $callTaint->getTaintedness() );
				$this->curError = self::mergeCausedByLines( $this->curError, $callTaint->getError() );
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
	 * @param Node $node
	 * @param string $caller
	 * @return iterable<mixed,FunctionInterface>
	 */
	private function getFuncsFromNode( Node $node, $caller = __METHOD__ ): iterable {
		$logError = function ( Exception $e ) use ( $caller ): void {
			$this->debug(
				$caller,
				"FIXME complicated case not handled. Maybe func not defined. " . $this->getDebugInfo( $e )
			);
		};
		if ( $node->kind === \ast\AST_CALL ) {
			try {
				return $this->getCtxN( $node->children['expr'] )->getFunctionFromNode();
			} catch ( IssueException $e ) {
				$logError( $e );
				return [];
			}
		}

		$methodName = $node->children['method'];
		$isStatic = $node->kind === \ast\AST_STATIC_CALL;
		try {
			return [ $this->getCtxN( $node )->getMethod( $methodName, $isStatic, true ) ];
		} catch ( NodeException | CodeBaseException | IssueException $e ) {
			$logError( $e );
			return [];
		}
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
			$this->debug( __METHOD__, "FIXME: Complex variable case not handled." );
			// Debug::printNode( $node );
			$this->setCurTaintUnknown();
			return;
		}
		if ( $this->isSuperGlobal( $varName ) ) {
			$this->curTaint = $this->getTaintednessOfSuperGlobal( $varName );
			$this->setCachedData( $node );
			return;
		} elseif ( !$this->context->getScope()->hasVariableWithName( $varName ) ) {
			// Probably the var just isn't in scope yet.
			// $this->debug( __METHOD__, "No var with name \$$varName in scope (Setting Unknown taint)" );
			$this->setCurTaintUnknown();
			$this->setCachedData( $node );
			return;
		}
		$variableObj = $this->context->getScope()->getVariableByName( $varName );
		$this->curTaint = $this->getTaintednessPhanObj( $variableObj );
		$this->curError = self::getCausedByRaw( $variableObj ) ?? [];
		$this->curLinks = self::getMethodLinksCloneOrEmpty( $variableObj );
		$this->setCachedData( $node );
	}

	/**
	 * Get the taintedness of a superglobal. Note that superglobals are tainted, regardless of whether they're in
	 * the current scope: `function foo() use ($argv)` puts $argv in the local scope, but it retains its
	 * taintedness (see test closure2).
	 * @param string $varName
	 * @return Taintedness
	 */
	private function getTaintednessOfSuperGlobal( string $varName ): Taintedness {
		switch ( $varName ) {
			case '_GET':
			case '_POST':
			case 'argc':
			case 'argv':
			case 'GLOBALS':
			case 'http_response_header':
			// TODO Improve these
			case '_SERVER':
			case '_COOKIE':
			case '_SESSION':
			case '_REQUEST':
			case '_ENV':
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
			default:
				$this->debug( __METHOD__, "FIXME Unknown superglobal $varName" );
				return Taintedness::newTainted();
		}
	}

	/**
	 * A global declaration. Assume most globals are untainted.
	 *
	 * @param Node $node
	 */
	public function visitGlobal( Node $node ): void {
		assert( isset( $node->children['var'] ) && $node->children['var']->kind === \ast\AST_VAR );
		$this->setCurTaintInapplicable();
		$this->setCachedData( $node );

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
		self::setCausedByRaw( $gvar, self::getCausedByRaw( $actualGlobal ) ?: [] );
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
		$this->setCurTaintInapplicable();
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 * @param FunctionInterface $func
	 */
	private function setFuncTaintFromReturn( Node $node, FunctionInterface $func ): void {
		assert( $node->kind === \ast\AST_RETURN );
		$retExpr = $node->children['expr'];
		$retTaintednessWithError = $this->getTaintedness( $retExpr );
		// The EXEC taint flags have different meaning for variables and
		// functions. We don't want to transmit exec flags here.
		// Keep PRESERVE, though, as that means that a parameter is being essentially passed through
		$keepMask = SecurityCheckPlugin::ALL_TAINT | SecurityCheckPlugin::PRESERVE_TAINT;
		$retTaintedness = $retTaintednessWithError->getTaintedness()->withOnly( $keepMask );
		if ( !$retExpr instanceof Node ) {
			assert( $retTaintedness->isSafe() );
			$this->setFuncTaint( $func, new FunctionTaintedness( $retTaintedness ) );
			return;
		}

		$paramTaint = new FunctionTaintedness( Taintedness::newUnknown() );

		$links = $retTaintednessWithError->getMethodLinks();
		// Note, not forCaller, as that doesn't see variadic parameters
		$calleeParamList = $func->getParameterList();
		foreach ( $calleeParamList as $i => $param ) {
			$presTaint = $links->asPreservedTaintednessForFuncParam( $func, $i );
			if ( $param->isVariadic() ) {
				$paramTaint->setVariadicParamPreservedTaint( $i, $presTaint );
			} else {
				$paramTaint->setParamPreservedTaint( $i, $presTaint );
			}
		}

		$paramTaint->setOverall( $retTaintedness->without( SecurityCheckPlugin::PRESERVE_TAINT ) );
		$this->setFuncTaint( $func, $paramTaint );
		$this->mergeTaintError( $func, $retTaintednessWithError->getError() );
	}

	/**
	 * @todo Handle array shape mutations (e.g. unset, array_shift, array_pop, etc.)
	 * @param Node $node
	 */
	public function visitArray( Node $node ): void {
		$curTaint = Taintedness::newSafe();
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

			if ( $valTaint->has( SecurityCheckPlugin::SQL_NUMKEY_TAINT ) ) {
				$curTaint->remove( SecurityCheckPlugin::SQL_NUMKEY_TAINT );
			}
			if (
				( $keyTaint->has( $sqlTaint ) ) ||
				( ( $key === null || $this->nodeIsInt( $key ) )
					&& ( $valTaint->has( $sqlTaint ) )
					&& $this->nodeIsString( $value ) )
			) {
				$curTaint->add( SecurityCheckPlugin::SQL_NUMKEY_TAINT );
			}
			// FIXME This will fail with in-place spread and when some numeric keys are specified
			//  explicitly (at least).
			$offset = $key ?? $curNumKey++;
			$offset = $this->resolveOffset( $offset );
			// Note that we remove numkey taint because that's only for the outer array
			$curTaint->setOffsetTaintedness( $offset, $valTaint->without( SecurityCheckPlugin::SQL_NUMKEY_TAINT ) );
			$curTaint->addKeysTaintedness( $keyTaint->get() );
			$this->curError = self::mergeCausedByLines( $this->curError, $keyTaintAll->getError() );
			$this->curError = self::mergeCausedByLines( $this->curError, $valTaintAll->getError() );
			$links->mergeWith( $keyTaintAll->getMethodLinks()->asCollapsed() );
			$links->setAtDim( $offset, $valTaintAll->getMethodLinks() );
		}
		$this->curTaint = $curTaint;
		$this->curLinks = $links;
		$this->setCachedData( $node );
	}

	/**
	 * A foreach() loop
	 *
	 * The variable from the loop condition has its taintedness
	 * transferred in TaintednessLoopVisitor
	 * @param Node $node
	 */
	public function visitForeach( Node $node ): void {
		// This is handled by TaintednessLoopVisitor.
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * @param Node $node
	 */
	public function visitClassConst( Node $node ): void {
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * @param Node $node
	 */
	public function visitConst( Node $node ): void {
		// We are going to assume nobody is doing stupid stuff like
		// define( "foo", $_GET['bar'] );
		$this->curTaint = Taintedness::newSafe();
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
		$this->curTaint = $this->getTaintednessPhanObj( $prop );
		$this->curError = self::getCausedByRaw( $prop ) ?? [];
		$this->curLinks = self::getMethodLinksCloneOrEmpty( $prop );
		$this->setCachedData( $node );
	}

	/**
	 * The -> operator (when not a method call)
	 * @param Node $node
	 */
	public function visitProp( Node $node ): void {
		$prop = $this->getPropFromNode( $node );
		if ( !$prop ) {
			if (
				is_object( $node->children['expr'] ) &&
				$node->children['expr']->kind === \ast\AST_VAR &&
				$node->children['expr']->children['name'] === 'row'
			) {
				// Almost certainly a MW db result.
				// FIXME this case isn't fully handled.
				// Stuff from db probably not escaped. Most of the time.
				// Don't include serialize here due to high false positives
				// Even though unserializing stuff from db can be very
				// problematic if user can ever control.
				// FIXME This is MW specific so should not be
				// in the generic visitor.
				$taint = SecurityCheckPlugin::YES_TAINT & ~SecurityCheckPlugin::SERIALIZE_TAINT;
				$this->curTaint = new Taintedness( $taint );
				$this->setCachedData( $node );
				return;
			}
			if (
				is_object( $node->children['expr'] ) &&
				$node->children['expr']->kind === \ast\AST_VAR &&
				is_string( $node->children['expr']->children['name'] ) &&
				is_string( $node->children['prop'] )
			) {
				$this->debug( __METHOD__, "Could not find Property \$" .
					$node->children['expr']->children['name'] . "->" .
					$node->children['prop']
				);
			} else {
				// FIXME, we should handle $this->foo->bar
				$this->debug( __METHOD__, 'Nested property reference' );
				# Debug::printNode( $node );
			}

			// Should this be NO_TAINT?
			$this->setCurTaintUnknown();
			$this->setCachedData( $node );
			return;
		}

		if ( $node->children['expr'] instanceof Node && $node->children['expr']->kind === \ast\AST_VAR ) {
			$variable = $this->getCtxN( $node->children['expr'] )->getVariable();
			// If the LHS is a variable and it can potentially be a stdClass, share its taintedness
			// with the property. TODO Improve this.
			$stdClassType = FullyQualifiedClassName::getStdClassFQSEN()->asType();
			if ( $variable->getUnionType()->hasType( $stdClassType ) ) {
				$this->doSetTaintedness(
					$prop,
					[],
					$this->getTaintednessPhanObj( $variable ),
					false,
					Taintedness::newSafe()
				);
				$this->mergeTaintError( $prop, $variable );
			}
		}

		$this->curTaint = $this->getTaintednessPhanObj( $prop );
		$this->curError = self::getCausedByRaw( $prop ) ?? [];
		$this->curLinks = self::getMethodLinksCloneOrEmpty( $prop );
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
		$this->curTaint = clone $trueTaint->getTaintedness()->withObj( $falseTaint->getTaintedness() );
		$this->curError = self::mergeCausedByLines( $trueTaint->getError(), $falseTaint->getError() );
		$this->curLinks = $trueTaint->getMethodLinks()->asMergedWith( $falseTaint->getMethodLinks() );
		$this->setCachedData( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitName( Node $node ): void {
		// FIXME I'm a little unclear on what a name is in php.
		// I think this means literal true, false, null
		// or a class name (The Foo part of Foo::bar())
		// Maybe other things too? Are class references always
		// untainted? Probably.

		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * This is e.g. for class X implements Name,List
	 *
	 * @param Node $node
	 */
	public function visitNameList( Node $node ): void {
		$this->curTaint = Taintedness::newSafe();
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
			$exprTaint = $this->getTaintedness( $node->children['expr'] );
			$this->curTaint = clone $exprTaint->getTaintedness();
			$this->curError = $exprTaint->getError();
			$this->curLinks = clone $exprTaint->getMethodLinks();
		} else {
			$this->curTaint = Taintedness::newSafe();
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
		$varTaint = $this->getTaintedness( $node->children['var'] );
		$this->curTaint = clone $varTaint->getTaintedness();
		$this->curError = $varTaint->getError();
		$this->curLinks = clone $varTaint->getMethodLinks();
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
			$this->curTaint = Taintedness::newSafe();
		} else {
			$exprTaint = $this->getTaintedness( $node->children['expr'] );
			$this->curTaint = clone $exprTaint->getTaintedness();
			$this->curError = $exprTaint->getError();
			$this->curLinks = clone $exprTaint->getMethodLinks();
		}
		$this->setCachedData( $node );
	}

	/**
	 * The taint is the taint of all the child elements
	 *
	 * @param Node $node
	 */
	public function visitEncapsList( Node $node ): void {
		$taint = Taintedness::newSafe();
		$links = MethodLinks::newEmpty();
		foreach ( $node->children as $child ) {
			$childTaint = $this->getTaintedness( $child );
			$taint->addObj( $childTaint->getTaintedness() );
			$this->curError = self::mergeCausedByLines( $this->curError, $childTaint->getError() );
			$links->mergeWith( $childTaint->getMethodLinks() );
		}
		$this->curTaint = $taint;
		$this->curLinks = $links;
		$this->setCachedData( $node );
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitIsset( Node $node ): void {
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * Visits calls to empty(), which is always safe
	 *
	 * @param Node $node
	 */
	public function visitEmpty( Node $node ): void {
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitMagicConst( Node $node ): void {
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitInstanceOf( Node $node ): void {
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * @param Node $node
	 */
	public function visitMatch( Node $node ): void {
		$taint = Taintedness::newSafe();
		// Based on UnionTypeVisitor
		foreach ( $node->children['stmts']->children as $armNode ) {
			// It sounds a bit weird to have to call this ourselves, but aight.
			if ( !BlockExitStatusChecker::willUnconditionallyThrowOrReturn( $armNode ) ) {
				// Note, we're straight using the expr to avoid implementing visitMatchArm
				$armTaint = $this->getTaintedness( $armNode->children['expr'] );
				$taint->mergeWith( $armTaint->getTaintedness() );
				$this->curError = self::mergeCausedByLines( $this->curError, $armTaint->getError() );
				$this->curLinks->mergeWith( $armTaint->getMethodLinks() );
			}
		}

		$this->curTaint = $taint;
		$this->setCachedData( $node );
	}
}
