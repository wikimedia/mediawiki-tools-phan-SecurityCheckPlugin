<?php

use ast\Node;
use Phan\AST\ContextNode;
use Phan\AST\UnionTypeVisitor;
use Phan\BlockAnalysisVisitor;
use Phan\CodeBase;
use Phan\Debug;
use Phan\Exception\IssueException;
use Phan\Issue;
use Phan\Language\Context;
use Phan\Language\Element\ClassElement;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\Method;
use Phan\Language\Element\Parameter;
use Phan\Language\Element\PassByReferenceVariable;
use Phan\Language\Element\Property;
use Phan\Language\Element\TypedElementInterface;
use Phan\Language\Element\Variable;
use Phan\Language\FQSEN\FullyQualifiedClassName;
use Phan\Language\FQSEN\FullyQualifiedFunctionLikeName;
use Phan\Language\FQSEN\FullyQualifiedFunctionName;
use Phan\Language\FQSEN\FullyQualifiedMethodName;
use Phan\Language\Scope\BranchScope;
use Phan\Language\Type\CallableType;
use Phan\Language\Type\ClosureType;
use Phan\Language\Type\IntType;
use Phan\Language\Type\MixedType;
use Phan\Language\Type\StringType;
use Phan\Language\UnionType;
use Phan\Library\Set;

/**
 * Trait for the Tainedness visitor subclasses. Mostly contains
 * utility methods.
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
/**
 * @property-read Context $context
 * @property-read \Phan\CodeBase $code_base
 */
trait TaintednessBaseVisitor {
	/** @var null|string|bool|resource filehandle to output debug messages */
	private $debugOutput;

	/** @var Context Override the file/line number to emit issues */
	protected $overrideContext;

	/**
	 * Change taintedness of a function/method
	 *
	 * @param FunctionInterface $func
	 * @param int[] $taint Numeric keys for each arg and an 'overall' key.
	 * @param bool $override Whether to merge taint or override
	 * @param string|Context|null $reason Either a reason or a context representing the line number
	 */
	protected function setFuncTaint(
		FunctionInterface $func,
		array $taint,
		bool $override = false,
		$reason = null
	) : void {
		if (
			$func instanceof Method &&
			(string)$func->getDefiningFQSEN() !== (string)$func->getFQSEN()
		) {
			$this->debug( __METHOD__, "Setting taint on function " . $func->getFQSEN() . " other than"
				. " its implementation " . $func->getDefiningFQSEN()
			);
			// FIXME we should maybe do something here.
			// As it stands, this case probably can't be reached.
		}
		$curTaint = [];
		$newTaint = [];

		if ( property_exists( $func, 'funcTaint' ) ) {
			$curTaint = $func->funcTaint;
		} elseif ( !$override ) {
			// If we are not overriding, and we don't know
			// current taint, figure it out.
			$curTaint = $this->getTaintOfFunction( $func, false );
		}
		if ( $override ) {
			$newTaint = $taint;
		}

		$bothTaint = $taint + $curTaint;
		foreach ( $bothTaint as $index => $_ ) {
			$t = $taint[$index] ?? 0;
			assert( is_int( $t ) );
			$curT = $curTaint[$index] ?? 0;
			if ( !$override ) {
				if ( $curT & SecurityCheckPlugin::NO_OVERRIDE ) {
					// We have some hard coded taint (e.g. from
					// docblock) and do not want to override it
					// from stuff deduced from src code.
					$newTaint[$index] = $curT;
				} else {
					// We also clear the UNKNOWN flag here, as
					// if we are explicitly setting it, it is no
					// longer unknown.
					$curTNoUnk = $curT & ( ~SecurityCheckPlugin::UNKNOWN_TAINT );
					$newTaint[$index] = $curTNoUnk | $t;
				}
			}
			// Only copy error lines if we add some taint not
			// previously present.
			if ( ( ( $curT | $t ) ^ $curT ) !== 0 ) {
				if ( is_int( $index ) ) {
					$this->addTaintError( $t, $func, $index, $reason );
				} else {
					$this->addTaintError( $t, $func, -1, $reason );
				}
			}
		}
		if ( !isset( $newTaint['overall'] ) ) {
			// FIXME, what's the right default??
			$this->debug( __METHOD__, 'FIXME No overall taint specified for ' . $func->getName() );
			$newTaint['overall'] = SecurityCheckPlugin::UNKNOWN_TAINT;
		}
		$this->checkFuncTaint( $newTaint );
		$func->funcTaint = $newTaint;
	}

	/**
	 * Merge the info on original cause of taint to left variable
	 *
	 * If you have something like $left = $right, merge any information
	 * about what tainted $right into $left as $right's taint may now
	 * have tainted $left (Or may not if the assingment is in a branch
	 * or its not a local variable).
	 *
	 * @note It is assumed you already checked that right is tainted in some way.
	 * @param TypedElementInterface $left (LHS-ish variable)
	 * @param TypedElementInterface $right (RHS-ish variable)
	 */
	protected function mergeTaintError(
		TypedElementInterface $left,
		TypedElementInterface $right
	) : void {
		if ( !property_exists( $left, 'taintedOriginalError' ) ) {
			$left->taintedOriginalError = '';
		}
		$rightError = $right->taintedOriginalError ?? '';
		if ( strpos( $left->taintedOriginalError, $rightError ?: "\1\2" ) === false ) {
			$left->taintedOriginalError .= $rightError;
		}

		if ( strlen( $left->taintedOriginalError ) > 254 ) {
			$this->debug( __METHOD__, "Too long original error! for " . $left->getName() );
			$left->taintedOriginalError = substr( $left->taintedOriginalError, 0, 250 ) . '... ';
		}
	}

	/**
	 * Clears any previous error on the given element.
	 *
	 * @param TypedElementInterface $elem
	 */
	protected function clearTaintError( TypedElementInterface $elem ) : void {
		if ( property_exists( $elem, 'taintedOriginalError' ) ) {
			$elem->taintedOriginalError = '';
		}
	}

	/**
	 * Clears any taintedness links on this object
	 *
	 * @param TypedElementInterface $elem
	 */
	protected function clearTaintLinks( TypedElementInterface $elem ) : void {
		unset( $elem->taintedMethodLinks, $elem->taintedVarLinks );
	}

	/**
	 * Add the current context to taintedOriginalError book-keeping
	 *
	 * This allows us to show users what line caused an issue.
	 *
	 * @param int $taintedness The taintedness in question
	 * @param TypedElementInterface $elem Where to put it
	 * @param int $arg [Optional] For functions, which argument
	 * @param string|Context|null $reason To override the caused by line
	 */
	protected function addTaintError(
		int $taintedness,
		TypedElementInterface $elem,
		int $arg = -1,
		$reason = null
	) : void {
		if ( !$this->isExecTaint( $taintedness ) && !$this->isAllTaint( $taintedness ) ) {
			// Don't add book-keeping if no actual taint was added.
			return;
		}

		assert( $arg === -1 || $elem instanceof FunctionInterface );

		if ( $arg === -1 ) {
			if ( !property_exists( $elem, 'taintedOriginalError' ) ) {
				$elem->taintedOriginalError = '';
			}
		} else {
			if ( !property_exists( $elem, 'taintedOriginalErrorByArg' ) ) {
				$elem->taintedOriginalErrorByArg = [];
			}
			if ( !isset( $elem->taintedOriginalErrorByArg[$arg] ) ) {
				$elem->taintedOriginalErrorByArg[$arg] = '';
			}
		}
		if ( !is_string( $reason ) ) {
			$newErrors = [ $this->dbgInfo( $reason ?? $this->context ) . ';' ];
		} else {
			$newErrors = [ ' ' . $reason . ';' ];
		}
		if ( $this->overrideContext ) {
			$newErrors[] = $this->dbgInfo( $this->overrideContext ) . ';';
		}
		foreach ( $newErrors as $newError ) {
			if ( $arg === -1 ) {
				if ( strpos( $elem->taintedOriginalError, $newError ) === false ) {
					$elem->taintedOriginalError .= $newError;
				}
			} elseif ( strpos( $elem->taintedOriginalErrorByArg[$arg], $newError ) === false ) {
				$elem->taintedOriginalErrorByArg[$arg] .= $newError;
			}
		}

		if ( $arg === -1 ) {
			if ( strlen( $elem->taintedOriginalError ) > 254 ) {
				$this->debug( __METHOD__, "Too long original error! " . $elem->getName() );
				$elem->taintedOriginalError = substr(
					$elem->taintedOriginalError, 0, 250
				) . '... ';
			}
		} elseif ( strlen( $elem->taintedOriginalErrorByArg[$arg] ) > 254 ) {
			$this->debug( __METHOD__, "Too long original error! " . $elem->getName() );
			$elem->taintedOriginalErrorByArg[ $arg ] =
				substr( $elem->taintedOriginalErrorByArg[ $arg ], 0, 250 ) . '... ';
		}
	}

	/**
	 * @param TypedElementInterface $var
	 * @return int
	 */
	protected function getTaintednessReference( TypedElementInterface $var ) : int {
		if ( $var instanceof PassByReferenceVariable ) {
			throw new Exception( __METHOD__ . ' takes the element inside PassByRefs' );
		}
		return $var->taintednessRef ?? SecurityCheckPlugin::NO_TAINT;
	}

	/**
	 * Given a PassByRef, recursively extract the argument it refers to.
	 *
	 * @param PassByReferenceVariable $obj
	 * @return TypedElementInterface
	 */
	protected function extractReferenceArgument(
		PassByReferenceVariable $obj
	) : TypedElementInterface {
		do {
			$obj = $obj->getElement();
		} while ( $obj instanceof PassByReferenceVariable );
		return $obj;
	}

	/**
	 * Whether the object is a reference argument of a hook.
	 *
	 * @param TypedElementInterface $obj
	 * @return bool
	 */
	protected function isHookRefArg( TypedElementInterface $obj ) : bool {
		return property_exists( $obj, 'isHookRefArg' );
	}

	/**
	 * @param TypedElementInterface $var
	 * @param int $taint
	 * @param bool $override
	 */
	protected function setRefTaintedness(
		TypedElementInterface $var,
		int $taint,
		bool $override
	) : void {
		if ( $var instanceof PassByReferenceVariable ) {
			throw new Error(
				__METHOD__ . ' not meant for PassByReferenceVariable objects, but for their element'
			);
		}

		if (
			$this->context->getScope() instanceof BranchScope ||
			$var instanceof Property ||
			$this->isHookRefArg( $var )
		) {
			$override = false;
		}
		$var->taintednessRef = $override
			? $taint
			: $this->mergeAddTaint( $var->taintednessRef ?? SecurityCheckPlugin::NO_TAINT, $taint );

		$this->addTaintError( $taint, $var );
	}

