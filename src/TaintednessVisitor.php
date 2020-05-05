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

use ast\Node;
use Phan\CodeBase;
use Phan\Debug;
use Phan\Exception\CodeBaseException;
use Phan\Language\Context;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\PassByReferenceVariable;
use Phan\Language\Element\Property;
use Phan\Language\FQSEN\FullyQualifiedClassName;
use Phan\Language\FQSEN\FullyQualifiedFunctionName;
use Phan\Language\Type\ClosureType;
use Phan\PluginV3\PluginAwarePostAnalysisVisitor;

/**
 * This class visits all the nodes in the ast. It has two jobs:
 *
 * 1) Return the taint value of the current node we are visiting.
 * 2) In the event of an assignment (and similar things) propogate
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

	/** @var int|null */
	protected $curTaint;

	/**
	 * @inheritDoc
	 */
	public function __construct(
		CodeBase $code_base,
		Context $context,
		int &$taint = null
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
		$this->curTaint = SecurityCheckPlugin::UNKNOWN_TAINT;
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
			$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
		}
	}

	/**
	 * These are the vars passed to closures via use()
	 *
	 * @param Node $node
	 */
	public function visitClosureVar( Node $node ) : void {
		$pobjs = $this->getPhanObjsForNode( $node );
		if ( !$pobjs ) {
			$this->debug( __METHOD__, 'No variable found' );
			$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
			return;
		}
		assert( count( $pobjs ) === 1 );
		$varObj = $pobjs[0];
		if ( $varObj instanceof PassByReferenceVariable ) {
			$varObj = $this->extractReferenceArgument( $varObj );
		}
		$this->curTaint = $this->getTaintednessPhanObj( $varObj );
	}

	/**
	 * The 'use' keyword for closures. The variables inside it are handled in visitClosureVar
	 *
	 * @param Node $node
	 */
	public function visitClosureUses( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitFuncDecl( Node $node ) : void {
		$func = $this->context->getFunctionLikeInScope( $this->code_base );
		$this->curTaint = $this->analyzeFunctionLike( $func );
	}

	/**
	 * Visit a method decleration
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
	 * @return int Taint
	 */
	private function analyzeFunctionLike( FunctionInterface $func ) : int {
		// Phan will remove the variable map after analysis, so save it for later
		// use by GetReturnObjsVisitor. Ref phan issue #2963
		$func->scopeAfterAnalysis = $this->context->getScope();
		if (
			!property_exists( $func, 'funcTaint' ) &&
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
			$this->setFuncTaint( $func, [ 'overall' => SecurityCheckPlugin::NO_TAINT ] );
		}
		return SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	// No-ops we ignore.
	// separate methods so we can use visit to output debugging
	// for anything we miss.

	/**
	 * @param Node $node
	 */
	public function visitStmtList( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitUseElem( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitType( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitArgList( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitParamList( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @note Params should be handled in PreTaintednessVisitor
	 * @param Node $node
	 */
	public function visitParam( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitClass( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitClassConstDecl( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitConstDecl( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitIf( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitThrow( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * Actual property decleration is PropElem
	 * @param Node $node
	 */
	public function visitPropDecl( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitConstElem( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitUse( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitUseTrait( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitBreak( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitContinue( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitGoto( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitCatch( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitNamespace( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitSwitch( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitSwitchCase( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitWhile( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitDoWhile( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitFor( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitSwitchList( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * This is e.g. the list of expressions inside the for condition
	 *
	 * @param Node $node
	 */
	public function visitExprList( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitUnset( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitTry( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
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

		// Note: If there is a local variable that is a reference
		// to another non-local variable, this will probably incorrectly
		// override the taint (Pass by reference variables are handled
		// specially and should be ok).

		// Make sure $foo[2] = 0; doesn't kill taint of $foo generally.
		// Ditto for $this->bar, or props in general just in case.
		$override = $node->children['var']->kind !== \ast\AST_DIM
			&& $node->children['var']->kind !== \ast\AST_PROP;

		$variableObjs = $this->getPhanObjsForNode( $node->children['var'] );

		$lhsTaintedness = $this->getTaintedness( $node->children['var'] );
		# $this->debug( __METHOD__, "Getting taint LHS = $lhsTaintedness:" );
		$rhsTaintedness = $this->getTaintedness( $node->children['expr'] );
		# $this->debug( __METHOD__, "Getting taint RHS = $rhsTaintedness:" );

		if ( $node->kind === \ast\AST_ASSIGN_OP ) {
			// TODO, be more specific for different OPs
			// Expand rhs to include implicit lhs ophand.
			$rhsTaintedness = $this->mergeAddTaint( $rhsTaintedness, $lhsTaintedness );
			$override = false;
		}

		// Special case for SQL_NUMKEY_TAINT
		// If we're assigning an SQL tainted value as an array key
		// or as the value of a numeric key, then set NUMKEY taint.
		$var = $node->children['var'];
		if ( $var->kind === \ast\AST_DIM ) {
			$dim = $var->children['dim'];
			if ( $rhsTaintedness & SecurityCheckPlugin::SQL_NUMKEY_TAINT ) {
				// Things like 'foo' => ['taint', 'taint']
				// are ok.
				$rhsTaintedness &= ~SecurityCheckPlugin::SQL_NUMKEY_TAINT;
			} elseif ( $rhsTaintedness & SecurityCheckPlugin::SQL_TAINT ) {
				// Checking the case:
				// $foo[1] = $sqlTainted;
				// $foo[] = $sqlTainted;
				// But ensuring we don't catch:
				// $foo['bar'][] = $sqlTainted;
				// $foo[] = [ $sqlTainted ];
				// $foo[2] = [ $sqlTainted ];
				if (
					( $dim === null || $this->nodeIsInt( $dim ) )
					&& !$this->nodeIsArray( $node->children['expr'] )
					&& !( $var->children['expr'] instanceof Node
						&& $var->children['expr']->kind === \ast\AST_DIM
					)
				) {
					$rhsTaintedness |= SecurityCheckPlugin::SQL_NUMKEY_TAINT;
				}
			}
			if ( $this->getTaintedness( $dim ) & SecurityCheckPlugin::SQL_TAINT ) {
				$rhsTaintedness |= SecurityCheckPlugin::SQL_NUMKEY_TAINT;

			}
		}

		// If we're assigning to a variable we know will be output later
		// raise an issue now.
		// We only want to give a warning if we are adding new taint to the
		// variable. If the variable is alredy tainted, no need to retaint.
		// Otherwise, this could result in a variable basically tainting itself.
		// TODO: Additionally, we maybe consider skipping this when in
		// branch scope and variable is not pass by reference.
		// @fixme Is this really necessary? It doesn't seem helpful for local variables,
		// and it doesn't handle props or globals.
		$adjustedRHS = $rhsTaintedness & ~$lhsTaintedness;
		$this->maybeEmitIssue(
			$lhsTaintedness,
			$adjustedRHS,
			"Assigning a tainted value to a variable that later does something unsafe with it"
				. $this->getOriginalTaintLine( $node->children['var'] )
		);

		$rhsObjs = [];
		if ( is_object( $node->children['expr'] ) ) {
			$rhsObjs = $this->getPhanObjsForNode( $node->children['expr'] );
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
				// Don't clear data if one of the objects in the RHS is the same as this object
				// in the LHS. This is especially important in conditionals e.g. tainted = tainted ?: null.
				$allowClearLHSData = !in_array( $variableObj, $rhsObjs, true );
				$this->setTaintedness( $variableObj, $rhsTaintedness, $override, $allowClearLHSData );
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
				if (
					( ( ( $lhsTaintedness | $rhsTaintedness )
					& ~$taintRHSObj ) & SecurityCheckPlugin::ALL_YES_EXEC_TAINT )
					=== 0
				) {
					$this->mergeTaintDependencies( $variableObj, $rhsObj );
				} elseif ( $taintRHSObj ) {
					$this->mergeTaintError( $variableObj, $rhsObj );
				}
			}
		}
		$this->curTaint = $rhsTaintedness;
	}

	/**
	 * @param Node $node
	 */
	public function visitBinaryOp( Node $node ) : void {
		$safeBinOps = [
			// Unsure about BITWISE ops, since
			// "A" | "B" still is a string
			// so skipping.
			\ast\flags\BINARY_BOOL_XOR,
			\ast\flags\BINARY_DIV,
			\ast\flags\BINARY_IS_EQUAL,
			\ast\flags\BINARY_IS_IDENTICAL,
			\ast\flags\BINARY_IS_NOT_EQUAL,
			\ast\flags\BINARY_IS_NOT_IDENTICAL,
			\ast\flags\BINARY_IS_SMALLER,
			\ast\flags\BINARY_IS_SMALLER_OR_EQUAL,
			\ast\flags\BINARY_MOD,
			\ast\flags\BINARY_MUL,
			\ast\flags\BINARY_POW,
			// BINARY_ADD handled below due to array addition.
			\ast\flags\BINARY_SUB,
			\ast\flags\BINARY_BOOL_AND,
			\ast\flags\BINARY_BOOL_OR,
			\ast\flags\BINARY_IS_GREATER,
			\ast\flags\BINARY_IS_GREATER_OR_EQUAL
		];

		if ( in_array( $node->flags, $safeBinOps, true ) ) {
			$this->curTaint = SecurityCheckPlugin::NO_TAINT;
			return;
		} elseif (
			$node->flags === \ast\flags\BINARY_ADD && (
				$this->nodeIsInt( $node->children['left'] ) ||
				$this->nodeIsInt( $node->children['right'] )
			)
		) {
			// This is used to avoid removing taintedness from array addition, and addition
			// of unknown types. If at least one node is integer, either the result will be an
			// integer, or PHP will throw a fatal.
			$this->curTaint = SecurityCheckPlugin::NO_TAINT;
			return;
		}

		// Otherwise combine the ophand taint.
		$leftTaint = $this->getTaintedness( $node->children['left'] );
		$rightTaint = $this->getTaintedness( $node->children['right'] );
		$this->curTaint = $this->mergeAddTaint( $leftTaint, $rightTaint );
	}

	/**
	 * @todo We need more fine grained handling of arrays.
	 *
	 * @param Node $node
	 */
	public function visitDim( Node $node ) : void {
		$this->curTaint = $this->getTaintednessNode( $node->children['expr'] );
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
	 * @param Node $node
	 */
	public function visitShellExec( Node $node ) : void {
		$taintedness = $this->getTaintedness( $node->children['expr'] );

		$this->maybeEmitIssue(
			SecurityCheckPlugin::SHELL_EXEC_TAINT,
			$taintedness,
			"Backtick shell execution operator contains user controlled arg"
				. $this->getOriginalTaintLine( $node->children['expr'] )
		);

		if (
			$node->children['expr'] instanceof Node &&
			$this->isSafeAssignment( SecurityCheckPlugin::SHELL_EXEC_TAINT, $taintedness )
		) {
			// In the event the assignment looks safe, keep track of it,
			// in case it later turns out not to be safe.
			$phanObjs = $this->getPhanObjsForNode( $node->children['expr'], [ 'return' ] );
			foreach ( $phanObjs as $phanObj ) {
				$this->debug( __METHOD__, "Setting {$phanObj->getName()} exec due to backtick" );
				$this->markAllDependentMethodsExec(
					$phanObj,
					SecurityCheckPlugin::SHELL_EXEC_TAINT
				);
			}
		}
		// Its unclear if we should consider this tainted or not
		$this->curTaint = SecurityCheckPlugin::YES_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitIncludeOrEval( Node $node ) : void {
		$taintedness = $this->getTaintedness( $node->children['expr'] );

		$this->maybeEmitIssue(
			SecurityCheckPlugin::MISC_EXEC_TAINT,
			$taintedness,
			"Argument to require, include or eval is user controlled"
				. $this->getOriginalTaintLine( $node->children['expr'] )
		);

		if (
			$node->children['expr'] instanceof Node &&
			$this->isSafeAssignment( SecurityCheckPlugin::MISC_EXEC_TAINT, $taintedness )
		) {
			// In the event the assignment looks safe, keep track of it,
			// in case it later turns out not to be safe.
			$phanObjs = $this->getPhanObjsForNode( $node->children['expr'], [ 'return' ] );
			foreach ( $phanObjs as $phanObj ) {
				$this->debug( __METHOD__, "Setting {$phanObj->getName()} exec due to require/eval" );
				$this->markAllDependentMethodsExec(
					$phanObj,
					SecurityCheckPlugin::MISC_EXEC_TAINT
				);
			}
		}
		// Strictly speaking we have no idea if the result
		// of an eval() or require() is safe. But given that we
		// don't know, and at least in the require() case its
		// fairly likely to be safe, no point in complaining.
		$this->curTaint = SecurityCheckPlugin::NO_TAINT;
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
		$echoTaint = SecurityCheckPlugin::HTML_EXEC_TAINT;
		$echoedExpr = $node->children['expr'];
		$taintedness = $this->getTaintedness( $echoedExpr );
		# $this->debug( __METHOD__, "Echoing with taint $taintedness" );

		$this->maybeEmitIssue(
			$echoTaint,
			$taintedness,
			"Echoing expression that was not html escaped"
				. $this->getOriginalTaintLine( $echoedExpr )
		);

		if ( $echoedExpr instanceof Node && $this->isSafeAssignment( $echoTaint, $taintedness ) ) {
			// In the event the assignment looks safe, keep track of it,
			// in case it later turns out not to be safe.
			$phanObjs = $this->getPhanObjsForNode( $echoedExpr, [ 'return' ] );
			foreach ( $phanObjs as $phanObj ) {
				$this->debug( __METHOD__, "Setting {$phanObj->getName()} exec due to echo" );
				// FIXME, maybe not do this for local variables
				// since they don't have other code paths that can set them.
				$this->markAllDependentMethodsExec(
					$phanObj,
					$echoTaint
				);
			}
		}
		$this->curTaint = SecurityCheckPlugin::NO_TAINT;
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
		if ( $node->children['class']->kind === \ast\AST_NAME ) {
			$this->visitMethodCall( $node );
		} else {
			$this->debug( __METHOD__, "cannot understand new" );
			$this->curTaint = SecurityCheckPlugin::UNKNOWN_TAINT;
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
		$ctxNode = $this->getCtxN( $node );
		$isStatic = ( $node->kind === \ast\AST_STATIC_CALL );
		$isFunc = ( $node->kind === \ast\AST_CALL );

		// First we need to get the taintedness of the method
		// in question.
		try {
			if ( $node->kind === \ast\AST_NEW ) {
				// We check the __construct() method first, but the
				// final resulting taint is from the __toString()
				// method. This is a little hacky.
				$constructor = $ctxNode->getMethod(
					'__construct',
					false,
					false,
					true
				);
				// First do __construct()
				$this->handleMethodCall(
					$constructor,
					$constructor->getFQSEN(),
					$node->children['args']->children
				);
				// Now return __toString()
				$clazz = $constructor->getClass( $this->code_base );
				try {
					$toString = $clazz->getMethodByName( $this->code_base, '__toString' );
				} catch ( CodeBaseException $_ ) {
					// There is no __toString(), then presumably the object can't be outputed, so should be safe.
					$this->debug( __METHOD__, "no __toString() in $clazz" );
					$this->curTaint = SecurityCheckPlugin::NO_TAINT;
					return;
				}

				$this->curTaint = $this->handleMethodCall(
					$toString,
					$toString->getFQSEN(),
					[] // __toString() has no args
				);
				return;
			} elseif ( $isFunc ) {
				if ( $node->children['expr']->kind === \ast\AST_NAME ) {
					$func = $ctxNode->getFunction( $node->children['expr']->children['name'] );
				} elseif ( $node->children['expr']->kind === \ast\AST_VAR ) {
					// Closure
					$pobjs = $this->getPhanObjsForNode( $node->children['expr'] );
					assert( count( $pobjs ) === 1 );
					$types = $pobjs[0]->getUnionType()->getTypeSet();
					$func = null;
					foreach ( $types as $type ) {
						if ( $type instanceof ClosureType ) {
							$func = $type->asFunctionInterfaceOrNull( $this->code_base, $this->context );
						}
					}
					if ( $func === null ) {
						throw new Exception( 'Cannot get closure from variable.' );
					}
				} else {
					throw new Exception( "Non-simple func call" );
				}
			} else {
				$methodName = $node->children['method'];
				$func = $ctxNode->getMethod( $methodName, $isStatic );
			}
			$funcName = $func->getFQSEN();
		} catch ( Exception $e ) {
			$this->debug( __METHOD__, "FIXME complicated case not handled."
				. " Maybe func not defined. " . $this->getDebugInfo( $e ) );
			$this->curTaint = SecurityCheckPlugin::UNKNOWN_TAINT;
			return;
		}

		$this->curTaint = $this->handleMethodCall(
			$func,
			$funcName,
			$node->children['args']->children
		);
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
			$this->curTaint = SecurityCheckPlugin::UNKNOWN_TAINT;
			return;
		}
		if ( $this->isSuperGlobal( $varName ) ) {
			// Superglobals are tainted, regardless of whether they're in the current scope:
			// `function foo() use ($argv)` puts $argv in the local scope, but it retains its
			// taintedness (see test closure2).
			// echo "$varName is superglobal. Marking tainted\n";
			$this->curTaint = SecurityCheckPlugin::YES_TAINT;
			return;
		} elseif ( !$this->context->getScope()->hasVariableWithName( $varName ) ) {
			// Probably the var just isn't in scope yet.
			// $this->debug( __METHOD__, "No var with name \$$varName in scope (Setting Unknown taint)" );
			$this->curTaint = SecurityCheckPlugin::UNKNOWN_TAINT;
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
			$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
			return;
		}
		$scope = $this->context->getScope();
		if ( $scope->hasGlobalVariableWithName( $varName ) ) {
			$globalVar = $scope->getGlobalVariableByName( $varName );
			$localVar = clone $globalVar;
			$localVar->isGlobalVariable = true;
			$scope->addVariable( $localVar );
		}
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
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
			$this->curTaint = SecurityCheckPlugin::UNKNOWN_TAINT;
			return;
		}

		$curFunc = $this->context->getFunctionLikeInScope( $this->code_base );
		// The EXEC taint flags have different meaning for variables and
		// functions. We don't want to transmit exec flags here.
		$taintedness = $this->getTaintedness( $node->children['expr'] ) &
			SecurityCheckPlugin::ALL_TAINT;

		$funcTaint = $this->matchTaintToParam(
			$node->children['expr'],
			$taintedness,
			$curFunc
		);

		$this->checkFuncTaint( $funcTaint );
		$this->setFuncTaint( $curFunc, $funcTaint );

		if ( $funcTaint['overall'] & SecurityCheckPlugin::YES_EXEC_TAINT ) {
			$taintSource = '';
			$pobjs = $this->getPhanObjsForNode( $node->children['expr'] );
			foreach ( $pobjs as $pobj ) {
				$taintSource .= $pobj->taintedOriginalError ?? '';
			}
			if ( strlen( $taintSource ) < 200 ) {
				if ( !property_exists( $curFunc, 'taintedOriginalError' ) ) {
					$curFunc->taintedOriginalError = '';
				}
				$curFunc->taintedOriginalError = substr(
					$curFunc->taintedOriginalError . $taintSource,
					0,
					250
				);
			}
		}
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitArray( Node $node ) : void {
		$curTaint = SecurityCheckPlugin::NO_TAINT;
		foreach ( $node->children as $child ) {
			if ( $child === null ) {
				// Happens for list( , $x ) = foo()
				continue;
			}
			assert( $child->kind === \ast\AST_ARRAY_ELEM );
			$childTaint = $this->getTaintedness( $child );
			$key = $child->children['key'];
			$value = $child->children['value'];
			$sqlTaint = SecurityCheckPlugin::SQL_TAINT;
			if (
				$this->getTaintedness( $value )
				& SecurityCheckPlugin::SQL_NUMKEY_TAINT
			) {
				$childTaint &= ~SecurityCheckPlugin::SQL_NUMKEY_TAINT;
			}
			if (
				( $this->getTaintedness( $key ) & $sqlTaint ) ||
				( ( $key === null || $this->nodeIsInt( $key ) )
				&& ( $this->getTaintedness( $value ) & $sqlTaint )
				&& $this->nodeIsString( $value ) )
			) {
				$childTaint |= SecurityCheckPlugin::SQL_NUMKEY_TAINT;
			}
			$curTaint = $this->mergeAddTaint( $curTaint, $childTaint );
		}
		$this->curTaint = $curTaint;
	}

	/**
	 * A => B
	 * @param Node $node
	 */
	public function visitArrayElem( Node $node ) : void {
		$this->curTaint = $this->mergeAddTaint(
			$this->getTaintedness( $node->children['value'] ),
			$this->getTaintedness( $node->children['key'] )
		);
	}

	/**
	 * A foreach() loop
	 *
	 * The variable from the loop condition has its taintedness
	 * transferred in PreTaintednessVisitor
	 * @param Node $node
	 */
	public function visitForeach( Node $node ) : void {
		// This is handled by PreTaintednessVisitor.
		$this->curTaint = SecurityCheckPlugin::NO_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitClassConst( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::NO_TAINT;
	}

	/**
	 * @param Node $node
	 */
	public function visitConst( Node $node ) : void {
		// We are going to assume nobody is doing stupid stuff like
		// define( "foo", $_GET['bar'] );
		$this->curTaint = SecurityCheckPlugin::NO_TAINT;
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
		$taint = 0;
		foreach ( $props as $prop ) {
			$taint |= $this->getTaintednessPhanObj( $prop );
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
				// Eventhough unserializing stuff from db can be very
				// problematic if user can ever control.
				// FIXME This is MW specific so should not be
				// in the generic visitor.
				$this->curTaint = SecurityCheckPlugin::YES_TAINT & ~SecurityCheckPlugin::SERIALIZE_TAINT;
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
				$this->curTaint = SecurityCheckPlugin::UNKNOWN_TAINT;
				return;
			}
		}
		$prop = $props[0];

		if ( $node->children['expr'] instanceof Node && $node->children['expr']->kind === \ast\AST_VAR ) {
			$variable = $this->getCtxN( $node->children['expr'] )->getVariable();
			if ( property_exists( $variable, 'taintedness' ) ) {
				// If the variable has taintedness set and its union type contains stdClass, it's
				// because this is the result of casting an array to object. Share the taintedness
				// of the variable with all its properties like we do for arrays.
				$types = array_map( 'strval', $variable->getUnionType()->getTypeSet() );
				if ( in_array( FullyQualifiedClassName::getStdClassFQSEN()->__toString(), $types, true ) ) {
					$prop->taintedness = $this->mergeAddTaint( $prop->taintedness ?? 0, $variable->taintedness );
					$this->mergeTaintError( $prop, $variable );
				}
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
		// FIXME should this be NO?
		// $this->debug( __METHOD__, "Setting taint preserve if not set"
		// . " yet for \$" . $node->children['name'] . "" );
		$this->setTaintedness( $prop, SecurityCheckPlugin::NO_TAINT, false );
		$this->curTaint = SecurityCheckPlugin::INAPPLICABLE_TAINT;
	}

	/**
	 * Ternary operator.
	 * @param Node $node
	 */
	public function visitConditional( Node $node ) : void {
		if ( $node->children['true'] === null ) {
			// $foo ?: $bar;
			$t = $this->getTaintedness( $node->children['cond'] );
		} else {
			$t = $this->getTaintedness( $node->children['true'] );
		}
		$f = $this->getTaintedness( $node->children['false'] );
		$this->curTaint = $this->mergeAddTaint( $t, $f );
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

		$this->curTaint = SecurityCheckPlugin::NO_TAINT;
	}

	/**
	 * This is e.g. for class X implements Name,List
	 *
	 * @param Node $node
	 */
	public function visitNameList( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::NO_TAINT;
	}

	/**
	 * @todo Is this right? The child condition should
	 *  be visited when going in post order analysis anyways,
	 *  and the taint of an If statement isn't really its condition.
	 * @param Node $node
	 */
	public function visitIfElem( Node $node ) : void {
		$this->curTaint = $this->getTaintedness( $node->children['cond'] );
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
			$this->curTaint = SecurityCheckPlugin::NO_TAINT;
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
		$children = $this->getPhanObjsForNode( $node );
		if ( count( $children ) === 1 ) {
			$pobj = reset( $children );
			if ( $pobj instanceof PassByReferenceVariable ) {
				$pobj = $this->extractReferenceArgument( $pobj );
			}
			$this->curTaint = $this->getTaintednessPhanObj( $pobj );
		} elseif ( isset( $node->children['var'] ) ) {
			// @fixme Stopgap to handle superglobals, which getPhanObjsForNode doesn't return
			$this->visitVar( $node->children['var'] );
		} else {
			$this->curTaint = SecurityCheckPlugin::NO_TAINT;
		}
	}

	/**
	 * @param Node $node
	 */
	public function visitCast( Node $node ) : void {
		// Casting between an array and object maintains
		// taint. Casting an object to a string calls __toString().
		// Future TODO: handle the string case properly.
		$dangerousCasts = [
			ast\flags\TYPE_STRING,
			ast\flags\TYPE_ARRAY,
			ast\flags\TYPE_OBJECT
		];

		if ( !in_array( $node->flags, $dangerousCasts, true ) ) {
			$this->curTaint = SecurityCheckPlugin::NO_TAINT;
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
		$taint = SecurityCheckPlugin::NO_TAINT;
		foreach ( $node->children as $child ) {
			$taint = $this->mergeAddTaint( $taint, $this->getTaintedness( $child ) );
		}
		$this->curTaint = $taint;
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitIsset( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::NO_TAINT;
	}

	/**
	 * Visits calls to empty(), which is always safe
	 *
	 * @param Node $node
	 */
	public function visitEmpty( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::NO_TAINT;
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitMagicConst( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::NO_TAINT;
	}

	/**
	 * Visit a node that is always safe
	 *
	 * @param Node $node
	 */
	public function visitInstanceOf( Node $node ) : void {
		$this->curTaint = SecurityCheckPlugin::NO_TAINT;
	}
}
