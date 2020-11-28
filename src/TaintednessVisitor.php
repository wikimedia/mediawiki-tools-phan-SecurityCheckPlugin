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
use Phan\AST\ContextNode;
use Phan\CodeBase;
use Phan\Debug;
use Phan\Exception\CodeBaseException;
use Phan\Exception\FQSENException;
use Phan\Exception\IssueException;
use Phan\Exception\NodeException;
use Phan\Language\Context;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\PassByReferenceVariable;
use Phan\Language\Element\Property;
use Phan\Language\Element\TypedElementInterface;
use Phan\Language\FQSEN\FullyQualifiedClassName;
use Phan\Language\FQSEN\FullyQualifiedFunctionName;
use Phan\Language\Type\ClosureType;
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
	 * @inheritDoc
	 */
	public function __construct(
		CodeBase $code_base,
		Context $context,
		Taintedness &$taint = null
	) {
		parent::__construct( $code_base, $context );
		$this->plugin = SecurityCheckPlugin::$pluginInstance;
		$this->curTaint =& $taint;
	}

	/**
	 * Generic visitor when we haven't defined a more specific one.
	 *
	 * @param Node $node
	 */
	public function visit( Node $node ) : void {
		// This method will be called on all nodes for which
		// there is no implementation of its kind visitor.

		// To see what kinds of nodes are passing through here,
		// you can run `Debug::printNode($node)`.
		# Debug::printNode( $node );
		$this->debug( __METHOD__, "unhandled case " . Debug::nodeName( $node ) );
		$this->curTaint = Taintedness::newUnknown();
	}

	/**
	 * @param Node $node
	 */
	public function visitClosure( Node $node ) : void {
		// We cannot use getFunctionLikeInScope for closures
		$closureFQSEN = FullyQualifiedFunctionName::fromClosureInContext( $this->context, $node );

		if ( $this->code_base->hasFunctionWithFQSEN( $closureFQSEN ) ) {
			$func = $this->code_base->getFunctionByFQSEN( $closureFQSEN );
			$this->curTaint = $this->analyzeFunctionLike( $func );
		} else {
			$this->debug( __METHOD__, 'closure doesn\'t exist' );
			$this->curTaint = Taintedness::newInapplicable();
		}
	}

	/**
	 * These are the vars passed to closures via use(). Nothing special to do, the variables
	 * themselves are already handled in visitVar.
	 *
	 * @param Node $node
	 */
	public function visitClosureVar( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * The 'use' keyword for closures. The variables inside it are handled in visitClosureVar
	 *
	 * @param Node $node
	 */
	public function visitClosureUses( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitFuncDecl( Node $node ) : void {
		$func = $this->context->getFunctionLikeInScope( $this->code_base );
		$this->curTaint = $this->analyzeFunctionLike( $func );
	}

	/**
	 * Visit a method declaration
	 *
	 * @param Node $node
	 */
	public function visitMethod( Node $node ) : void {
		$method = $this->context->getFunctionLikeInScope( $this->code_base );
		$this->curTaint = $this->analyzeFunctionLike( $method );
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
	private function analyzeFunctionLike( FunctionInterface $func ) : Taintedness {
		if (
			$this->getFuncTaint( $func ) === null &&
			$this->getBuiltinFuncTaint( $func->getFQSEN() ) === null &&
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
	public function visitStmtList( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitUseElem( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitType( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitArgList( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitParamList( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @note Params should be handled in PreTaintednessVisitor
	 * @param Node $node
	 */
	public function visitParam( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitClass( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitClassConstDecl( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitConstDecl( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitIf( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitIfElem( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitThrow( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * Actual property declaration is PropElem
	 * @param Node $node
	 */
	public function visitPropDecl( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitConstElem( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitUse( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitUseTrait( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitBreak( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitContinue( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitGoto( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitCatch( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitNamespace( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitSwitch( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitSwitchCase( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitWhile( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitDoWhile( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitFor( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitSwitchList( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * This is e.g. the list of expressions inside the for condition
	 *
	 * @param Node $node
	 */
	public function visitExprList( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitUnset( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitTry( Node $node ) : void {
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @param Node $node
	 */
	public function visitClone( Node $node ) : void {
		// @todo This should first check the __clone method, acknowledge its side effects
		// (probably via handleMethodCall), and *then* return the taintedness of the cloned
		// item. But finding the __clone definition might be hard...
		$this->curTaint = $this->getTaintedness( $node->children['expr'] );
	}

	/**
	 * @param Node $node
	 */
	public function visitAssignOp( Node $node ) : void {
		$this->visitAssign( $node );
	}

	/**
	 * Also handles visitAssignOp
	 *
	 * @param Node $node
	 */
	public function visitAssign( Node $node ) : void {
		// echo __METHOD__ . $this->dbgInfo() . ' ';
		// Debug::printNode($node);

		$lhs = $node->children['var'];
		if ( !$lhs instanceof Node ) {
			// Syntax error, don't crash
			$this->curTaint = Taintedness::newInapplicable();
			return;
		}
		$rhs = $node->children['expr'];
		// Note: If there is a local variable that is a reference
		// to another non-local variable, this will probably incorrectly
		// override the taint (Pass by reference variables are handled
		// specially and should be ok).

		// Make sure $foo[2] = 0; doesn't kill taint of $foo generally.
		// Ditto for $this->bar, or props in general just in case.
		$override = $lhs->kind !== \ast\AST_DIM && $lhs->kind !== \ast\AST_PROP;

		$variableObjs = $this->getPhanObjsForNode( $lhs );

		$lhsTaintedness = $this->getTaintedness( $lhs );
		# $this->debug( __METHOD__, "Getting taint LHS = $lhsTaintedness:" );
		$rhsTaintedness = $this->getTaintedness( $rhs );
		# $this->debug( __METHOD__, "Getting taint RHS = $rhsTaintedness:" );

		if ( $node->kind === \ast\AST_ASSIGN_OP ) {
			// TODO, be more specific for different OPs
			// Expand rhs to include implicit lhs ophand.
			$rhsTaintedness->add( $lhsTaintedness );
			$override = false;
		}

		// Special case for SQL_NUMKEY_TAINT
		// If we're assigning an SQL tainted value as an array key
		// or as the value of a numeric key, then set NUMKEY taint.
		if ( $lhs->kind === \ast\AST_DIM ) {
			$dim = $lhs->children['dim'];
			if ( $rhsTaintedness->has( SecurityCheckPlugin::SQL_NUMKEY_TAINT ) ) {
				// Things like 'foo' => ['taint', 'taint']
				// are ok.
				$rhsTaintedness->remove( SecurityCheckPlugin::SQL_NUMKEY_TAINT );
			} elseif ( $rhsTaintedness->has( SecurityCheckPlugin::SQL_TAINT ) ) {
				// Checking the case:
				// $foo[1] = $sqlTainted;
				// $foo[] = $sqlTainted;
				// But ensuring we don't catch:
				// $foo['bar'][] = $sqlTainted;
				// $foo[] = [ $sqlTainted ];
				// $foo[2] = [ $sqlTainted ];
				if (
					( $dim === null || $this->nodeIsInt( $dim ) )
					&& !$this->nodeIsArray( $rhs )
					&& !( $lhs->children['expr'] instanceof Node
						&& $lhs->children['expr']->kind === \ast\AST_DIM
					)
				) {
					$rhsTaintedness->add( SecurityCheckPlugin::SQL_NUMKEY_TAINT );
				}
			}
			if ( $this->getTaintedness( $dim )->has( SecurityCheckPlugin::SQL_TAINT ) ) {
				$rhsTaintedness->add( SecurityCheckPlugin::SQL_NUMKEY_TAINT );
			}
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
		$adjustedRHS = $rhsTaintedness->without( $lhsTaintedness );
		$this->maybeEmitIssue(
			$lhsTaintedness,
			$adjustedRHS,
			"Assigning a tainted value to a variable that later does something unsafe with it{DETAILS}",
			[ $this->getOriginalTaintLine( $lhs, null ) ]
		);

		$rhsObjs = [];
		if ( is_object( $rhs ) ) {
			$rhsObjs = $this->getPhanObjsForNode( $rhs );
		}

		foreach ( $variableObjs as $variableObj ) {
			$reference = false;
			if ( $variableObj instanceof PassByReferenceVariable ) {
				$reference = true;
				$variableObj = $this->extractReferenceArgument( $variableObj );
			}
			if (
				$variableObj instanceof Property &&
				$variableObj->getClass( $this->code_base )->getFQSEN() ===
				FullyQualifiedClassName::getStdClassFQSEN()
			) {
				// Phan conflates all stdClass props, see https://github.com/phan/phan/issues/3869
				// Avoid doing the same with taintedness, as that would cause weird issues (see
				// 'stdclassconflation' test).
				// @todo Is it possible to store prop taintedness in the Variable object?
				// that would be similar to a fine-grained handling of arrays.
				continue;
			}
			// echo $this->dbgInfo() . " " . $variableObj .
			// " now merging in taintedness " . $rhsTaintedness
			// . " (previously $lhsTaintedness)\n";
			if ( $reference ) {
				$this->setRefTaintedness( $variableObj, $rhsTaintedness, $override );
			} else {
				// First try updating specific offset taint
				$dimUpdated = $this->setOffsetTaintInAssignment( $variableObj, $node, $rhsTaintedness );
				if ( !$dimUpdated ) {
					// Don't clear data if one of the objects in the RHS is the same as this object
					// in the LHS. This is especially important in conditionals e.g. tainted = tainted ?: null.
					$allowClearLHSData = !in_array( $variableObj, $rhsObjs, true );
					$this->setTaintedness( $variableObj, $rhsTaintedness, $override, $allowClearLHSData );
				}
			}

			foreach ( $rhsObjs as $rhsObj ) {
				if ( $rhsObj instanceof PassByReferenceVariable ) {
					$rhsObj = $this->extractReferenceArgument( $rhsObj );
				}
				// Only merge dependencies if there are no other
				// sources of taint. Otherwise we can potentially
				// misattribute where the taint is coming from
				// See testcase dblescapefieldset.
				$taintRHSObj = $this->getTaintednessPhanObj( $rhsObj );
				$adjTaint = $lhsTaintedness->with( $rhsTaintedness )->without( $taintRHSObj );
				if ( $adjTaint->lacks( SecurityCheckPlugin::ALL_YES_EXEC_TAINT ) ) {
					$this->mergeTaintDependencies( $variableObj, $rhsObj );
				} elseif ( !$taintRHSObj->isSafe() ) {
					$this->mergeTaintError( $variableObj, $rhsObj );
				}
			}
		}
		$this->curTaint = $rhsTaintedness;
	}

	/**
	 * For an assignment, try to update the lhs precisely using dim information for both LHS and RHS.
	 * @todo Perhaps this should be decoupled from assignments and be moved into setTaintedness
	 *
	 * @param TypedElementInterface $variableObj The object at the LHS
	 * @param Node $node The assignment node. Must be AST_ASSIGN or AST_ASSINGN_OP
	 * @param Taintedness $rhsTaint Taintedness of the RHS, adjusted with numkey etc. if necessary
	 * @return bool Whether we managed to update the dim taintedness
	 */
	private function setOffsetTaintInAssignment(
		TypedElementInterface $variableObj,
		Node $node,
		Taintedness $rhsTaint
	) : bool {
		assert( $node->kind === \ast\AST_ASSIGN || $node->kind === \ast\AST_ASSIGN_OP );
		$lhs = $node->children['var'];
		if ( $lhs->kind === \ast\AST_ARRAY ) {
			// TODO Handle arrays at the LHS
			return false;
		}
		if (
			$lhs->kind !== \ast\AST_DIM &&
			( $node->kind !== \ast\AST_ASSIGN_OP || $node->flags !== \ast\flags\BINARY_ADD )
		) {
			// If we're not assigning to a dim and we don't have an array addition,
			// setTaintedness will handle the node.
			return false;
		}

		$resolvedOffsetsLhs = [];
		$resolvedAll = true;
		$lhsDimNode = $lhs;
		while ( $lhsDimNode instanceof Node && $lhsDimNode->kind === \ast\AST_DIM ) {
			$offsetNode = $lhsDimNode->children['dim'];
			if ( $offsetNode === null ) {
				// $arr[] = 'foo'
				// Phan doesn't infer real types here, nor will we.
				$resolvedAll = false;
				$curOff = null;
			} else {
				$curOff = $this->resolveOffset( $offsetNode );
				if ( $curOff instanceof Node ) {
					$resolvedAll = false;
				}
			}
			$resolvedOffsetsLhs[] = $curOff;
			$lhsDimNode = $lhsDimNode->children['expr'];
		}
		$resolvedOffsetsLhs = array_reverse( $resolvedOffsetsLhs );

		// TODO Direct access here not ideal
		$varTaint = property_exists( $variableObj, 'taintedness' )
			? clone $variableObj->taintedness
			: Taintedness::newSafe();

		if ( $node->kind !== \ast\AST_ASSIGN_OP || $node->flags !== \ast\flags\BINARY_ADD ) {
			assert( $lhs->kind === \ast\AST_DIM );
			assert( count( $resolvedOffsetsLhs ) >= 1 );
			$offsetOverride = $node->kind !== \ast\AST_ASSIGN_OP && $resolvedAll;
			$varTaint->setTaintednessAtOffsetList( $resolvedOffsetsLhs, $rhsTaint, $offsetOverride );
		} else {
			// Special handling for A += B, where A and B can be DIM nodes
			$varTaint->applyArrayPlusAtOffsetList( $resolvedOffsetsLhs, $rhsTaint );
		}

		// We avoid overriding just for properties here (globals etc. are handled in setTaintedness).
		// Note 1: override for ASSIGN_OP is already handled above when setting the updating the taint
		// Note 2: both checks are necessary, because we may have e.g. a DIM node for $this->prop['foo']
		$dimOverride = $lhs->kind !== \ast\AST_PROP && !( $variableObj instanceof Property );
		$this->setTaintedness( $variableObj, $varTaint, $dimOverride, false, $rhsTaint );
		return true;
	}

	/**
	 * @param Node $node
	 */
	public function visitBinaryOp( Node $node ) : void {
		$lhs = $node->children['left'];
		$rhs = $node->children['right'];
		$leftTaint = $this->getTaintedness( $lhs );
		$rightTaint = $this->getTaintedness( $rhs );
		$mask = $this->getBinOpTaintMask( $node, $lhs, $rhs );
		if ( $node->flags === \ast\flags\BINARY_ADD && $mask !== SecurityCheckPlugin::NO_TAINT ) {
			// HACK: This means that a node can be array, so assume array plus
			$combinedTaint = $leftTaint->asArrayPlusWith( $rightTaint );
		} else {
			$combinedTaint = $leftTaint->with( $rightTaint )->withOnly( $mask );
		}
		$this->curTaint = $combinedTaint;
	}

	/**
	 * @param Node $node
	 */
	public function visitDim( Node $node ) : void {
		$varNode = $node->children['expr'];
		if ( !$varNode instanceof Node ) {
			// Accessing offset of a string literal
			$this->curTaint = Taintedness::newSafe();
			return;
		}
		$nodeTaint = $this->getTaintednessNode( $varNode );
		if ( $node->children['dim'] === null ) {
			// This should only happen in assignments: $x[] = 'foo'. Just return
			// the taint of the whole object.
			$this->curTaint = $nodeTaint;
			return;
		}
		$offset = $this->resolveOffset( $node->children['dim'] );
		$this->curTaint = $nodeTaint->getTaintednessForOffsetOrWhole( $offset );
	}

	/**
	 * @param Node $node
	 */
	public function visitPrint( Node $node ) : void {
		$this->visitEcho( $node );
	}

	/**
	 * This is for exit() and die(). If they're passed an argument, they behave the
	 * same as print.
	 * @param Node $node
	 */
	public function visitExit( Node $node ) : void {
		$this->visitEcho( $node );
	}

	/**
	 * Visits the backtick operator. Note that shell_exec() has a simple AST_CALL node.
	 * @param Node $node
	 */
	public function visitShellExec( Node $node ) : void {
		$taintedness = $this->getTaintedness( $node->children['expr'] );
		$shellExecTaint = new Taintedness( SecurityCheckPlugin::SHELL_EXEC_TAINT );

		$this->maybeEmitIssue(
			$shellExecTaint,
			$taintedness,
			"Backtick shell execution operator contains user controlled arg{DETAILS}",
			[ $this->getOriginalTaintLine( $node->children['expr'], $shellExecTaint ) ]
		);

		if (
			$node->children['expr'] instanceof Node &&
			$this->isSafeAssignment( $shellExecTaint, $taintedness )
		) {
			// In the event the assignment looks safe, keep track of it,
			// in case it later turns out not to be safe.
			$phanObjs = $this->getPhanObjsForNode( $node->children['expr'], [ 'return' ] );
			foreach ( $phanObjs as $phanObj ) {
				if ( $this->getPossibleFutureTaintOfElement( $phanObj )->has( $shellExecTaint->get() ) ) {
					$this->debug( __METHOD__, "Setting {$phanObj->getName()} exec due to backtick" );
					$this->markAllDependentMethodsExec(
						$phanObj,
						$shellExecTaint
					);
				}
			}
		}
		// Its unclear if we should consider this tainted or not
		$this->curTaint = Taintedness::newTainted();
	}

	/**
	 * @param Node $node
	 */
	public function visitIncludeOrEval( Node $node ) : void {
		$taintedness = $this->getTaintedness( $node->children['expr'] );
		$includeOrEvalTaint = new Taintedness( SecurityCheckPlugin::MISC_EXEC_TAINT );

		$this->maybeEmitIssue(
			$includeOrEvalTaint,
			$taintedness,
			"Argument to require, include or eval is user controlled{DETAILS}",
			[ $this->getOriginalTaintLine( $node->children['expr'], $includeOrEvalTaint ) ]
		);

		if (
			$node->children['expr'] instanceof Node &&
			$this->isSafeAssignment( $includeOrEvalTaint, $taintedness )
		) {
			// In the event the assignment looks safe, keep track of it,
			// in case it later turns out not to be safe.
			$phanObjs = $this->getPhanObjsForNode( $node->children['expr'], [ 'return' ] );
			foreach ( $phanObjs as $phanObj ) {
				if ( $this->getPossibleFutureTaintOfElement( $phanObj )->has( $includeOrEvalTaint->get() ) ) {
					$this->debug( __METHOD__, "Setting {$phanObj->getName()} exec due to require/eval" );
					$this->markAllDependentMethodsExec(
						$phanObj,
						$includeOrEvalTaint
					);
				}
			}
		}
		// Strictly speaking we have no idea if the result
		// of an eval() or require() is safe. But given that we
		// don't know, and at least in the require() case its
		// fairly likely to be safe, no point in complaining.
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * Also handles exit(), print, eval() and include() (for now).
	 *
	 * We assume a web based system, where outputting HTML via echo
	 * is bad. This will have false positives in a CLI environment.
	 *
	 * @param Node $node
	 */
	public function visitEcho( Node $node ) : void {
		$echoTaint = new Taintedness( SecurityCheckPlugin::HTML_EXEC_TAINT );
		$echoedExpr = $node->children['expr'];
		$taintedness = $this->getTaintedness( $echoedExpr );
		# $this->debug( __METHOD__, "Echoing with taint $taintedness" );

		$this->maybeEmitIssue(
			$echoTaint,
			$taintedness,
			"Echoing expression that was not html escaped{DETAILS}",
			[ $this->getOriginalTaintLine( $echoedExpr, $echoTaint ) ]
		);

		if ( $echoedExpr instanceof Node && $this->isSafeAssignment( $echoTaint, $taintedness ) ) {
			// In the event the assignment looks safe, keep track of it,
			// in case it later turns out not to be safe.
			$phanObjs = $this->getPhanObjsForNode( $echoedExpr, [ 'return' ] );
			foreach ( $phanObjs as $phanObj ) {
				if ( $this->getPossibleFutureTaintOfElement( $phanObj )->has( $echoTaint->get() ) ) {
					$this->debug( __METHOD__, "Setting {$phanObj->getName()} exec due to echo" );
					// FIXME, maybe not do this for local variables
					// since they don't have other code paths that can set them.
					$this->markAllDependentMethodsExec(
						$phanObj,
						$echoTaint
					);
				}
			}
		}
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * @param Node $node
	 */
	public function visitStaticCall( Node $node ) : void {
		$this->visitMethodCall( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitNew( Node $node ) : void {
		$ctxNode = $this->getCtxN( $node );
		if ( !$node->children['class'] instanceof Node ) {
			// Syntax error, don't crash
			$this->curTaint = Taintedness::newInapplicable();
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
					$node->children['args']->children
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
				$this->curTaint = Taintedness::newUnknown();
				return;
			}

			$clazzesCount = count( $clazzes );
			if ( $clazzesCount !== 1 ) {
				// TODO None or too many, we give up for now
				$this->debug( __METHOD__, "got $clazzesCount, giving up" );
				$this->curTaint = Taintedness::newUnknown();
				return;
			}
			$clazz = $clazzes[0];
			try {
				$toString = $clazz->getMethodByName( $this->code_base, '__toString' );
			} catch ( CodeBaseException $_ ) {
				// There is no __toString(), then presumably the object can't be outputted, so should be safe.
				$this->debug( __METHOD__, "no __toString() in $clazz" );
				$this->curTaint = Taintedness::newSafe();
				return;
			}

			$this->curTaint = $this->handleMethodCall(
				$toString,
				$toString->getFQSEN(),
				[] // __toString() has no args
			);
		} else {
			$this->debug( __METHOD__, "cannot understand new" );
			$this->curTaint = Taintedness::newUnknown();
		}
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
	public function visitMethodCall( Node $node ) : void {
		$func = $this->getFuncToAnalyze( $node );
		if ( is_string( $func ) ) {
			$this->debug( __METHOD__, $func );
			$this->curTaint = Taintedness::newUnknown();
			return;
		}
		$this->curTaint = $this->handleMethodCall(
			$func,
			$func->getFQSEN(),
			$node->children['args']->children
		);
	}

	/**
	 * @param Node $node
	 * @return FunctionInterface|string String for an error message
	 */
	private function getFuncToAnalyze( Node $node ) {
		$ctxNode = $this->getCtxN( $node );
		$isStatic = ( $node->kind === \ast\AST_STATIC_CALL );
		$isFunc = ( $node->kind === \ast\AST_CALL );
		if ( $isFunc ) {
			if ( !( $node->children['expr'] ) instanceof Node ) {
				// Likely a syntax error (see test 'weirdsyntax'), don't crash.
				return 'Likely sintax error';
			}
			if ( $node->children['expr']->kind === \ast\AST_NAME ) {
				try {
					$func = $ctxNode->getFunction( $node->children['expr']->children['name'] );
				} catch ( IssueException | FQSENException $e ) {
					return "FIXME complicated case not handled. Maybe func not defined. " . $this->getDebugInfo( $e );
				}
			} elseif ( $node->children['expr']->kind === \ast\AST_VAR ) {
				// Closure
				$pobjs = $this->getPhanObjsForNode( $node->children['expr'] );
				if ( !$pobjs ) {
					return 'Closure var is not defined?';
				}
				assert( count( $pobjs ) === 1 );
				$types = $pobjs[0]->getUnionType()->getTypeSet();
				$func = null;
				foreach ( $types as $type ) {
					if ( $type instanceof ClosureType ) {
						$func = $type->asFunctionInterfaceOrNull( $this->code_base, $this->context );
					}
				}
				if ( $func === null ) {
					return 'Cannot get closure from variable.';
				}
			} else {
				return "Non-simple func call";
			}
		} else {
			$methodName = $node->children['method'];
			try {
				$func = $ctxNode->getMethod( $methodName, $isStatic );
			} catch ( NodeException | CodeBaseException | IssueException $e ) {
				return "FIXME complicated case not handled. Maybe method not defined. " . $this->getDebugInfo( $e );
			}
		}
			return $func;
	}

	/**
	 * A function call
	 *
	 * @param Node $node
	 */
	public function visitCall( Node $node ) : void {
		$this->visitMethodCall( $node );
	}

	/**
	 * A variable (e.g. $foo)
	 *
	 * This always considers superglobals as tainted
	 *
	 * @param Node $node
	 */
	public function visitVar( Node $node ) : void {
		$varName = $this->getCtxN( $node )->getVariableName();
		if ( $varName === '' ) {
			$this->debug( __METHOD__, "FIXME: Complex variable case not handled." );
			// Debug::printNode( $node );
			$this->curTaint = Taintedness::newUnknown();
			return;
		}
		if ( $this->isSuperGlobal( $varName ) ) {
			// Superglobals are tainted, regardless of whether they're in the current scope:
			// `function foo() use ($argv)` puts $argv in the local scope, but it retains its
			// taintedness (see test closure2).
			// echo "$varName is superglobal. Marking tainted\n";
			$this->curTaint = Taintedness::newTainted();
			return;
		} elseif ( !$this->context->getScope()->hasVariableWithName( $varName ) ) {
			// Probably the var just isn't in scope yet.
			// $this->debug( __METHOD__, "No var with name \$$varName in scope (Setting Unknown taint)" );
			$this->curTaint = Taintedness::newUnknown();
			return;
		}
		$variableObj = $this->context->getScope()->getVariableByName( $varName );
		if ( $variableObj instanceof PassByReferenceVariable ) {
			$this->curTaint = $this->getTaintednessReference( $this->extractReferenceArgument( $variableObj ) );
		} else {
			$this->curTaint = $this->getTaintednessPhanObj( $variableObj );
		}
	}

	/**
	 * A global declaration. Assume most globals are untainted.
	 *
	 * @param Node $node
	 */
	public function visitGlobal( Node $node ) : void {
		assert( isset( $node->children['var'] ) && $node->children['var']->kind === \ast\AST_VAR );
		$varName = $node->children['var']->children['name'];
		if ( !is_string( $varName ) ) {
			// Something like global $$indirectReference;
			$this->curTaint = Taintedness::newInapplicable();
			return;
		}
		$scope = $this->context->getScope();
		if ( $scope->hasGlobalVariableWithName( $varName ) ) {
			$globalVar = $scope->getGlobalVariableByName( $varName );
			$localVar = clone $globalVar;
			$localVar->isGlobalVariable = true;
			$scope->addVariable( $localVar );
		}
		$this->curTaint = Taintedness::newInapplicable();
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
	public function visitReturn( Node $node ) : void {
		if ( !$this->context->isInFunctionLikeScope() ) {
			$this->debug( __METHOD__, "return outside func?" );
			// Debug::printNode( $node );
			$this->curTaint = Taintedness::newUnknown();
			return;
		}

		$curFunc = $this->context->getFunctionLikeInScope( $this->code_base );
		// The EXEC taint flags have different meaning for variables and
		// functions. We don't want to transmit exec flags here.
		$taintedness = $this->getTaintedness( $node->children['expr'] )->withOnly( SecurityCheckPlugin::ALL_TAINT );

		$funcTaint = $this->matchTaintToParam(
			$node->children['expr'],
			$taintedness,
			$curFunc
		);

		$this->setFuncTaint( $curFunc, $funcTaint );

		if ( $node->children['expr'] instanceof Node ) {
			// Save this object in the Function object
			$retObjs = $this->getPhanObjsForNode( $node->children['expr'] );
			$curFunc->retObjs = array_merge(
				$curFunc->retObjs ?? [],
				$retObjs
			);

			if ( $funcTaint->getOverall()->has( SecurityCheckPlugin::YES_EXEC_TAINT ) ) {
				foreach ( $retObjs as $pobj ) {
					$this->mergeTaintError( $curFunc, $pobj );
				}
			}
		}
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * @todo Handle array shape mutations (e.g. unset, array_shift, array_pop, etc.)
	 * @param Node $node
	 */
	public function visitArray( Node $node ) : void {
		$curTaint = Taintedness::newSafe();
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
			$childTaint = $this->getTaintedness( $child );
			$key = $child->children['key'];
			$keyTaint = $this->getTaintedness( $key );
			$value = $child->children['value'];
			$valTaint = $this->getTaintedness( $value );
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
			// TODO $childTaint includes the taintedness of the key. In the future,
			// we might want to distinguish.
			// Note that we remove numkey taint because that's only for the outer array
			$curTaint->setOffsetTaintedness( $offset, $childTaint->without( SecurityCheckPlugin::SQL_NUMKEY_TAINT ) );
		}
		$this->curTaint = $curTaint;
	}

	/**
	 * A => B
	 * @param Node $node
	 */
	public function visitArrayElem( Node $node ) : void {
		$this->curTaint = $this->getTaintedness( $node->children['value'] )
			->with( $this->getTaintedness( $node->children['key'] ) );
	}

	/**
	 * A foreach() loop
	 *
	 * The variable from the loop condition has its taintedness
	 * transferred in TaintednessLoopVisitor
	 * @param Node $node
	 */
	public function visitForeach( Node $node ) : void {
		// This is handled by TaintednessLoopVisitor.
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * @param Node $node
	 */
	public function visitClassConst( Node $node ) : void {
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * @param Node $node
	 */
	public function visitConst( Node $node ) : void {
		// We are going to assume nobody is doing stupid stuff like
		// define( "foo", $_GET['bar'] );
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * The :: operator (for props)
	 * @param Node $node
	 */
	public function visitStaticProp( Node $node ) : void {
		$props = $this->getPhanObjsForNode( $node );
		if ( count( $props ) > 1 ) {
			// This is unexpected.
			$this->debug( __METHOD__, "static prop has many objects" );
		}
		$taint = Taintedness::newSafe();
		foreach ( $props as $prop ) {
			$taint->add( $this->getTaintednessPhanObj( $prop ) );
		}
		$this->curTaint = $taint;
	}

	/**
	 * The -> operator (when not a method call)
	 * @param Node $node
	 */
	public function visitProp( Node $node ) : void {
		$props = $this->getPhanObjsForNode( $node );
		if ( count( $props ) !== 1 ) {
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
				$this->debug( __METHOD__, "Nested property reference " . count( $props ) . "" );
				# Debug::printNode( $node );
			}
			if ( count( $props ) === 0 ) {
				// Should this be NO_TAINT?
				$this->curTaint = Taintedness::newUnknown();
				return;
			}
		}
		$prop = $props[0];

		if ( $node->children['expr'] instanceof Node && $node->children['expr']->kind === \ast\AST_VAR ) {
			$variable = $this->getCtxN( $node->children['expr'] )->getVariable();
			// If the LHS is a variable and it can potentially be a stdClass, share its taintedness
			// with the property. TODO Can we improve this?
			$stdClassType = FullyQualifiedClassName::getStdClassFQSEN()->asType();
			if ( $variable->getUnionType()->hasType( $stdClassType ) ) {
				$this->doSetTaintedness(
					$prop,
					$this->getTaintednessPhanObj( $variable ),
					false,
					Taintedness::newSafe()
				);
				$this->mergeTaintError( $prop, $variable );
			}
		}

		$this->curTaint = $this->getTaintednessPhanObj( $prop );
	}

	/**
	 * When a class property is declared
	 * @param Node $node
	 */
	public function visitPropElem( Node $node ) : void {
		assert( $this->context->isInClassScope() );
		$clazz = $this->context->getClassInScope( $this->code_base );

		assert( $clazz->hasPropertyWithName( $this->code_base, $node->children['name'] ) );
		$prop = $clazz->getPropertyByName( $this->code_base, $node->children['name'] );
		// Initialize the taintedness of the prop if not set
		$this->setTaintedness( $prop, Taintedness::newSafe(), false );
		$this->curTaint = Taintedness::newInapplicable();
	}

	/**
	 * Ternary operator.
	 * @param Node $node
	 */
	public function visitConditional( Node $node ) : void {
		if ( $node->children['true'] === null ) {
			// $foo ?: $bar;
			$trueTaint = $this->getTaintedness( $node->children['cond'] );
		} else {
			$trueTaint = $this->getTaintedness( $node->children['true'] );
		}
		$falseTaint = $this->getTaintedness( $node->children['false'] );
		$this->curTaint = $trueTaint->with( $falseTaint );
	}

	/**
	 * @param Node $node
	 */
	public function visitName( Node $node ) : void {
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
	public function visitNameList( Node $node ) : void {
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * @param Node $node
	 */
	public function visitUnaryOp( Node $node ) : void {
		// ~ and @ are the only two unary ops
		// that can preserve taint (others cast bool or int)
		$unsafe = [
			\ast\flags\UNARY_BITWISE_NOT,
			\ast\flags\UNARY_SILENCE
		];
		if ( in_array( $node->flags, $unsafe, true ) ) {
			$this->curTaint = $this->getTaintedness( $node->children['expr'] );
		} else {
			$this->curTaint = Taintedness::newSafe();
		}
	}

	/**
	 * @param Node $node
	 */
	public function visitPostInc( Node $node ) : void {
		$this->analyzeIncOrDec( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitPreInc( Node $node ) : void {
		$this->analyzeIncOrDec( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitPostDec( Node $node ) : void {
		$this->analyzeIncOrDec( $node );
	}

	/**
	 * @param Node $node
	 */
	public function visitPreDec( Node $node ) : void {
		$this->analyzeIncOrDec( $node );
	}

	/**
	 * Handles all post/pre-increment/decrement operators. They have no effect on the
	 * taintedness of a variable.
	 *
	 * @param Node $node
	 */
	private function analyzeIncOrDec( Node $node ) : void {
		$this->curTaint = $this->getTaintedness( $node->children['var'] );
	}

	/**
	 * @param Node $node
	 */
	public function visitCast( Node $node ) : void {
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
			$this->curTaint = $this->getTaintedness( $node->children['expr'] );
		}
	}

	/**
	 * The taint is the taint of all the child elements
	 *
	 * @param Node $node
	 */
	public function visitEncapsList( Node $node ) : void {
		$taint = Taintedness::newSafe();
		foreach ( $node->children as $child ) {
			$taint->add( $this->getTaintedness( $child ) );
		}
		$this->curTaint = $taint;
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitIsset( Node $node ) : void {
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * Visits calls to empty(), which is always safe
	 *
	 * @param Node $node
	 */
	public function visitEmpty( Node $node ) : void {
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitMagicConst( Node $node ) : void {
		$this->curTaint = Taintedness::newSafe();
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitInstanceOf( Node $node ) : void {
		$this->curTaint = Taintedness::newSafe();
	}
}