	/**
	 * Change the taintedness of a variable
	 *
	 * @param TypedElementInterface $variableObj The variable in question
	 * @param int $taintedness One of the class constants
	 * @param bool $override Override taintedness or just take max.
	 * @param bool $allowClearLHSData Whether we're allowed to clear taint error and links
	 *   from the LHS. This is only honored when the taint is being overridden.
	 */
	protected function setTaintedness(
		TypedElementInterface $variableObj,
		int $taintedness,
		$override = true,
		bool $allowClearLHSData = false
	) : void {
		// $this->debug( __METHOD__, "begin for \$" . $variableObj->getName()
		// . " <- $taintedness (override=$override) prev " . ( $variableObj->taintedness ?? 'unset' )
		// . ' Caller: ' . ( debug_backtrace()[1]['function'] ?? 'n/a' )
		// . ', ' . ( debug_backtrace()[2]['function'] ?? 'n/a' ) );

		assert( $taintedness >= 0, "Taintedness: $taintedness" );

		if ( $variableObj instanceof FunctionInterface ) {
			// FIXME what about closures?
			throw new Exception( "Must use setFuncTaint for functions" );
		}

		if ( $variableObj instanceof PassByReferenceVariable ) {
			throw new AssertionError( 'Handle passbyrefs before calling this method' );
		}

		// $this->debug( __METHOD__, "\$" . $variableObj->getName() . " has outer scope - "
		// . get_class( $this->context->getScope() ) . "" );

		if ( property_exists( $variableObj, 'isGlobalVariable' ) ) {
			$globalVar = $this->context->getScope()->getGlobalVariableByName( $variableObj->getName() );
			// Merge the taint on the "true" global object, too
			$this->doSetTaintedness( $globalVar, $taintedness, false );
			$override = false;
		}
		if ( $this->isHookRefArg( $variableObj ) ) {
			// We do this in the general case as well. In doing so, we assume that a hook handler
			// is only used as a hook handler.
			$override = false;
		}

		if ( $override && $allowClearLHSData ) {
			// Clear any error and link before setting taintedness if we're overriding taint.
			// Checking for $override here already takes into account globals, props,
			// outer scope, and whatnot.
			$this->clearTaintError( $variableObj );
			$this->clearTaintLinks( $variableObj );
		}

		$this->doSetTaintedness( $variableObj, $taintedness, $override );
	}

	/**
	 * Actually sets the taintedness on $variableObj. This should only be called by
	 * setTaintedness.
	 *
	 * @param TypedElementInterface $variableObj
	 * @param int $taintedness
	 * @param bool $override
	 */
	private function doSetTaintedness( TypedElementInterface $variableObj, int $taintedness, bool $override ) {
		$variableObj->taintedness = $override ?
			$taintedness :
			$this->mergeAddTaint(
				$variableObj->taintedness ?? 0, $taintedness
			);
		// $this->debug( __METHOD__, $variableObj->getName() . " now has taint " .
		// ( $variableObj->taintedness ?? 'unset' ) );
		$this->addTaintError( $taintedness, $variableObj );
	}

	/**
	 * Merge two taint values together
	 *
	 * @param int $oldTaint One of the class constants
	 * @param int $newTaint One of the class constants
	 * @return int The merged taint value
	 */
	protected function mergeAddTaint( int $oldTaint, int $newTaint ) : int {
		// TODO: Should this clear UNKNOWN_TAINT if its present
		// only in one of the args?
		return $oldTaint | $newTaint;
	}

	/**
	 * Get the taint of a PHP builtin function/method
	 *
	 * Assume that anything not-hardcoded just passes its
	 * arguments into its return value
	 *
	 * @param FunctionInterface $func A builtin Function/Method
	 * @return array The function taint.
	 */
	private function getTaintOfFunctionPHP( FunctionInterface $func ) : array {
		$taint = $this->getBuiltinFuncTaint( $func->getFQSEN() );
		if ( $taint !== null ) {
			return $taint;
		}

		// Assume that anything really dangerous we've already
		// hardcoded. So just preserve taint
		$taintFromReturnType = $this->getTaintByReturnType( $func->getUnionType() );
		if ( $taintFromReturnType === SecurityCheckPlugin::NO_TAINT ) {
			return [ 'overall' => SecurityCheckPlugin::NO_TAINT ];
		}
		return [ 'overall' => SecurityCheckPlugin::PRESERVE_TAINT ];
	}

	/**
	 * Given a func, get the defining func or null
	 *
	 * @param FunctionInterface $func
	 * @return null|FunctionInterface
	 */
	private function getDefiningFunc( FunctionInterface $func ) : ?FunctionInterface {
		if ( $func instanceof Method && $func->hasDefiningFQSEN() ) {
			// Our function has a parent, and potentially interface and traits.
			if ( (string)$func->getDefiningFQSEN() !== (string)$func->getFQSEN() ) {
				return $this->code_base->getMethodByFQSEN(
					$func->getDefiningFQSEN()
				);
			}
		}
		return null;
	}

	/**
	 * Get a list of places to look for function taint info
	 *
	 * @todo How to handle multiple function definitions (phan "alternates")
	 * @param FunctionInterface $func
	 * @return FunctionInterface[]
	 */
	private function getPossibleFuncDefinitions( FunctionInterface $func ) : array {
		$funcsToTry = [ $func ];

		// If we don't have a defining func, stay with the same func.
		// definingFunc is used later on during fallback processing.
		$definingFunc = $this->getDefiningFunc( $func );
		if ( $definingFunc ) {
			$funcsToTry[] = $definingFunc;
		}
		if ( $func instanceof Method ) {
			try {
				$class = $func->getClass( $this->code_base );
				$nonParents = $class->getNonParentAncestorFQSENList();

				foreach ( $nonParents as $nonParentFQSEN ) {
					$nonParent = $this->code_base->getClassByFQSEN( $nonParentFQSEN );
					if ( $nonParent->hasMethodWithName(
						$this->code_base, $func->getName()
					) ) {
						$funcsToTry[] = $nonParent->getMethodByName(
							$this->code_base, $func->getName()
						);
					}
				}
			} catch ( Exception $e ) {
				// Could happen if the interface file is missing
				$this->debug( __METHOD__, "Error looking up interface " .
					get_class( $e ) . ' ' . $e->getMessage()
				);
			}
		}
		return $funcsToTry;
	}

	/**
	 * This is also for methods and other function like things
	 *
	 * @param FunctionInterface $func What function/method to look up
	 * @param bool $clearOverride Include SecurityCheckPlugin::NO_OVERRIDE
	 * @return int[] Array with "overall" key, and numeric keys.
	 *   The overall key specifies what taint the function returns
	 *   irrespective of its arguments. The numeric keys are how
	 *   each individual argument affects taint.
	 *
	 *   For 'overall': the EXEC flags mean a call does evil regardless of args
	 *                  the TAINT flags are what taint the output has
	 *   For numeric keys: EXEC flags for what taints are unsafe here
	 *                     TAINT flags for what taint gets passed through func.
	 *   If func has an arg that is missing from array, then it should be
	 *   treated as TAINT_NO if its a number or bool. TAINT_YES otherwise.
	 */
	protected function getTaintOfFunction( FunctionInterface $func, $clearOverride = true ) : array {
		// Fast case, either a builtin to php function or we already
		// know taint:
		if ( $func->isPHPInternal() ) {
			return $this->maybeClearNoOverride( $this->getTaintOfFunctionPHP( $func ), $clearOverride );
		}

		if ( property_exists( $func, 'funcTaint' ) ) {
			return $this->maybeClearNoOverride( $func->funcTaint, $clearOverride );
		}

		// Gather up

		$funcsToTry = $this->getPossibleFuncDefinitions( $func );
		foreach ( $funcsToTry as $trialFunc ) {
			$trialFuncName = $trialFunc->getFQSEN();
			$taint = $this->getDocBlockTaintOfFunc( $trialFunc );
			if ( $taint !== null ) {
				$this->setFuncTaint( $func, $taint, true, $trialFunc->getContext() );

				return $this->maybeClearNoOverride( $taint, $clearOverride );
			}
			$taint = $this->getBuiltinFuncTaint( $trialFuncName );
			if ( $taint !== null ) {
				$this->setFuncTaint( $func, $taint, true, "Builtin-$trialFuncName" );
				return $this->maybeClearNoOverride( $taint, $clearOverride );
			}
		}

		$definingFunc = $this->getDefiningFunc( $func ) ?: $func;
		// Ensure we don't indef loop.
		if (
			!$definingFunc->isPHPInternal() &&
			( !$this->context->isInFunctionLikeScope() ||
			$definingFunc->getFQSEN() !== $this->context->getFunctionLikeFQSEN() )
		) {
			$this->debug( __METHOD__, 'no taint info for func ' . $func->getName() );
			if ( !property_exists( $definingFunc, 'funcTaint' ) ) {
				// Optim: don't reanalyze if we already have taint data. This might rarely hide
				// some issues, see T203651#6046483.
				try {
					$this->analyzeFunc( $definingFunc );
				} catch ( Exception $e ) {
					$this->debug( __METHOD__, "Error" . $e->getMessage() . "\n" );
				}
				$this->debug( __METHOD__, 'updated taint info for ' . $definingFunc->getName() );
			}
			// var_dump( $definingFunc->funcTaint ?? "NO INFO" );
			if ( property_exists( $definingFunc, 'funcTaint' ) ) {
				$this->checkFuncTaint( $definingFunc->funcTaint );
				return $this->maybeClearNoOverride( $definingFunc->funcTaint, $clearOverride );
			}
		}
		// TODO: Maybe look at __toString() if we are at __construct().
		// FIXME this could probably use a second look.

		// If we haven't seen this function before, first of all
		// check the return type. If it (e.g.) returns just an int,
		// its probably safe.
		$taint = [ 'overall' => $this->getTaintByReturnType( $func->getUnionType() ) ];
		$this->checkFuncTaint( $taint );
		$this->setFuncTaint( $func, $taint, true );
		return $this->maybeClearNoOverride( $taint, $clearOverride );
	}

	/**
	 * Analyze a function. This is very similar to Analyzable::analyze, but avoids several checks
	 * used by phan for performance. Phan doesn't know about taintedness, so it may decide to skip
	 * a re-analysis which we need.
	 * @todo This is a bit hacky.
	 * @see \Phan\Analysis\Analyzable::analyze()
	 *
	 * @param FunctionInterface $func
	 */
	public function analyzeFunc( FunctionInterface $func ) : void {
		static $depth = 0;
		$node = $func->getNode();
		if ( !$node ) {
			return;
		}
		// @todo Tune the max depth. Raw benchmarking shows very little difference between e.g.
		// 5 and 10. However, while with higher values we can detect more issues and avoid more
		// false positives, it becomes harder to tell where an issue is coming from.
		// Thus, this value should be increased only when we'll have better error reporting.
		if ( $depth > 5 ) {
			$this->debug( __METHOD__, 'WARNING: aborting analysis earlier due to max depth' );
			return;
		}
		if ( $node->kind === \ast\AST_CLOSURE && isset( $node->children['uses'] ) ) {
			return;
		}
		$depth++;

		// Like Analyzable::analyze, clone the context to avoid overriding anything
		$context = clone $func->getContext();
		// @phan-suppress-next-line PhanUndeclaredMethod All implementations have it
		if ( $func->getRecursionDepth() !== 0 ) {
			// Add the arguments types to the internal scope of the function, see
			// https://github.com/phan/phan/issues/3848
			foreach ( $func->getParameterList() as $parameter ) {
				$context->addScopeVariable( $parameter->cloneAsNonVariadic() );
			}
		}
		try {
			( new BlockAnalysisVisitor( $this->code_base, $context ) )(
				$node
			);
		} finally {
			$depth--;
		}
	}

	/**
	 * Sometimes we don't want NO_OVERRIDE.
	 *
	 * This is primarily used to ensure that no override doesn't
	 * propagate into other variables.
	 *
	 * @param array $taint Function taint
	 * @param bool $clear Whether to clear it or not
	 * @return array Function taint
	 */
	private function maybeClearNoOverride( array $taint, bool $clear ) : array {
		if ( !$clear ) {
			return $taint;
		}
		foreach ( $taint as &$t ) {
			$t &= ~SecurityCheckPlugin::NO_OVERRIDE;
		}
		return $taint;
	}

	/**
	 * Obtain taint information from a docblock comment.
	 *
	 * @param FunctionInterface $func The function to check
	 * @return null|int[] null for no info, or a function taint array
	 */
	protected function getDocBlockTaintOfFunc( FunctionInterface $func ) : ?array {
		// Note that we're not using the hashed docblock for caching, because the same docblock
		// may have different meanings in different contexts. E.g. @return self
		$fqsen = (string)$func->getFQSEN();
		if ( isset( SecurityCheckPlugin::$docblockCache[ $fqsen ] ) ) {
			return SecurityCheckPlugin::$docblockCache[ $fqsen ];
		}
		// @phan-suppress-next-line PhanUndeclaredMethod All FunctionInterface implementations have it
		if ( !$func->hasNode() ) {
			// No docblock available
			return null;
		}
		// Assume that if some of the taint is specified, then
		// the person would specify all the dangerous taints, so
		// don't set the unknown flag if not taint annotation on
		// @return.
		$funcTaint = [ 'overall' => SecurityCheckPlugin::NO_TAINT ];
		$docBlock = $func->getNode()->children['docComment'] ?? '';
		$lines = explode( "\n", $docBlock );
		$validTaintEncountered = false;

		foreach ( $lines as $line ) {
			$m = [];
			if ( preg_match( '/@param-taint &?\$(\S+)\s+(.*)$/', $line, $m ) ) {
				$paramNumber = $this->getParamNumberGivenName( $func, $m[1] );
				if ( $paramNumber === null ) {
					continue;
				}
				$taint = SecurityCheckPlugin::parseTaintLine( $m[2] );
				if ( $taint !== null ) {
					$funcTaint[$paramNumber] = $taint;
					$validTaintEncountered = true;
					if ( ( $taint & SecurityCheckPlugin::ESCAPES_HTML ) ===
						SecurityCheckPlugin::ESCAPES_HTML
					) {
						// Special case to auto-set anything that escapes html to detect double escaping.
						$funcTaint['overall'] |= SecurityCheckPlugin::ESCAPED_TAINT;
					}
				} else {
					$this->debug( __METHOD__, "Could not " .
						"understand taint line '$m[2]'" );
				}
			} elseif ( strpos( $line, '@return-taint' ) !== false ) {
				$taintLine = substr(
					$line,
					strpos( $line, '@return-taint' ) + strlen( '@return-taint' ) + 1
				);
				$taint = SecurityCheckPlugin::parseTaintLine( $taintLine );
				if ( $taint !== null ) {
					$funcTaint['overall'] = $taint;
					$validTaintEncountered = true;
				} else {
					$this->debug( __METHOD__, "Could not " .
						"understand return taint '$taintLine'" );
				}
			}
		}

		if ( !$validTaintEncountered ) {
			return null;
		}

		SecurityCheckPlugin::$docblockCache[ $fqsen ] = $funcTaint;
		if ( count( SecurityCheckPlugin::$docblockCache ) > 1000 ) {
			array_shift( SecurityCheckPlugin::$docblockCache );
		}
		return $funcTaint;
	}

	/**
	 * @param FunctionInterface $func
	 * @param string $name The name of parameter, no $ or & prefixed
	 * @return null|int null on no such parameter
	 */
	private function getParamNumberGivenName( FunctionInterface $func, string $name ) : ?int {
		$parameters = $func->getParameterList();
		foreach ( $parameters as $i => $param ) {
			if ( $name === $param->getName() ) {
				return $i;
			}
		}
		$this->debug( __METHOD__, $func->getName() . " does not have param $name" );
		return null;
	}

	/**
	 * Given a type, determine what type of taint
	 *
	 * e.g. Integers are probably untainted since its hard to do evil
	 * with them, but mark strings as unknown since we don't know.
	 *
	 * Only use as a fallback
	 * @param UnionType $types The types
	 * @return int The taint in question
	 */
	protected function getTaintByReturnType( UnionType $types ) : int {
		$taint = SecurityCheckPlugin::NO_TAINT;

		$typelist = $types->getTypeSet();
		if ( count( $typelist ) === 0 ) {
			// $this->debug( __METHOD__, "Setting type unknown due to no type info." );
			return SecurityCheckPlugin::UNKNOWN_TAINT;
		}
		foreach ( $typelist as $type ) {
			switch ( $type->getName() ) {
			case 'int':
			case 'non-zero-int':
			case 'float':
			case 'bool':
			case 'false':
			case 'true':
			case 'null':
			case 'void':
			case 'class-string':
			case 'callable-string':
			case 'callable-object':
			case 'callable-array':
				$taint = $this->mergeAddTaint( $taint, SecurityCheckPlugin::NO_TAINT );
				break;
			case 'string':
			case 'non-empty-string':
			case 'Closure':
			case 'callable':
			case 'array':
			case 'iterable':
			case 'object':
			case 'resource':
			case 'mixed':
			case 'non-empty-mixed':
				// $this->debug( __METHOD__, "Taint set unknown due to type '$type'." );
				$taint = $this->mergeAddTaint( $taint, SecurityCheckPlugin::UNKNOWN_TAINT );
				break;
			default:
				// This means specific class or a type not listed above (likely phan-specific)
				$fqsen = $type->isObjectWithKnownFQSEN() ? $type->asFQSEN() : $type->__toString();
				if ( !( $fqsen instanceof FullyQualifiedClassName ) ) {
					$this->debug( __METHOD__, " $type not a class?" );
					$taint = $this->mergeAddTaint( $taint, SecurityCheckPlugin::UNKNOWN_TAINT );
					break;
				}
				$toStringFQSEN = FullyQualifiedMethodName::fromStringInContext(
					$fqsen . '::__toString',
					$this->context
				);
				if ( !$this->code_base->hasMethodWithFQSEN( $toStringFQSEN ) ) {
					// This is common in a void context.
					// e.g. code like $this->foo() will reach this
					// check.
					$taint |= SecurityCheckPlugin::UNKNOWN_TAINT;
					break;
				}
				$toString = $this->code_base->getMethodByFQSEN( $toStringFQSEN );
				$taint |= $this->handleMethodCall( $toString, $toStringFQSEN, [] );
			}
		}
		return $taint;
	}

	/**
	 * Get the built in taint of a function/method
	 *
	 * This is used for when people special case if a function is tainted.
	 *
	 * @param FullyQualifiedFunctionLikeName $fqsen Function to check
	 * @return null|array Null if no info, otherwise the taint for the function
	 */
	protected function getBuiltinFuncTaint( FullyQualifiedFunctionLikeName $fqsen ) : ?array {
		$taint = SecurityCheckPlugin::$pluginInstance->getBuiltinFuncTaint( $fqsen );
		if ( $taint !== null ) {
			$this->checkFuncTaint( $taint );
		}
		return $taint;
	}

	/**
	 * Get name of current method (for debugging purposes)
	 *
	 * @return string Name of method or "[no method]"
	 */
	protected function getCurrentMethod() : string {
		return $this->context->isInFunctionLikeScope() ?
			(string)$this->context->getFunctionLikeFQSEN() : '[no method]';
	}

	/**
	 * Get the taintedness of something from the AST tree.
	 *
	 * @warning This does not take into account preexisting taint
	 *  unless you provide it with a Phan object (Not an AST node).
	 *
	 * FIXME maybe it should try and turn into phan object.
	 * @param Mixed $expr An expression from the AST tree.
	 * @return int The taint
	 */
	protected function getTaintedness( $expr ) : int {
		$type = gettype( $expr );
		switch ( $type ) {
		case "string":
		case "boolean":
		case "integer":
		case "double":
		case "NULL":
			// simple literal
			return SecurityCheckPlugin::NO_TAINT;
		case "object":
			if ( $expr instanceof Node ) {
				return $this->getTaintednessNode( $expr );
			}
			// fallthrough
		case "resource":
		case "unknown type":
		case "array":
		default:
			throw new Exception( __METHOD__ . " called with invalid type $type" );
		}
	}

	/**
	 * Give an AST node, find its taint
	 *
	 * @param Node $node
	 * @return int The taint
	 */
	protected function getTaintednessNode( Node $node ) : int {
		// Debug::printNode( $node );
		$ret = null;
		( new TaintednessVisitor( $this->code_base, $this->context, $ret ) )(
			$node
		);
		assert( $ret >= 0, $ret );
		return $ret;
	}

	/**
	 * Given a phan object (not method/function) find its taint
	 *
	 * @param TypedElementInterface $variableObj
	 * @return int The taint
	 */
	protected function getTaintednessPhanObj( TypedElementInterface $variableObj ) : int {
		if ( $variableObj instanceof FunctionInterface ) {
			throw new Exception( "This method cannot be used with methods" );
		}
		if ( $variableObj instanceof PassByReferenceVariable ) {
			throw new Exception( 'Handle PassByRefs before calling this method' );
		}
		if ( property_exists( $variableObj, 'taintedness' ) ) {
			$taintedness = $variableObj->taintedness;
			// echo "$varName has taintedness $taintedness due to last time\n";
		} else {
			$type = $variableObj->getUnionType();
			$taintedness = $this->getTaintByReturnType( $type );
			// $this->debug( " \$" . $variableObj->getName() . " first sight."
			// . " taintedness set to $taintedness due to type $type\n";
		}
		assert( is_int( $taintedness ) && $taintedness >= 0 );
		return $taintedness;
	}

	/**
	 * Quick wrapper to get the ContextNode for a node
	 *
	 * @param Node $node
	 * @return ContextNode
	 */
	protected function getCtxN( Node $node ) : ContextNode {
		return new ContextNode(
			$this->code_base,
			$this->context,
			$node
		);
	}

	/**
	 * Given a node, return the Phan variable objects that
	 * corespond to that node. Note, this will ignore
	 * things like method calls (for now at least).
	 *
	 * TODO: Maybe this should be a visitor class instead(?)
	 *
	 * This method is a little confused, because sometimes we only
	 * want the objects that materially contribute to taint, and
	 * other times we want all the objects.
	 * e.g. Should foo( $bar ) return the $bar variable object?
	 *  What about the foo function object?
	 *
	 * @param Node $node AST node in question
	 * @param string[] $options Change type of objects returned
	 *    * 'all' -> Given a method call, include the method and its args
	 *    * 'return' -> Given a method call, include objects in its return.
	 * @return TypedElementInterface[] Array of various phan objects corresponding to $node
	 */
	protected function getPhanObjsForNode( Node $node, $options = [] ) : array {
		$cn = $this->getCtxN( $node );

		switch ( $node->kind ) {
			case \ast\AST_PROP:
			case \ast\AST_STATIC_PROP:
				try {
					return [ $cn->getProperty( $node->kind === \ast\AST_STATIC_PROP ) ];
				} catch ( Exception $e ) {
					// There won't be an expr for static prop.
					if ( isset( $node->children['expr'] ) && $node->children['expr'] instanceof Node ) {
						$cnClass = $this->getCtxN( $node->children['expr'] );
						if ( $cnClass->getVariableName() === 'row' ) {
							// Its probably a db row, so ignore.
							// FIXME, we should handle the
							// db row situation much better.
							return [];
						}
					}

					$this->debug( __METHOD__, "Cannot determine " .
						"property [3] (Maybe don't know what class) - " .
						$this->getDebugInfo( $e )
					);
					return [];
				}
			case \ast\AST_VAR:
			case \ast\AST_CLOSURE_VAR:
				try {
					if ( Variable::isHardcodedGlobalVariableWithName( $cn->getVariableName() ) ) {
						return [];
					} else {
						return [ $cn->getVariable() ];
						// return [];
					}
				} catch ( Exception $e ) {
					$this->debug( __METHOD__, "variable not in scope?? " . $this->getDebugInfo( $e ) );
					return [];
				}
			case \ast\AST_ENCAPS_LIST:
			case \ast\AST_ARRAY:
				$results = [];
				foreach ( $node->children as $child ) {
					if ( !is_object( $child ) ) {
						continue;
					}
					$results = array_merge( $this->getPhanObjsForNode( $child, $options ), $results );
				}
				return $results;
			case \ast\AST_ARRAY_ELEM:
				$results = [];
				if ( is_object( $node->children['key'] ) ) {
					$results = array_merge(
						$this->getPhanObjsForNode( $node->children['key'], $options ),
						$results
					);
				}
				if ( is_object( $node->children['value'] ) ) {
					$results = array_merge(
						$this->getPhanObjsForNode( $node->children['value'], $options ),
						$results
					);
				}
				return $results;
			case \ast\AST_CAST:
				// Future todo might be to ignore casts to ints, since
				// such things should be safe. Unclear if that makes
				// sense in all circumstances.
				if ( $node->children['expr'] instanceof Node ) {
					return $this->getPhanObjsForNode( $node->children['expr'], $options );
				}
				return [];
			case \ast\AST_DIM:
				if ( $node->children['expr'] instanceof Node ) {
					// For now just consider the outermost array.
					// FIXME. doesn't handle tainted array keys!
					return $this->getPhanObjsForNode( $node->children['expr'], $options );
				}
				return [];
			case \ast\AST_UNARY_OP:
				$var = $node->children['expr'];
				return $var instanceof Node ? $this->getPhanObjsForNode( $var, $options ) : [];
			case \ast\AST_BINARY_OP:
				$left = $node->children['left'];
				$right = $node->children['right'];
				$leftObj = $left instanceof Node ? $this->getPhanObjsForNode( $left, $options ) : [];
				$rightObj = $right instanceof Node ? $this->getPhanObjsForNode( $right, $options ) : [];
				return array_merge( $leftObj, $rightObj );
			case \ast\AST_CONDITIONAL:
				$t = $node->children['true'];
				$f = $node->children['false'];
				$tObj = $t instanceof Node ? $this->getPhanObjsForNode( $t, $options ) : [];
				$fObj = $f instanceof Node ? $this->getPhanObjsForNode( $f, $options ) : [];
				return array_merge( $tObj, $fObj );
			case \ast\AST_CONST:
			case \ast\AST_CLASS_CONST:
			case \ast\AST_CLASS_NAME:
			case \ast\AST_MAGIC_CONST:
			case \ast\AST_ISSET:
			case \ast\AST_NEW:
			// For now we don't do methods, only variables
			// Also don't do args to function calls.
			// Unclear if this makes sense.
				return [];
			case \ast\AST_CALL:
			case \ast\AST_STATIC_CALL:
			case \ast\AST_METHOD_CALL:
				if ( !$options ) {
					return [];
				}
				try {
					$ctxNode = $this->getCtxN( $node );
					if ( $node->kind === \ast\AST_CALL ) {
						if ( $node->children['expr']->kind !== \ast\AST_NAME ) {
							return [];
						}
						$func = $ctxNode->getFunction( $node->children['expr']->children['name'] );
					} else {
						$methodName = $node->children['method'];
						$func = $ctxNode->getMethod(
							$methodName,
							$node->kind === \ast\AST_STATIC_CALL
						);
					}
					if ( in_array( 'return', $options ) ) {
						// intentionally resetting options to []
						// here to ensure we don't recurse beyond
						// a depth of 1.
						return $this->getReturnObjsOfFunc( $func );
					}
					$args = $node->children['args']->children;
					$pObjs = [ $func ];
					foreach ( $args as $arg ) {
						if ( !( $arg instanceof Node ) ) {
							continue;
						}
						$pObjs = array_merge(
							$pObjs,
							$this->getPhanObjsForNode( $arg, $options )
						);
					}
					return $pObjs;
				} catch ( Exception $_ ) {
					// Something non-simple
					// @todo Future todo might be to still return arguments in this case.
					return [];
				}
			case \ast\AST_PRE_INC:
			case \ast\AST_PRE_DEC:
			case \ast\AST_POST_INC:
			case \ast\AST_POST_DEC:
				$children = $node->children;
				assert( count( $children ) === 1 );
				return $this->getPhanObjsForNode( reset( $children ) );
			default:
				// Debug::printNode( $node );
				// This should really be a visitor that recurses into
				// things.
				$this->debug( __METHOD__, "FIXME unhandled case"
					. Debug::nodeName( $node ) . "\n"
				);
				return [];
		}
	}

	/**
	 * Extract some useful debug data from an exception
	 * @param Exception $e
	 * @return string
	 */
	protected function getDebugInfo( Exception $e ) : string {
		return $e instanceof IssueException
			? $e->getIssueInstance()->__toString()
			: ( get_class( $e ) . " {$e->getMessage()}" );
	}

	/**
	 * Whether a variable can be considered a superglobal. Phan doesn't consider $argv and $argc
	 * as such, but for our use case, they should be.
	 * @param string $varName
	 * @return bool
	 */
	protected function isSuperGlobal( $varName ) : bool {
		return Variable::isSuperglobalVariableWithName( $varName ) ||
			$varName === 'argv' || $varName === 'argc';
	}

	/**
	 * Get the current filename and line.
	 *
	 * @param Context|null $context Override the context to make debug info for
	 * @return string path/to/file +linenumber
	 */
	protected function dbgInfo( Context $context = null ) : string {
		$ctx = $context ?: $this->context;
		// Using a + instead of : so that I can just copy and paste
		// into a vim command line.
		return ' ' . $ctx->getFile() . ' +' . $ctx->getLineNumberStart();
	}

	/**
	 * Link together a Method and its parameters
	 *
	 * The idea being if the method gets called with something evil
	 * later, we can traceback anything it might affect
	 *
	 * @param Variable $param The variable object for the parameter. This can also be
	 *  instance of Parameter (subclass of Variable).
	 * @param FunctionInterface $func The function/method in question
	 * @param int $i Which argument number is $param
	 */
	protected function linkParamAndFunc( Variable $param, FunctionInterface $func, int $i ) : void {
		// $this->debug( __METHOD__, "Linking '$param' to '$func' arg $i" );

		if ( !property_exists( $func, 'taintedVarLinks' ) ) {
			$func->taintedVarLinks = [];
		}
		if ( !isset( $func->taintedVarLinks[$i] ) ) {
			$func->taintedVarLinks[$i] = new Set;
		}
		if ( !property_exists( $param, 'taintedMethodLinks' ) ) {
			// This is a map of FunctionInterface -> int[]
			$param->taintedMethodLinks = new Set;
		}

		$func->taintedVarLinks[$i]->attach( $param );
		if ( $param->taintedMethodLinks->contains( $func ) ) {
			$data = $param->taintedMethodLinks[$func];
			$data[$i] = true;
			$param->taintedMethodLinks[$func] = $data;
		} else {
			$param->taintedMethodLinks[$func] = [ $i => true ];
		}
	}

	/**
	 * Given a LHS and RHS make all the methods that can set RHS also for LHS
	 *
	 * Given 2 variables (e.g. $lhs = $rhs ), see to it that any function/method
	 * which we marked as being able to set the value of rhs, is also marked
	 * as being able to set the value of lhs. We use this information to figure
	 * out what method parameter is causing the return statement to be tainted.
	 *
	 * @warning Be careful calling this function if lhs already has taint
	 *  or rhs side is a compound statement. This could result in misattribution
	 *  of where the taint is coming from.
	 *
	 * This also merges the information on what line caused the taint.
	 *
	 * @param TypedElementInterface $lhs Source of method list
	 * @param TypedElementInterface $rhs Destination of merged method list
	 */
	protected function mergeTaintDependencies(
		TypedElementInterface $lhs,
		TypedElementInterface $rhs
	) : void {
		// $this->debug( __METHOD__, "merging $lhs <- $rhs" );
		$taintRHS = $this->getTaintednessPhanObj( $rhs );

		if ( $taintRHS &
			( SecurityCheckPlugin::ALL_EXEC_TAINT | SecurityCheckPlugin::ALL_TAINT )
		) {
			$this->mergeTaintError( $lhs, $rhs );
		}

		if ( !property_exists( $rhs, 'taintedMethodLinks' ) ) {
			// $this->debug( __METHOD__, "FIXME no back links on preserved taint" );
			return;
		}

		if ( !property_exists( $lhs, 'taintedMethodLinks' ) ) {
			$lhs->taintedMethodLinks = new Set;
		}

		// So if we have $a = $b;
		// First we find out all the methods that can set $b
		// Then we add $a to the list of variables that those methods can set.
		// Last we add these methods to $a's list of all methods that can set it.
		foreach ( $rhs->taintedMethodLinks as $method ) {
			$paramInfo = $rhs->taintedMethodLinks[$method];
			foreach ( $paramInfo as $index => $_ ) {
				assert( property_exists( $method, 'taintedVarLinks' ) );
				assert( isset( $method->taintedVarLinks[$index] ) );
				assert( $method->taintedVarLinks[$index] instanceof Set );
				// $this->debug( __METHOD__, "During assignment, we link $lhs to $method($index)" );
				$method->taintedVarLinks[$index]->attach( $lhs );
			}
			if ( isset( $lhs->taintedMethodLinks[$method] ) ) {
				$lhs->taintedMethodLinks[$method] += $paramInfo;
			} else {
				$lhs->taintedMethodLinks[ $method ] = $paramInfo;
			}
		}
	}

	/**
	 * Mark any function setting a specific variable as EXEC taint
	 *
	 * If you do something like echo $this->foo;
	 * This method is called to make all things that set $this->foo
	 * as TAINT_EXEC.
	 *
	 * @param TypedElementInterface $var The variable in question
	 * @param int $taint What taint to mark them as.
	 */
	protected function markAllDependentMethodsExec(
		TypedElementInterface $var,
		int $taint = SecurityCheckPlugin::EXEC_TAINT
	) : void {
		// Ensure we only set exec bits, not normal taint bits.
		$taint &= SecurityCheckPlugin::BACKPROP_TAINTS;

		if ( $var instanceof PassByReferenceVariable ) {
			$var = $this->extractReferenceArgument( $var );
		}
		if (
			$taint === 0 ||
			$this->isIssueSuppressedOrFalsePositive( $taint ) ||
			!property_exists( $var, 'taintedMethodLinks' ) ||
			!count( $var->taintedMethodLinks )
		) {
			return;
		}

		if (
			$var instanceof Property && (
				(
					$this->context->isInClassScope() &&
					$this->context->getClassInScope( $this->code_base ) !== $var->getClass( $this->code_base )
				) ||
				$var->getContext()->getFile() !== $this->context->getFile()
			)
		) {
			// @todo This should be tweaked. The idea behind this check is:
			// - Let properties affect methods within the same class / the same file (depending
			// on whether we're currently in class scope), e.g. for getters/setters
			// - Forbid any other taintedness transfer. See the test 'user2' for the reason.
			return;
		}

		$oldMem = memory_get_peak_usage();

		foreach ( $var->taintedMethodLinks as $method ) {
			$paramInfo = $var->taintedMethodLinks[$method];
			$paramTaint = [ 'overall' => SecurityCheckPlugin::NO_TAINT ];
			foreach ( $paramInfo as $i => $_ ) {
				$paramTaint[$i] = $taint;
				// $this->debug( __METHOD__, "Setting method $method" .
					// " arg $i as $taint due to depenency on $var" );
			}
			$this->setFuncTaint( $method, $paramTaint );
		}

		if ( $var instanceof Property || property_exists( $var, 'isGlobalVariable' ) ) {
			// For local variables, don't set the taint: the taintedness set here should only be used
			// when examining a function call. Inside the function body, we'll already have all the
			// info we need, and actually, this extra taint would cause false positives with variable
			// names reuse.
			$curVarTaint = $this->getTaintednessPhanObj( $var );
			$newTaint = $this->mergeAddTaint( $curVarTaint, $taint );
			$this->setTaintedness( $var, $newTaint );
		}

		$newMem = memory_get_peak_usage();
		$diffMem = round( ( $newMem - $oldMem ) / ( 1024 * 1024 ) );
		if ( $diffMem > 2 ) {
			$this->debug( __METHOD__, "Memory spike $diffMem for variable " . $var->getName() );
		}
	}

	/**
	 * This happens when someone calls foo( $evilTaintedVar );
	 *
	 * It makes sure that any variable that the function foo() sets takes on
	 * the taint of the supplied argument.
	 *
	 * @param FunctionInterface $method The function or method in question
	 * @param int $i The number of the argument in question.
	 * @param int $taint The taint to apply.
	 */
	protected function markAllDependentVarsYes(
		FunctionInterface $method,
		int $i,
		int $taint
	) : void {
		$taintAdjusted = $taint & SecurityCheckPlugin::ALL_TAINT;
		if ( $method->isPHPInternal() ) {
			return;
		}
		if (
			!property_exists( $method, 'taintedVarLinks' )
			|| !isset( $method->taintedVarLinks[$i] )
		) {
			$this->debug( __METHOD__, "returning early no backlinks" );
			return;
		}
		$oldMem = memory_get_peak_usage();
		// If we mark a class member as being tainted, we recheck all the
		// methods of the class, as the previous taint of the methods may
		// have assumed the class member was not tainted.
		$classesNeedRefresh = new Set;
		foreach ( $method->taintedVarLinks[$i] as $var ) {
			assert( $var instanceof TypedElementInterface );
			$curVarTaint = $this->getTaintednessPhanObj( $var );
			$newTaint = $this->mergeAddTaint( $curVarTaint, $taintAdjusted );
			// $this->debug( __METHOD__, "handling $var as dependent yes" .
			// " of $method($i). Prev=$curVarTaint; new=$newTaint" );
			$this->setTaintedness( $var, $newTaint );
			if (
				$this->isAllTaint( $newTaint ^ $curVarTaint ) &&
				$var instanceof ClassElement
			) {
				// TODO: This is subpar -
				// * Its inefficient, reanalyzing much more than needed.
				// * It doesn't handle parent classes properly
				// * For public class members, it wouldn't catch uses
				// outside of the member's own class.
				$classesNeedRefresh->attach( $var->getClass( $this->code_base ) );
			}
		}
		foreach ( $classesNeedRefresh as $class ) {
			foreach ( $class->getMethodMap( $this->code_base ) as $classMethod ) {
				$this->debug( __METHOD__, "reanalyze $classMethod" );
				$this->analyzeFunc( $classMethod );
			}
		}
		// Maybe delete links??
		$newMem = memory_get_peak_usage();
		$diffMem = round( ( $newMem - $oldMem ) / ( 1024 * 1024 ) );
		if ( $diffMem > 2 ) {
			$this->debug( __METHOD__, "Memory spike $diffMem for method {$method->getName()}" );
		}
	}

	/**
	 * Are any of the positive (i.e HTML_TAINT) taint flags set
	 *
	 * @param int $taint
	 * @return bool If the variable has known (non-execute taint)
	 */
	protected function isAllTaint( $taint ) : bool {
		return ( $taint & SecurityCheckPlugin::ALL_TAINT ) !== 0;
	}

	/**
	 * Does the taint have one of EXEC flags set
	 *
	 * @param int $taint
	 * @return bool If the variable has any exec taint
	 */
	protected function isExecTaint( $taint ) : bool {
		return ( $taint & SecurityCheckPlugin::ALL_EXEC_TAINT ) !== 0;
	}

	/**
	 * Convert the yes taint bits to corresponding exec taint bits.
	 *
	 * Any UNKNOWN_TAINT or INAPPLICABLE_TAINT is discarded.
	 *
	 * @param int $taint
	 * @return int The converted taint
	 */
	protected function yesToExecTaint( int $taint ) : int {
		return ( $taint & SecurityCheckPlugin::ALL_TAINT ) << 1;
	}

	/**
	 * Convert exec to yes taint
	 *
	 * Special flags like UNKNOWN or INAPPLICABLE are discarded.
	 * Any YES flags are also discarded
	 *
	 * @param int $taint The taint in question
	 * @return int The taint with all the EXEC to yes, and all other flags off
	 */
	protected function execToYesTaint( int $taint ) : int {
		return ( $taint & SecurityCheckPlugin::ALL_EXEC_TAINT ) >> 1;
	}

	/**
	 * Whether merging the rhs to lhs is an safe operation
	 *
	 * @param int $lhs Taint of left hand side
	 * @param int $rhs Taint of right hand side
	 * @return bool Is it safe
	 */
	protected function isSafeAssignment( $lhs, $rhs ) : bool {
		$adjustRHS = $this->yesToExecTaint( $rhs );
		// $this->debug( __METHOD__, "lhs=$lhs; rhs=$rhs, adjustRhs=$adjustRHS" );
		return ( $adjustRHS & $lhs ) === 0 &&
			!(
				( $lhs & SecurityCheckPlugin::ALL_EXEC_TAINT ) &&
				( $rhs & SecurityCheckPlugin::UNKNOWN_TAINT )
			);
	}

	/**
	 * Is taint likely a false positive
	 *
	 * Taint is a false positive if it has the unknown flag but
	 * none of the yes flags.
	 *
	 * @param int $taint
	 * @return bool
	 * @suppress PhanUnreferencedProtectedMethod It's really unused though
	 */
	protected function isLikelyFalsePositive( int $taint ) : bool {
		return ( $taint & SecurityCheckPlugin::UNKNOWN_TAINT ) !== 0
			&& ( $taint & SecurityCheckPlugin::YES_TAINT ) === 0;
	}

	/**
	 * Get the line number of the original cause of taint.
	 *
	 * @param TypedElementInterface|Node $element
	 * @param int $arg [optional] For functions what arg. -1 for overall.
	 * @return string
	 */
	protected function getOriginalTaintLine( $element, $arg = -1 ) : string {
		$line = $this->getOriginalTaintLineRaw( $element, $arg );
		if ( $line ) {
			$line = substr( $line, 0, - 1 );
			return " (Caused by:$line)";
		} else {
			return '';
		}
	}

	/**
	 * Get the line number of the original cause of taint without "Caused by" string.
	 *
	 * @param TypedElementInterface|Node $element
	 * @param int $arg [optional] For functions what arg. -1 for overall.
	 * @return string
	 */
	private function getOriginalTaintLineRaw( $element, $arg = - 1 ) : string {
		if ( !is_object( $element ) ) {
			return '';
		}

		$line = '';
		if ( $element instanceof TypedElementInterface ) {
			if ( $arg === -1 ) {
				if ( $element instanceof PassByReferenceVariable ) {
					$element = $this->extractReferenceArgument( $element );
				}
				if ( property_exists( $element, 'taintedOriginalError' ) ) {
					$line = $element->taintedOriginalError;
				}
				foreach ( $element->taintedOriginalErrorByArg ?? [] as $origArg ) {
					// FIXME is this right? In the generic
					// case should we include all arguments as
					// well?
					$line .= $origArg;
				}
			} else {
				assert( $element instanceof FunctionInterface );
				$line .= $element->taintedOriginalErrorByArg[$arg] ?? '';
			}
		} elseif ( $element instanceof Node ) {
			$pobjs = $this->getPhanObjsForNode( $element );
			foreach ( $pobjs as $elem ) {
				$line .= $this->getOriginalTaintLineRaw( $elem );
			}
			if ( $line === '' ) {
				// try to dig deeper.
				// This will also include method calls and whatnot.
				// FIXME should we always do this? Is it too spammy.
				$pobjs = $this->getPhanObjsForNode( $element, [ 'all' ] );
				foreach ( $pobjs as $elem ) {
					$line .= $this->getOriginalTaintLineRaw( $elem );
				}
			}
		} else {
			throw new Exception(
				$this->dbgInfo() . "invalid parameter "
				. get_class( $element )
			);
		}
		assert( strlen( $line ) < 8096, " taint error too long $line" );
		return $line;
	}

	/**
	 * Match an expressions taint to func arguments
	 *
	 * Given an ast expression (node, or literal value) try and figure
	 * out which of the current function's parameters its taint came
	 * from.
	 *
	 * @param Mixed $node Either a Node or a string, int, etc. The expression
	 * @param int $taintedness The taintedness in question
	 * @param FunctionInterface $curFunc The function/method we are in.
	 * @return array numeric keys for each parameter taint and 'overall' key
	 */
	protected function matchTaintToParam(
		$node,
		int $taintedness,
		FunctionInterface $curFunc
	) : array {
		assert( $taintedness >= 0, "Taintedness: $taintedness" );
		if ( !is_object( $node ) ) {
			assert( $taintedness === SecurityCheckPlugin::NO_TAINT );
			return [ 'overall' => $taintedness ];
		}

		// Try to match up the taintedness of the return expression
		// to which parameter caused the taint. This will only work
		// in relatively simple cases.
		// $taintRemaining is any taint we couldn't attribute.
		$taintRemaining = $taintedness;
		// $paramTaint is taint we attribute to each param
		$paramTaint = [];
		// $otherTaint is taint contributed by other things.
		$otherTaint = SecurityCheckPlugin::NO_TAINT;

		$pobjs = $this->getPhanObjsForNode( $node );
		foreach ( $pobjs as $pobj ) {
			if ( $pobj instanceof PassByReferenceVariable ) {
				$pobj = $this->extractReferenceArgument( $pobj );
			}
			$pobjTaintContribution = $this->getTaintednessPhanObj( $pobj );
			// $this->debug( __METHOD__, "taint for $pobj is $pobjTaintContribution" );
			$links = $pobj->taintedMethodLinks ?? null;
			if ( !$links ) {
				// No method links.
				// $this->debug( __METHOD__, "no method links for $pobj in " . $curFunc->getFQSEN() );
				// If its a non-private property, try getting parent class
				if ( $pobj instanceof Property && !$pobj->isPrivate() ) {
					$this->debug( __METHOD__, "FIXME should check parent class of $pobj" );
				}
				$otherTaint |= $pobjTaintContribution;
				$taintRemaining &= ~$pobjTaintContribution;
				continue;
			}

			/** @var Set $links Its not a normal array */
			foreach ( $links as $func ) {
				/** @var $paramInfo array Array of int -> true */
				$paramInfo = $links[$func];
				if ( (string)( $func->getFQSEN() ) === (string)( $curFunc->getFQSEN() ) ) {
					foreach ( $paramInfo as $i => $_ ) {
						$paramTaint[$i] = $pobjTaintContribution;
						$taintRemaining &= ~$pobjTaintContribution;
					}
				} else {
					$taintRemaining &= ~$pobjTaintContribution;
					$otherTaint |= $pobjTaintContribution;
				}
			}
		}
		$paramTaint['overall'] = ( $otherTaint | $taintRemaining ) &
			$taintedness;
		return $paramTaint;
	}

	/**
	 * Output a debug message to stdout.
	 *
	 * @param string $method __METHOD__ in question
	 * @param string $msg debug message
	 */
	public function debug( $method, $msg ) : void {
		if ( $this->debugOutput === null ) {
			$errorOutput = getenv( "SECCHECK_DEBUG" );
			if ( $errorOutput && $errorOutput !== '-' ) {
				$this->debugOutput = fopen( $errorOutput, "w" );
			} elseif ( $errorOutput === '-' ) {
				$this->debugOutput = '-';
			} else {
				$this->debugOutput = false;
			}
		}
		$line = $method . "\33[1m" . $this->dbgInfo() . " \33[0m" . $msg . "\n";
		if ( $this->debugOutput && $this->debugOutput !== '-' ) {
			fwrite(
				$this->debugOutput,
				$line
			);
		} elseif ( $this->debugOutput === '-' ) {
			echo $line;
		}
	}

	/**
	 * Make sure func taint array is well formed
	 *
	 * @warning Will cause an assertion failure if not well formed.
	 *
	 * @param array $taint the taint of a function
	 */
	protected function checkFuncTaint( array $taint ) : void {
		assert(
			isset( $taint['overall'] )
			&& is_int( $taint['overall'] )
			&& $taint['overall'] >= 0,
			"Overall taint is wrong " . $this->dbgInfo() . ( $taint['overall'] ?? 'unset' )
		);

		foreach ( $taint as $i => $t ) {
			if ( !( is_int( $t ) && $t >= 0 ) ) {
				throw new AssertionError( "Taint index $i wrong $t" . $this->dbgInfo() );
			}
		}
	}

	/**
	 * Given an AST node that's a callable, try and determine what it is
	 *
	 * This is intended for functions that register callbacks. It will
	 * only really work for callbacks that are basically literals.
	 *
	 * @note $node may not be the current node in $this->context.
	 *
	 * @param Node|string $node The thingy from AST expected to be a Callable
	 * @return FullyQualifiedMethodName|FullyQualifiedFunctionName|null The corresponding FQSEN
	 */
	protected function getFQSENFromCallable( $node ) {
		$callback = null;
		if ( is_string( $node ) ) {
			// Easy case, 'Foo::Bar'
			if ( strpos( $node, '::' ) === false ) {
				$callback = FullyQualifiedFunctionName::fromFullyQualifiedString(
					$node
				);
			} else {
				$callback = FullyQualifiedMethodName::fromFullyQualifiedString(
					$node
				);
			}
		} elseif ( $node instanceof Node && $node->kind === \ast\AST_CLOSURE ) {
			$method = (
				new ContextNode(
					$this->code_base,
					$this->context->withLineNumberStart(
						$node->lineno ?? 0
					),
					$node
				)
			)->getClosure();
			$callback = $method->getFQSEN();
		} elseif (
			$node instanceof Node
			&& $node->kind === \ast\AST_VAR
			&& is_string( $node->children['name'] )
		) {
			$cnode = $this->getCtxN( $node );
			$var = $cnode->getVariable();
			$types = $var->getUnionType()->getTypeSet();
			foreach ( $types as $type ) {
				if (
					( $type instanceof CallableType || $type instanceof ClosureType ) &&
					$type->asFQSEN() instanceof FullyQualifiedFunctionLikeName
				) {
					// @todo FIXME This doesn't work if the closure
					// is defined in a different function scope
					// then the one we are currently in. Perhaps
					// we could look up the closure in
					// $this->code_base to figure out what func
					// its defined on via its parent scope. Or
					// something.
					$callback = $type->asFQSEN();
					break;
				}
			}
		} elseif ( $node instanceof Node && $node->kind === \ast\AST_ARRAY ) {
			if ( count( $node->children ) !== 2 ) {
				return null;
			}
			if (
				$node->children[0]->children['key'] !== null ||
				$node->children[1]->children['key'] !== null ||
				!is_string( $node->children[1]->children['value'] )
			) {
				return null;
			}
			$methodName = $node->children[1]->children['value'];
			$classNode = $node->children[0]->children['value'];
			if ( is_string( $node->children[0]->children['value'] ) ) {
				$className = $classNode;
			} elseif ( $classNode instanceof Node ) {
				switch ( $classNode->kind ) {
				case \ast\AST_MAGIC_CONST:
					// Mostly a special case for MediaWiki
					// CoreParserFunctions.php
					if (
						( $classNode->flags & \ast\flags\MAGIC_CLASS ) !== 0
						&& $this->context->isInClassScope()
					) {
						$className = (string)$this->context->getClassFQSEN();
					} else {
						return null;
					}
					break;
				case \ast\AST_CLASS_NAME:
					if (
						$classNode->children['class']->kind === \ast\AST_NAME &&
						is_string( $classNode->children['class']->children['name'] )
					) {
						$className = $classNode->children['class']->children['name'];
					} else {
						return null;
					}
					break;
				case \ast\AST_CLASS_CONST:
					return null;
				case \ast\AST_VAR:
				case \ast\AST_PROP:
					$var = $classNode->kind === \ast\AST_VAR
						? $this->getCtxN( $classNode )->getVariable()
						: $this->getCtxN( $classNode )->getProperty( false );
					$type = $var->getUnionType();
					if ( $type->typeCount() !== 1 || $type->isScalar() ) {
						return null;
					}
					$cl = $type->asClassList(
						$this->code_base,
						$this->context
					);
					$clazz = false;
					foreach ( $cl as $item ) {
						$clazz = $item;
						break;
					}
					if ( !$clazz ) {
						return null;
					}
					$className = (string)$clazz->getFQSEN();
					break;
				default:
					return null;
				}

			} else {
				return null;
			}
			// Note, not from in context, since this goes to call_user_func.
			$callback = FullyQualifiedMethodName::fromFullyQualifiedString(
				$className . '::' . $methodName
			);
		} else {
			return null;
		}

		if (
			( $callback instanceof FullyQualifiedMethodName &&
			$this->code_base->hasMethodWithFQSEN( $callback ) )
			|| ( $callback instanceof FullyQualifiedFunctionName &&
			 $this->code_base->hasFunctionWithFQSEN( $callback ) )
		) {
			return $callback;
		} else {
			// @todo Should almost emit a non-security issue for this
			$this->debug( __METHOD__, "Missing Callable $callback" );
			return null;
		}
	}

	/**
	 * Get the issue name and severity given a taint
	 *
	 * @param int $combinedTaint The taint to warn for. I.e. The exec flags
	 *   from LHS shifted to non-exec bitwise AND'd with the rhs taint.
	 * @return array Issue type and severity
	 */
	public function taintToIssueAndSeverity( int $combinedTaint ) : array {
		$severity = Issue::SEVERITY_NORMAL;

		switch ( $combinedTaint ) {
			case SecurityCheckPlugin::HTML_TAINT:
				$issueType = 'SecurityCheck-XSS';
				break;
			case SecurityCheckPlugin::SQL_TAINT:
			case SecurityCheckPlugin::SQL_NUMKEY_TAINT:
			case SecurityCheckPlugin::SQL_TAINT | SecurityCheckPlugin::SQL_NUMKEY_TAINT:
				$issueType = 'SecurityCheck-SQLInjection';
				$severity = Issue::SEVERITY_CRITICAL;
				break;
			case SecurityCheckPlugin::SHELL_TAINT:
				$issueType = 'SecurityCheck-ShellInjection';
				$severity = Issue::SEVERITY_CRITICAL;
				break;
			case SecurityCheckPlugin::SERIALIZE_TAINT:
				$issueType = 'SecurityCheck-PHPSerializeInjection';
				// For now this is low because it seems to have a lot
				// of false positives.
				// $severity = 4;
				break;
			case SecurityCheckPlugin::ESCAPED_TAINT:
				$issueType = 'SecurityCheck-DoubleEscaped';
				break;
			case SecurityCheckPlugin::CUSTOM1_TAINT:
				$issueType = 'SecurityCheck-CUSTOM1';
				break;
			case SecurityCheckPlugin::CUSTOM2_TAINT:
				$issueType = 'SecurityCheck-CUSTOM2';
				break;
			case SecurityCheckPlugin::MISC_TAINT:
				$issueType = 'SecurityCheck-OTHER';
				break;
			default:
				$issueType = 'SecurityCheckMulti';
				if ( $combinedTaint & ( SecurityCheckPlugin::SHELL_TAINT | SecurityCheckPlugin::SQL_TAINT ) ) {
					$severity = Issue::SEVERITY_CRITICAL;
				}
		}

		return [ $issueType, $severity ];
	}

	/**
	 * Emit an issue using the appropriate issue type
	 *
	 * If $this->overrideContext is set, it will use that for the
	 * file/line number to report. This is meant as a hack, so that
	 * in MW we can force hook related issues to be in the extension
	 * instead of where the hook is called from in MW core.
	 *
	 * @param int $lhsTaint Taint of left hand side (or equivalent)
	 * @param int $rhsTaint Taint of right hand side (or equivalent)
	 * @param string $msg Issue description
	 */
	public function maybeEmitIssue( int $lhsTaint, int $rhsTaint, string $msg ) : void {
		if ( ( $lhsTaint & SecurityCheckPlugin::RAW_PARAM ) === SecurityCheckPlugin::RAW_PARAM ) {
			$msg .= ' (Param is raw)';
			$lhsTaint = $this->yesToExecTaint( $lhsTaint & ~SecurityCheckPlugin::RAW_PARAM );
		}
		if ( $this->isSafeAssignment( $lhsTaint, $rhsTaint ) ) {
			return;
		}

		$adjustLHS = $this->execToYesTaint( $lhsTaint );
		$combinedTaint = $rhsTaint & $adjustLHS;
		if (
			( $combinedTaint === 0 &&
			$rhsTaint & SecurityCheckPlugin::UNKNOWN_TAINT ) ||
			SecurityCheckPlugin::$pluginInstance->isFalsePositive(
				$adjustLHS,
				$rhsTaint,
				$msg,
				// FIXME should this be $this->overrideContext ?
				$this->context,
				$this->code_base
			)
		) {
			$issueType = 'SecurityCheck-LikelyFalsePositive';
			$severity = Issue::SEVERITY_LOW;
		} else {
			list( $issueType, $severity ) = $this->taintToIssueAndSeverity(
				$combinedTaint
			);
		}

		// If we have multiple, include what types.
		if ( $issueType === 'SecurityCheckMulti' ) {
			$msg .= " ($lhsTaint <- $rhsTaint)";
		}

		$context = $this->context;
		if ( $this->overrideContext ) {
			// If we are overriding the file/line number,
			// report the original line number as well.
			$msg .= " (Originally at: $this->context)";
			$context = $this->overrideContext;
		}

		SecurityCheckPlugin::emitIssue(
			$this->code_base,
			$context,
			$issueType,
			$msg,
			[],
			$severity
		);
	}

	/**
	 * Method to determine if a potential error isn't really real
	 *
	 * This is useful when a specific warning would have a side effect
	 * and we want to know whether we should suppress the side effect in
	 * addition to the warning.
	 *
	 * @param int $lhsTaint Must have at least one EXEC flag set
	 * @return bool
	 */
	public function isIssueSuppressedOrFalsePositive( $lhsTaint ) : bool {
		assert( ( $lhsTaint & SecurityCheckPlugin::ALL_EXEC_TAINT ) !== 0 );
		$context = $this->overrideContext ?: $this->context;
		$adjustLHS = $this->execToYesTaint( $lhsTaint );
		list( $issueType ) = $this->taintToIssueAndSeverity( $adjustLHS );

		if ( $context->hasSuppressIssue( $this->code_base, $issueType ) ) {
			return true;
		}

		$msg = "[dummy msg for false positive check]";
		return SecurityCheckPlugin::$pluginInstance->isFalsePositive(
			$adjustLHS,
			$adjustLHS,
			$msg,
			// not using $this->overrideContext to be consistent with maybeEmitIssue()
			$this->context,
			$this->code_base
		);
	}

	/**
	 * Somebody invokes a method or function (or something similar)
	 *
	 * This has to figure out:
	 *  Is the return value of the call tainted
	 *  Are any of the arguments tainted
	 *  Does the function do anything scary with its arguments
	 * It also has to maintain quite a bit of book-keeping.
	 *
	 * @param FunctionInterface $func
	 * @param FullyQualifiedFunctionLikeName $funcName
	 * @param array $args Arguments to function/method
	 * @return int Taint The resulting taint of the expression
	 */
	public function handleMethodCall(
		FunctionInterface $func,
		FullyQualifiedFunctionLikeName $funcName,
		array $args
	) : int {
		$oldMem = memory_get_peak_usage();
		$taint = $this->getTaintOfFunction( $func );
		$this->checkFuncTaint( $taint );

		// We need to look at the taintedness of the arguments
		// we are passing to the method.
		$overallArgTaint = SecurityCheckPlugin::NO_TAINT;
		foreach ( $args as $i => $argument ) {
			if ( !( $argument instanceof Node ) ) {
				// Literal value
				continue;
			}

			list( $curArgTaintedness, $effectiveArgTaintedness ) = $this->getArgTaint(
				$taint, $argument, $i, $funcName
			);

			// If this is a call by reference parameter,
			// link the taintedness variables.
			$param = $func->getParameterForCaller( $i );
			// @todo Internal funcs that pass by reference. Should we
			// assume that their variables are tainted? Most common
			// example is probably preg_match, which may very well be
			// tainted much of the time.
			if ( $param && $param->isPassByReference() && !$func->isPHPInternal() ) {
				$this->handlePassByRef( $func, $param, $argument, $i );
			}

			// We are doing something like someFunc( $evilArg );
			// Propogate that any vars set by someFunc should now be
			// marked tainted.
			// FIXME: We also need to handle the case where
			// someFunc( $execArg ) for pass by reference where
			// the parameter is later executed outside the func.
			if ( $this->isAllTaint( $curArgTaintedness ) ) {
				// $this->debug( __METHOD__, "cur arg $i is YES taint " .
				// "($curArgTaintedness). Marking dependent $funcName" );
				// Mark all dependent vars as tainted.
				$this->markAllDependentVarsYes( $func, $i, $curArgTaintedness );
			}

			// We are doing something like evilMethod( $arg );
			// where $arg is a parameter to the current function.
			// So backpropagate that assigning to $arg can cause evilness.
			if ( $this->isExecTaint( $taint[$i] ?? 0 ) ) {
				// $this->debug( __METHOD__, "cur param is EXEC. $funcName" );
				$phanObjs = $this->getPhanObjsForNode( $argument, [ 'return' ] );
				try {
					foreach ( $phanObjs as $phanObj ) {
						$this->markAllDependentMethodsExec(
							$phanObj,
							$taint[$i]
						);
					}
				} catch ( Exception $e ) {
					$this->debug( __METHOD__, "FIXME " . get_class( $e ) . " " . $e->getMessage() );
				}
			}
			$taintedArg = $argument->children['name'] ?? '[arg #' . ( $i + 1 ) . ']';
			$taintedArg = is_string( $taintedArg ) ? $taintedArg : '[arg #' . ( $i + 1 ) . ']';
			// We use curArgTaintedness here, as we aren't checking what taint
			// gets passed to return value, but which taint is EXECed.
			// $this->debug( __METHOD__, "Checking safe assing $funcName" .
				// " arg=$i paramTaint= " . ( $taint[$i] ?? "MISSING" ) .
				// " vs argTaint= $curArgTaintedness" );
			$containingMethod = $this->getCurrentMethod();
			$this->maybeEmitIssue(
				$taint[$i] ?? 0,
				$curArgTaintedness,
				"Calling method $funcName() in $containingMethod" .
				" that outputs using tainted argument \$$taintedArg." .
				$this->getOriginalTaintLine( $func, $i ) .
				$this->getOriginalTaintLine( $argument )
			);

			$overallArgTaint |= $effectiveArgTaintedness;
		}

		$containingMethod = $this->getCurrentMethod();
		$this->maybeEmitIssue(
			$taint['overall'],
			$this->execToYesTaint( $taint['overall'] ),
			"Calling method $funcName in $containingMethod that "
			. "is always unsafe " .
			$this->getOriginalTaintLine( $func )
		);

		$newMem = memory_get_peak_usage();
		$diffMem = round( ( $newMem - $oldMem ) / ( 1024 * 1024 ) );
		if ( $diffMem > 2 ) {
			$this->debug( __METHOD__, "Memory spike $diffMem $funcName" );
		}
		// The taint of the method call expression is the overall taint
		// of the method not counting the preserve flag plus any of the
		// taint from arguments of the right type.
		// With all the exec bits removed from args.
		$neitherPreserveOrExec = ~( SecurityCheckPlugin::PRESERVE_TAINT |
			SecurityCheckPlugin::ALL_EXEC_TAINT );
		return ( $taint['overall'] & $neitherPreserveOrExec )
			| ( $overallArgTaint & ~SecurityCheckPlugin::ALL_EXEC_TAINT );
	}

	/**
	 * Get current and effective taint of an argument when examining a func call
	 *
	 * @param array $funcTaint
	 * @param Node $argument
	 * @param int $i Position of the param
	 * @param FullyQualifiedFunctionLikeName $funcName
	 * @return int[] [ cur, effective ]
	 */
	private function getArgTaint(
		array $funcTaint,
		Node $argument,
		int $i,
		FullyQualifiedFunctionLikeName $funcName
	) : array {
		if (
			isset( $funcTaint[$i] )
			&& ( $funcTaint[$i] & SecurityCheckPlugin::ARRAY_OK )
			&& $this->nodeIsArray( $argument )
		) {
			// This function specifies that arrays are always ok
			// So treat as if untainted.
			return [ SecurityCheckPlugin::NO_TAINT, SecurityCheckPlugin::NO_TAINT ];
		}

		$curArgTaintedness = $this->getTaintednessNode( $argument );
		if ( isset( $funcTaint[$i] ) ) {
			if (
				( $funcTaint[$i] & SecurityCheckPlugin::SQL_NUMKEY_EXEC_TAINT )
				&& ( $curArgTaintedness & SecurityCheckPlugin::SQL_TAINT )
				&& $this->nodeIsString( $argument )
			) {
				// Special case to make NUMKEY work right for non-array
				// values. Should consider if this is really best
				// approach.
				$curArgTaintedness |= SecurityCheckPlugin::SQL_NUMKEY_TAINT;
			}
			$effectiveArgTaintedness = $curArgTaintedness &
				( $funcTaint[$i] | $this->execToYesTaint( $funcTaint[$i] ) );
			$this->debug( __METHOD__, "effective $effectiveArgTaintedness"
				. " via arg $i $funcName" );
		} elseif ( ( $funcTaint['overall'] &
			( SecurityCheckPlugin::PRESERVE_TAINT | SecurityCheckPlugin::UNKNOWN_TAINT )
		) ) {
			// No info for this specific parameter, but
			// the overall function either preserves taint
			// when unspecified or is unknown. So just
			// pass the taint through.
			// FIXME, could maybe check if type is safe like int.
			$effectiveArgTaintedness = $curArgTaintedness;
			// $this->debug( __METHOD__, "effective $effectiveArgTaintedness"
			// . " via preserve or unkown $funcName" );
		} else {
			// This parameter has no taint info.
			// And overall this function doesn't depend on param
			// for taint and isn't unknown.
			// So we consider this argument untainted.
			$effectiveArgTaintedness = SecurityCheckPlugin::NO_TAINT;
			// $this->debug( __METHOD__, "effective $effectiveArgTaintedness"
			// . " via no taint info $funcName" );
		}
		return [ $curArgTaintedness, $effectiveArgTaintedness ];
	}

	/**
	 * Handle pass-by-ref params when examining a function call. Phan handles passbyref by reanalyzing
	 * the method with PassByReferenceVariable objects instead of Parameters. These objects contain
	 * the info about the param, but proxy all calls to the underlying argument object. Our approach
	 * to passbyrefs takes advantage of that, and is described below.
	 *
	 * Whenever we find a PassByReferenceVariable, we first extract the argument from it.
	 * This means that we can set taintedness, links, caused-by, etc. all on the argument object,
	 * and without having to use dedicated code paths.
	 * However, methods are usually analyzed *before* the call, hence, if we modify the
	 * taintedness of the argument immediately, the effect of the method call will be reproduced
	 * twice. This would lead to weird bugs where a method escapes its (ref) parameter, and calling
	 * such a method with a non-tainted argument would result in a DoubleEscaped warning.
	 * To avoid that, we save taint data for passbyrefs inside another property (on the
	 * argument object), taintednessRef. Then, when the method call is found, the "ref" taintedness
	 * becomes actual, which is what this very method takes care of.
	 *
	 * @param FunctionInterface $func
	 * @param Parameter $param
	 * @param Node $argument
	 * @param int $i Position of the param
	 * @throws Exception
	 */
	private function handlePassByRef(
		FunctionInterface $func,
		Parameter $param,
		Node $argument,
		int $i
	) : void {
		if ( !$func->getInternalScope()->hasVariableWithName( $param->getName() ) ) {
			$this->debug( __METHOD__, "Missing variable in scope for arg $i \$" . $param->getName() );
			return;
		}
		$argObjs = $this->getPhanObjsForNode( $argument );
		if ( count( $argObjs ) !== 1 ) {
			$this->debug( __METHOD__, "Expected only one $param" );
		}
		foreach ( $argObjs as $argObj ) {
			$overrideTaint = true;
			if ( $argObj instanceof PassByReferenceVariable ) {
				// Watch out for nested references, and do not reset taint in that case, yet
				$argObj = $this->extractReferenceArgument( $argObj );
				$overrideTaint = false;
			}
			// Move the ref taintedness to the "actual" taintedness of the object
			$overrideTaint = $overrideTaint && !( $argObj instanceof Property );
			$this->setTaintedness( $argObj, $this->getTaintednessReference( $argObj ), $overrideTaint );
			if ( $overrideTaint ) {
				unset( $argObj->taintednessRef );
			}
		}
	}

	/**
	 * Given a Node, is it an array? (And definitely not a string)
	 *
	 * @param Mixed|Node $node A node object or simple value from AST tree
	 * @return bool Is it an array?
	 */
	protected function nodeIsArray( $node ) : bool {
		if ( !( $node instanceof Node ) ) {
			// simple literal
			return false;
		}
		if ( $node->kind === \ast\AST_ARRAY ) {
			// Exit early in the simple case.
			return true;
		}
		try {
			$type = UnionTypeVisitor::unionTypeFromNode(
				$this->code_base,
				$this->context,
				$node
			);
			if (
				$type->hasArrayLike() &&
				!$type->hasType( MixedType::instance( false ) ) &&
				!$type->hasType( StringType::instance( false ) )
			) {
				return true;
			}
		} catch ( Exception $e ) {
			$this->debug( __METHOD__, "Got error " . get_class( $e ) );
		}
		return false;
	}

	/**
	 * Given a Node, is it a string?
	 *
	 * @todo Unclear if this should return true for things that can
	 *   autocast to a string (e.g. ints)
	 * @param Mixed|Node $node A node object or simple value from AST tree
	 * @return bool Is it a string?
	 */
	protected function nodeIsString( $node ) : bool {
		if ( is_string( $node ) ) {
			return true;
		}
		if ( !( $node instanceof Node ) ) {
			// simple literal
			return false;
		}
		try {
			$type = UnionTypeVisitor::unionTypeFromNode(
				$this->code_base,
				$this->context,
				$node,
				// Don't check types, as this might be called e.g. on the LHS (see T249647)
				false
			);
			if ( $type->hasType( StringType::instance( false ) ) ) {
				// @todo Should having mixed type result in returning false here?
				return true;
			}
		} catch ( Exception $e ) {
			$this->debug( __METHOD__, "Got error " . get_class( $e ) );
		}
		return false;
	}

	/**
	 * Given a Node, is it definitely an int (and nothing else)
	 *
	 * Floats are not considered ints here.
	 *
	 * @param Mixed|Node $node A node object or simple value from AST tree
	 * @return bool Is it an int?
	 */
	protected function nodeIsInt( $node ) : bool {
		if ( is_int( $node ) ) {
			return true;
		}
		if ( !( $node instanceof Node ) ) {
			// simple literal that's not an int.
			return false;
		}
		try {
			$type = UnionTypeVisitor::unionTypeFromNode(
				$this->code_base,
				$this->context,
				$node,
				// Don't check types, as this might be called e.g. on the LHS (see T249647)
				false
			);
			if (
				$type->hasType( IntType::instance( false ) ) &&
				$type->typeCount() === 1
			) {
				return true;
			}
		} catch ( Exception $e ) {
			$this->debug( __METHOD__, "Got error " . get_class( $e ) );
		}
		return false;
	}

	/**
	 * Get the phan objects from the return line of a Func/Method
	 *
	 * This is primarily used to handle the case where a method
	 * returns a member (e.g. return $this->foo), and then something
	 * else does something evil with it - e.g. echo $someObj->getFoo().
	 * This allows keeping track that $this->foo is outputted, so if
	 * somewhere else in the code someone calls $someObj->setFoo( $unsafe )
	 * we can trigger a warning.
	 *
	 * This of course will only work in simple cases. It may also potentially
	 * have false positives if one instance is used solely for escaped stuff
	 * and a different instance is used for unsafe values that are later
	 * escaped, as all the different instaces are treated the same.
	 *
	 * It needs the return statement to be trivial (e.g. return $this->foo;). It
	 * will not work even with something as simple as $a = $this->foo; return $a;
	 * However, this code path will only happen if the plugin encounters the
	 * code to output the value prior to reading the code that sets the value to
	 * something evil. The other code path where the set happens first is much
	 * more robust and hopefully the more common code path.
	 *
	 * @param FunctionInterface $func The function/method. Must use Analyzable trait
	 * @return array An array of phan objects
	 */
	public function getReturnObjsOfFunc( FunctionInterface $func ) : array {
		if ( !property_exists( $func, 'retObjs' ) ) {
			// We still have to see the function. Analyze it now.
			$this->analyzeFunc( $func );
		}

		if ( !property_exists( $func, 'retObjs' ) ) {
			// If it still doesn't exist, perhaps we reached the recursion limit, or it might be
			// a kind of function that we can't handle.
			return [];
		}

		// Note that if a function is recursively calling itself, this list might be incomplete.
		// This could be remediated with another dynamic property (e.g. retObjsCollected), initialized
		// inside visitMethod in preorder, and set to true inside visitMethod in postorder.
		// It would be pointless, though, as returning a partial list is better than returning no list.
		return $func->retObjs;
	}

	/**
	 * Shorthand to check if $child is subclass of $parent.
	 *
	 * @param FullyQualifiedClassName $child
	 * @param FullyQualifiedClassName $parent
	 * @param CodeBase $codeBase
	 * @return bool
	 */
	public static function isSubclassOf(
		FullyQualifiedClassName $child,
		FullyQualifiedClassName $parent,
		CodeBase $codeBase
	) : bool {
		$childTypes = $child->asType()->asExpandedTypes( $codeBase )->getTypeSet();
		$parentType = $parent->asType();
		return in_array( $parentType, $childTypes, true );
	}
}
