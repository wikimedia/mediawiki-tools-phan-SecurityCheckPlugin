<?php

use Phan\AST\AnalysisVisitor;
use Phan\AST\ContextNode;
use Phan\AST\UnionTypeVisitor;
use Phan\CodeBase;
use Phan\Language\Context;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\Variable;
use Phan\Language\Element\TypedElementInterface;
use Phan\Language\Element\ClassElement;
use Phan\Language\UnionType;
use Phan\Language\Type\CallableType;
use Phan\Language\Type\MixedType;
use Phan\Language\Type\IntType;
use Phan\Language\Type\StringType;
use Phan\Language\FQSEN\FullyQualifiedFunctionLikeName;
use Phan\Language\FQSEN\FullyQualifiedFunctionName;
use Phan\Language\FQSEN\FullyQualifiedMethodName;
use Phan\Language\FQSEN;
use Phan\Plugin;
use ast\Node;
use Phan\Debug;
use Phan\Issue;
use Phan\Language\Scope\FunctionLikeScope;
use Phan\Language\Scope\BranchScope;
use Phan\Library\Set;
use Phan\Exception\IssueException;

/**
 * Base class for the Tainedness visitor subclass. Mostly contains
 * utility methods.
 */
abstract class TaintednessBaseVisitor extends AnalysisVisitor {

	/** @var SecurityCheckPlugin */
	protected $plugin;

	/** @var null|string|bool|resource filehandle to output debug messages */
	private $debugOutput = null;

	/** @var Context Override the file/line number to emit issues */
	protected $overrideContext = null;

	/**
	 * @param CodeBase $code_base
	 * @param Context $context
	 * @param SecurityCheckPlugin $plugin The instance of the plugin we're using
	 */
	public function __construct(
		CodeBase $code_base,
		Context $context,
		SecurityCheckPlugin $plugin
	) {
		parent::__construct( $code_base, $context );
		$this->plugin = $plugin;
	}

	/**
	 * Change taintedness of a function/method
	 *
	 * @todo FIXME, the taintedOriginalError property should be on
	 *  a per parameter granuality, instead of per function. As it
	 *  stands this means very misleading error messages can be
	 *  output. See the json integration test for an example.
	 * @param FunctionInterface $func
	 * @param int[] $taint Numeric keys for each arg and an 'overall' key.
	 * @param bool $override Whether to merge taint or override
	 * @suppress PhanUndeclaredMethod
	 */
	protected function setFuncTaint( FunctionInterface $func, array $taint, bool $override = false ) {
		if (
			$func instanceof ClassElement &&
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
		// What taint we're setting, to do book-keeping about whenever
		// we add a dangerous taint.
		$mergedTaint = SecurityCheckPlugin::NO_TAINT;
		if ( property_exists( $func, 'funcTaint' ) ) {
			$curTaint = $func->funcTaint;
		}
		if ( $override ) {
			$newTaint = $taint;
		}

		$bothTaint = $taint + $curTaint;
		foreach ( $bothTaint as $index => $_ ) {
			$t = $taint[$index] ?? 0;
			assert( is_int( $t ) );
			if ( !$override ) {
				$newTaint[$index] = ( $curTaint[$index] ?? 0 ) | $t;
			}
			$mergedTaint |= $t;
		}
		if ( !isset( $newTaint['overall'] ) ) {
			// FIXME, what's the right default??
			$this->debug( __METHOD__, "FIXME No overall taint specified $func" );
			$newTaint['overall'] = SecurityCheckPlugin::UNKNOWN_TAINT;
		}
		$this->checkFuncTaint( $newTaint );
		$func->funcTaint = $newTaint;

		if ( $mergedTaint & SecurityCheckPlugin::YES_EXEC_TAINT ) {
			if ( !property_exists( $func, 'taintedOriginalError' ) ) {
				$func->taintedOriginalError = '';
			}
			$newError = $this->dbgInfo() . ';';
			if ( strpos( $func->taintedOriginalError, $newError ) === false ) {
				// Only add to error if we haven't added this line before
				$func->taintedOriginalError .= $newError;
			}

			if ( strlen( $func->taintedOriginalError ) > 254 ) {
				$this->debug( __METHOD__, "Too long original error! for $func" );
				$func->taintedOriginalError = substr( $func->taintedOriginalError, 0, 250 ) . '...';
			}
		}
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
	protected function mergeTaintError( TypedElementInterface $left, TypedElementInterface $right ) {
		if ( !property_exists( $left, 'taintedOriginalError' ) ) {
			$left->taintedOriginalError = '';
		}
		$rightError = $right->taintedOriginalError ?? '';
		if ( strpos( $left->taintedOriginalError, $rightError ?: "\1\2" ) === false ) {
			$left->taintedOriginalError .= $rightError;
		}

		if ( strlen( $left->taintedOriginalError ) > 254 ) {
			$this->debug( __METHOD__, "Too long original error! for $left" );
			$left->taintedOriginalError = substr( $left->taintedOriginalError, 0, 250 ) . '...';
		}
	}

	/**
	 * Change the taintedness of a variable
	 *
	 * @param TypedElementInterface $variableObj The variable in question
	 * @param int $taintedness One of the class constants
	 * @param bool $override Override taintedness or just take max.
	 */
	protected function setTaintedness(
		TypedElementInterface $variableObj,
		int $taintedness,
		$override = true
	) {
		// $this->debug( __METHOD__, "begin for \$" . $variableObj->getName()
			// . " <- $taintedness (override=$override)" );

		assert( $taintedness >= 0, $taintedness );

		if ( $variableObj instanceof FunctionInterface ) {
			// FIXME what about closures?
			throw new Exception( "Must use setFuncTaint for functions" );
		}

		if ( property_exists( $variableObj, 'taintednessHasOuterScope' )
			|| !( $this->context->getScope() instanceof FunctionLikeScope )
		) {
// $this->debug( __METHOD__, "\$" . $variableObj->getName() . " has outer scope - "
// . get_class( $this->context->getScope()) . "" );
			// If the current context is not a FunctionLikeScope, then
			// it might be a class, or an if branch, or global. In any case
			// its probably a non-local variable (or in the if case, code
			// that may not be executed).

			if ( !property_exists( $variableObj, 'taintednessHasOuterScope' )
				&& ( $this->context->getScope() instanceof BranchScope )
			) {
// echo __METHOD__ . "in a branch\n";
				$scope = $this->context->getScope();
				do {
					// echo __METHOD__ . " getting parent scope\n";
					$scope = $scope->getParentScope();
				} while ( $scope instanceof BranchScope );
				if ( $scope->hasVariableWithName( $variableObj->getName() ) ) {
					$parentVarObj = $scope->getVariableByName( $variableObj->getName() );

					if ( !property_exists( $parentVarObj, 'taintedness' ) ) {
						// echo __METHOD__ . " parent scope for {$variableObj->getName()} has no taint\n";
						$parentVarObj->taintedness = $taintedness;
					} else {
						$parentVarObj->taintedness = $this->mergeAddTaint( $parentVarObj->taintedness, $taintedness );
					}
					$variableObj->taintedness =& $parentVarObj->taintedness;

					$methodLinks = $parentVarObj->taintedMethodLinks ?? new Set;
					$variableObjLinks = $variableObj->taintedMethodLinks ?? new Set;
					$variableObj->taintedMethodLinks = $methodLinks->union( $variableObjLinks );
					$parentVarObj->taintedMethodLinks =& $variableObj->taintedMethodLinks;
					$varError = $variableObj->taintedOriginalError ?? '';
					$combinedOrig = $parentVarObj->taintedOriginalError ?? '';
					if ( strpos( $combinedOrig, $varError ?: "\1\2" ) === false ) {
						$combinedOrig .= $varError;
					}

					if ( strlen( $combinedOrig ) > 254 ) {
						$this->debug( __METHOD__, "Too long original error! $variableObj" );
						$combinedOrig = substr( $combinedOrig, 0, 250 ) . '...';
					}
					$variableObj->taintedOriginalError = $combinedOrig;
					$parentVarObj->taintedOriginalError =& $variableObj->taintedOriginalError;

				} else {
					// $this->debug( __METHOD__, "var {$variableObj->getName()} does not exist outside branch!" );
				}
			}
			// This may not be executed, so it can only increase
			// taint level, not decrease.
			// Future todo: In cases of if...else where all cases covered,
			// should try to merge all branches ala ContextMergeVisitor.
			if ( property_exists( $variableObj, 'taintedness' ) ) {
				$variableObj->taintedness = $this->mergeAddTaint( $variableObj->taintedness, $taintedness );
			} else {
				$variableObj->taintedness = $taintedness;
			}
		} else {
// echo __METHOD__ . " \${$variableObj->getName()} is local variable\n";
			// This must be executed, so it can overwrite taintedness.
			$variableObj->taintedness = $override ?
				$taintedness :
				$this->mergeAddTaint(
					$variableObj->taintedness ?? 0, $taintedness
				);
		}

		if ( $this->isExecTaint( $taintedness ) || $this->isYesTaint( $taintedness ) ) {
			if ( !property_exists( $variableObj, 'taintedOriginalError' ) ) {
				$variableObj->taintedOriginalError = '';
			}
			$newErrors = [ $this->dbgInfo() . ';' ];
			if ( $this->overrideContext ) {
				$newErrors[] = $this->dbgInfo( $this->overrideContext ) . ';';
			}
			foreach ( $newErrors as $newError ) {
				if ( strpos( $variableObj->taintedOriginalError, $newError ) === false ) {
					$variableObj->taintedOriginalError .= $newError;
				}
			}

			if ( strlen( $variableObj->taintedOriginalError ) > 254 ) {
				$this->debug( __METHOD__, "Too long original error! $variableObj" );
				$variableObj->taintedOriginalError = substr(
					$variableObj->taintedOriginalError, 0, 250
				) . '...';
			}
		}
	}

	/**
	 * Merge two taint values together
	 *
	 * @param int $oldTaint One of the class constants
	 * @param int $newTaint One of the class constants
	 * @return int The merged taint value
	 */
	protected function mergeAddTaint( int $oldTaint, int $newTaint ) {
		// TODO: Should this clear UNKNOWN_TAINT if its present
		// only in one of the args?
		return $oldTaint | $newTaint;
	}

	/**
	 * This is also for methods and other function like things
	 *
	 * @param FunctionInterface $func What function/method to look up
	 * @return int[] Array with "overall" key, and numeric keys.
	 *   The overall key specifies what taint the function returns
	 *   irrespective of its arguments. The numeric keys are how
	 *   each individual argument affects taint.
	 *
	 *   For 'overall': the EXEC flags mean a call does evil regardless of args
	 *                  the TAINT flags are what taint the output has
	 *   For numeric keys: EXEC flags for what taints are unsafe here
	 *                     TAINT flags for what taint gets passed through func.
	 *   If func has an arg that is mssing from array, then it should be
	 *   treated as TAINT_NO if its a number or bool. TAINT_YES otherwise.
	 * @suppress PhanUndeclaredMethod
	 */
	protected function getTaintOfFunction( FunctionInterface $func ) {
		$funcName = $func->getFQSEN();
		$taint = null;
		$taint = $this->getBuiltinFuncTaint( $funcName );
		if ( $taint !== null ) {
			return $taint;
		}
		if ( $func->isInternal() ) {
			// Built in php.
			// Assume that anything really dangerous we've already
			// hardcoded. So just preserve taint
			return [ 'overall' => SecurityCheckPlugin::PRESERVE_TAINT ];
		}
		if ( property_exists( $func, 'funcTaint' ) ) {
			$taint = $func->funcTaint;
		} elseif (
			$func instanceof ClassElement
			&& $func->hasDefiningFQSEN()
			&& (string)$func->getDefiningFQSEN() !== (string)$func->getFQSEN()
		) {
			// @todo Should we check this earlier. Should we skip right to
			// the taint of the defining method, and not even look at
			// whatever the method is called as?
			// In the case of builtin taints, perhaps it should still look
			// at base classes even if the base class got overriden(?)
			$definingFqsen = $func->getDefiningFQSEN();
			$definingFunc = $this->code_base->getMethodByFQSEN( $definingFqsen );
			$this->debug( __METHOD__, "Checking base implemntation $definingFqsen of $funcName" );
			return $this->getTaintOfFunction( $definingFunc );
		} else {
			// Ensure we don't indef loop.
			if (
				!$func->isInternal() &&
				( !$this->context->isInFunctionLikeScope() ||
				$func->getFQSEN() !== $this->context->getFunctionLikeFQSEN() )
			) {
				// $this->debug( __METHOD__, "no taint info for func $func" );
				try {
					$func->analyze( $func->getContext(), $this->code_base );
				} catch ( Exception $e ) {
					$this->debug( __METHOD__, "Error" . $e->getMessage() . "\n" );
				}
				// $this->debug( __METHOD__, "updated taint info for $func" );
				// var_dump( $func->funcTaint ?? "NO INFO" );
				if ( property_exists( $func, 'funcTaint' ) ) {
					$this->checkFuncTaint( $func->funcTaint );
					return $func->funcTaint;
				}
			}
			// TODO: Maybe look at __toString() if we are at __construct().
			// FIXME this could probably use a second look.

			// If we haven't seen this function before, first of all
			// check the return type. If it (e.g.) returns just an int,
			// its probably safe.
			$taint = [ 'overall' => $this->getTaintByReturnType( $func->getUnionType() ) ];
			/*if ( $taint === SecurityCheckPlugin::UNKNOWN_TAINT ) {
			*	//Otherwise, if its unknown, assume that
			*	// the function depends only on its arguments (unclear how
			*	// good an assumption this is. Does it make more sense to
			*	// assume its safe until). Except we don't.
			*	$taint = SecurityCheckPlugin::PRESERVE_TAINT;
			}*/
			// echo "No taint for method $funcName - now $taint\n";
		}
		$this->checkFuncTaint( $taint );
		return $taint;
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
		foreach ( $types->getTypeSet() as $type ) {
			switch ( $type->getName() ) {
			case 'int':
			case 'float':
			case 'bool':
			case 'true':
			case 'null':
			case 'void':
				$taint = $this->mergeAddTaint( $taint, SecurityCheckPlugin::NO_TAINT );
				break;
			default:
				// This means specific class.
				// TODO - maybe look up __toString() method.
			case 'string':
			case 'closure':
			case 'callable':
			case 'array':
			case 'object':
			case 'resource':
			case 'mixed':
				// TODO If we have a specific class, maybe look at __toString()
				// $this->debug( __METHOD__, "Taint set unknown due to type '$type'." );
				$taint = $this->mergeAddTaint( $taint, SecurityCheckPlugin::UNKNOWN_TAINT );
				break;
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
	protected function getBuiltinFuncTaint( FullyQualifiedFunctionLikeName $fqsen ) {
		$taint = $this->plugin->getBuiltinFuncTaint( $fqsen );
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
	protected function getCurrentMethod() {
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
			} elseif ( $expr instanceof TypedElementInterface ) {
				// echo __METHOD__ . "FIXME, do we want this interface here?\n";
				return $this->getTaintednessPhanObj( $expr );
			}
			// fallthrough
		case "resource":
		case "unknown type":
		case "array":
		default:
			throw new Exception( "wtf - $type" );

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
		$r = ( new TaintednessVisitor( $this->code_base, $this->context, $this->plugin ) )(
			$node
		);
		assert( $r >= 0, $r );
		return $r;
	}

	/**
	 * Given a phan object (not method/function) find its taint
	 *
	 * @param TypedElementInterface $variableObj
	 * @return int The taint
	 */
	protected function getTaintednessPhanObj( TypedElementInterface $variableObj ) : int {
		$taintedness = SecurityCheckPlugin::UNKNOWN_TAINT;
		if ( $variableObj instanceof FunctionInterface ) {
			throw new Exception( "This method cannot be used with methods" );
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
	protected function getCtxN( Node $node ) {
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
	 * @throws Exception (Not sure what circumstances)
	 *
	 * TODO: Maybe this should be a visitor class instead(?)
	 *
	 * This method is a little confused, because sometimes we only
	 * want the objects that materially contribute to taint, and
	 * other times we want all the objects.
	 * e.g. Should foo( $bar ) return the $bar variable object?
	 *  What about the foo function object?
	 *
	 * @suppress PhanTypeMismatchForeach No idea why its confused
	 * @suppress PhanUndeclaredMethod it checks method_exists()
	 * @param Node $node AST node in question
	 * @param bool $all Whether or not to only include objects that directly correspond
	 *   In particular, whether or not to include Method objects for method calls.
	 * @return Array Array of various phan objects corresponding to $node
	 */
	protected function getPhanObjsForNode( Node $node, $all = false ) {
		$cn = $this->getCtxN( $node );

		switch ( $node->kind ) {
			case \ast\AST_PROP:
			case \ast\AST_STATIC_PROP:
				try {
					return [ $cn->getProperty( $node->children['prop'] ) ];
				} catch ( Exception $e ) {
					try {
						// There won't be an expr for static prop.
						if ( isset( $node->children['expr'] ) ) {
							$cnClass = $this->getCtxN( $node->children['expr'] );
							if ( $cnClass->getVariableName() === 'row' ) {
								// Its probably a db row, so ignore.
								// FIXME, we should handle the
								// db row situation much better.
								return [];
							}
						}
					} catch ( IssueException $e ) {
						$this->debug( __METHOD__,
							"Cannot determine property or var " .
							"name [1] (Maybe don't know what class) - "
							. $e->getIssueInstance()
						);
						return [];
					} catch ( Exception $e ) {
						$this->debug( __METHOD__,
							"Cannot determine property or var " .
							"name [2] (Maybe don't know what class) - "
							. get_class( $e ) . $e->getMessage() );
						return [];
					}
					$this->debug( __METHOD__, "Cannot determine " .
						"property [3] (Maybe don't know what class) - " .
						( method_exists( $e, 'getIssueInstance' )
						? $e->getIssueInstance()
						: get_class( $e ) . $e->getMessage() )
					);
					return [];
				}
			case \ast\AST_VAR:
				try {
					if ( Variable::isHardcodedGlobalVariableWithName( $cn->getVariableName() ) ) {
						return [];
					} else {
						return [ $cn->getVariable() ];
return [];
					}
				} catch ( IssueException $e ) {
					$this->debug( __METHOD__, "variable not in scope?? " . $e->getIssueInstance() );
					return [];
				} catch ( Exception $e ) {
					$this->debug( __METHOD__, "variable not in scope?? " . get_class( $e ) . $e->getMessage() );
					return [];
				}
			case \ast\AST_LIST:
			case \ast\AST_ENCAPS_LIST:
			case \ast\AST_ARRAY:
				$results = [];
				foreach ( $node->children as $child ) {
					if ( !is_object( $child ) ) {
						continue;
					}
					$results = array_merge( $this->getPhanObjsForNode( $child ), $results );
				}
				return $results;
			case \ast\AST_ARRAY_ELEM:
				$results = [];
				if ( is_object( $node->children['key'] ) ) {
					$results = array_merge(
						$this->getPhanObjsForNode( $node->children['key'] ),
						$results
					);
				}
				if ( is_object( $node->children['value'] ) ) {
					$results = array_merge(
						$this->getPhanObjsForNode( $node->children['value'] ),
						$results
					);
				}
				return $results;
			case \ast\AST_CAST:
				// Future todo might be to ignore casts to ints, since
				// such things should be safe. Unclear if that makes
				// sense in all circumstances.
				if ( is_object( $node->children['expr'] ) ) {
					return $this->getPhanObjsForNode( $node->children['expr'] );
				}
				return [];
			case \ast\AST_DIM:
				// For now just consider the outermost array.
				// FIXME. doesn't handle tainted array keys!
				return $this->getPhanObjsForNode( $node->children['expr'] );
			case \ast\AST_UNARY_OP:
				$var = $node->children['expr'];
				return $var instanceof Node ? $this->getPhanObjsForNode( $var ) : [];
			case \ast\AST_BINARY_OP:
				$left = $node->children['left'];
				$right = $node->children['right'];
				$leftObj = $left instanceof Node ? $this->getPhanObjsForNode( $left ) : [];
				$rightObj = $right instanceof Node ? $this->getPhanObjsForNode( $right ) : [];
				return array_merge( $leftObj, $rightObj );
			case \ast\AST_CONDITIONAL:
				$t = $node->children['true'];
				$f = $node->children['false'];
				$tObj = $t instanceof Node ? $this->getPhanObjsForNode( $t ) : [];
				$fObj = $f instanceof Node ? $this->getPhanObjsForNode( $f ) : [];
				return array_merge( $tObj, $fObj );
			case \ast\AST_CONST:
			case \ast\AST_CLASS_CONST:
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
				if ( !$all ) {
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
					$args = $node->children['args']->children;
					$pObjs = [ $func ];
					foreach ( $args as $arg ) {
						if ( !( $arg instanceof Node ) ) {
							continue;
						}
						$pObjs = array_merge(
							$pObjs,
							$this->getPhanObjsForNode( $arg )
						);
					}
					return $pObjs;
				} catch ( Exception $e ) {
					// Something non-simple
					// Future todo might be to still return
					// arguments in this case.
					return [];
				}
			default:
				// Debug::printNode( $node );
				// This should really be a visitor that recurses into
				// things.
				$this->debug( __METHOD__, "FIXME unhandled case"
					. \ast\get_kind_name( $node->kind ) . "\n"
				);
				return [];
		}
	}

	/**
	 * Get the current filename and line.
	 *
	 * @param Context $context Override the context to make debug info for
	 * @return string path/to/file +linenumber
	 */
	protected function dbgInfo( Context $context = null ) {
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
	 * @suppress PhanTypeMismatchProperty
	 * @param Variable $param The variable object for the parameter
	 * @param FunctionInterface $func The function/method in question
	 * @param int $i Which argument number is $param
	 */
	protected function linkParamAndFunc( Variable $param, FunctionInterface $func, int $i ) {
		if ( !( $param instanceof Variable ) ) {
			// Probably a PassByReferenceVariable.
			// TODO, handling of PassByReferenceVariable probably wrong here.
			$this->debug( __METHOD__, "Called on a non-variable \$"
				. $param->getName() . " of type " . get_class( $param )
				. ". May be handled wrong."
			);
		}
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
		} else {
			$param->taintedMethodLinks[$func] = [ $i => true ];
		}
	}

	/**
	 * Given a LHS and RHS make all the methods that can set RHS also for LHS
	 *
	 * Given 2 variables (e.g. $lhs = $rhs ), see to it that any function/method
	 * which we marked as being able to set the value of rhs, is also marked
	 * as being able to set the value of lhs.
	 *
	 * This also merges the information on what line caused the taint.
	 *
	 * @param TypedElementInterface $lhs Source of method list
	 * @param TypedElementInterface $rhs Destination of merged method list
	 */
	protected function mergeTaintDependencies(
		TypedElementInterface $lhs,
		TypedElementInterface $rhs
	) {
		$taintLHS = $this->getTaintedness( $lhs );
		$taintRHS = $this->getTaintedness( $rhs );
		/********************
		FIXME what was this check about. Does it make sense as an
		error condition??
		// LHS may already be tainted by something earlier.
		if (
			$taintLHS < SecurityCheckPlugin::PRESERVE_TAINT ||
			$taintRHS !== SecurityCheckPlugin::PRESERVE_TAINT
		) {
			$this->debug( __METHOD__, "FIXME merging dependencies where" .
				" LHS and RHS are not both preserved taint. lhs=$taintLHS; rhs=$taintRHS" );
		} */

		if ( $taintRHS & SecurityCheckPlugin::YES_EXEC_TAINT ) {
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

				$method->taintedVarLinks[$index]->attach( $lhs );
			}
			if ( isset( $lhs->taintedMethodLinks[$method] ) ) {
				$lhs->taintedMethodLinks[$method] += $paramInfo;
			}
			$lhs->taintedMethodLinks[$method] = $paramInfo;
		}
	}

	/**
	 * Mark any function setting a specific variable as EXEC taint
	 *
	 * If you do something like echo $this->foo;
	 * This method is called to make all things that set $this->foo
	 * as TAINT_EXEC.
	 *
	 * @todo delete all dependencies as no longer needed (or are they?)
	 * @param TypedElementInterface $var The variable in question
	 * @param int $taint What taint to mark them as.
	 */
	protected function markAllDependentMethodsExec(
		TypedElementInterface $var,
		int $taint = SecurityCheckPlugin::EXEC_TAINT
	) {
		// FIXME. Does this check make sense?
		// should it also check if it has any of the YES_TAINT flags?

		// echo __METHOD__ . $this->dbgInfo() . "Setting all methods dependent on $var as exec\n";
		if ( !property_exists( $var, 'taintedMethodLinks' ) ) {
			// $this->debug( __METHOD__, "no backlinks on $var" );
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
		$curVarTaint = $this->getTaintedness( $var );
		$newTaint = $this->mergeAddTaint( $curVarTaint, $taint );
		$this->setTaintedness( $var, $newTaint );

		$newMem = memory_get_peak_usage();
		$diffMem = round( ( $newMem - $oldMem ) / ( 1024 * 1024 ) );
		if ( $diffMem > 2 ) {
			$this->debug( __METHOD__, "Memory spike $diffMem for $var" );
		}
		// FIXME delete links
	}

	/**
	 * This happens when someone call foo( $evilTaintedVar );
	 *
	 * It makes sure that any variable that the function foo() sets takes on
	 * the taint of the supplied argument.
	 *
	 * @todo FIXME this needs to handle different types of taint.
	 *
	 * @param FunctionInterface $method The function or method in question
	 * @param int $i The number of the argument in question.
	 */
	protected function markAllDependentVarsYes( FunctionInterface $method, int $i ) {
		if ( $method->isInternal() ) {
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
		foreach ( $method->taintedVarLinks[$i] as $var ) {
			$curVarTaint = $this->getTaintedness( $var );
			$newTaint = $this->mergeAddTaint( $curVarTaint, SecurityCheckPlugin::YES_TAINT );
			$this->setTaintedness( $var, $newTaint );
		}
		// Maybe delete links??
		$newMem = memory_get_peak_usage();
		$diffMem = round( ( $newMem - $oldMem ) / ( 1024 * 1024 ) );
		if ( $diffMem > 2 ) {
			$this->debug( __METHOD__, "Memory spike $diffMem for $var" );
		}
	}

	/**
	 * Are any of the YES (i.e HTML_TAINT) taint flags set
	 *
	 * @param int $taint
	 * @return bool If the variable has known (non-execute taint)
	 */
	protected function isYesTaint( $taint ) {
		return ( $taint & SecurityCheckPlugin::YES_TAINT ) !== 0;
	}

	/**
	 * Does the taint have one of EXEC flags set
	 *
	 * @param int $taint
	 * @return bool If the variable has any exec taint
	 */
	protected function isExecTaint( $taint ) {
		return ( $taint & SecurityCheckPlugin::EXEC_TAINT ) !== 0;
	}

	/**
	 * Convert the yes taint bits to corresponding exec taint bits.
	 *
	 * Any UNKNOWN_TAINT or INAPLICABLE_TAINT is discarded.
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
	 * Special flags like UNKNOWN or INAPLICABLE are discarded.
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
	protected function isSafeAssignment( $lhs, $rhs ) {
		$adjustRHS = $this->yesToExecTaint( $rhs );
		// $this->debug( __METHOD__, "lhs=$lhs; rhs=$rhs, adjustRhs=$adjustRHS" );
		return ( $adjustRHS & $lhs ) === 0 &&
			!(
				( $lhs & SecurityCheckPlugin::EXEC_TAINT ) &&
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
	 */
	protected function isLikelyFalsePositive( int $taint ) : bool {
		return ( $taint & SecurityCheckPlugin::UNKNOWN_TAINT ) !== 0
			&& ( $taint & SecurityCheckPlugin::YES_TAINT ) === 0;
	}

	/**
	 * Get the line number of the original cause of taint.
	 *
	 * @param TypedElementInterface|Node $element
	 * @return string
	 */
	protected function getOriginalTaintLine( $element ) {
		$line = '';
		if ( !is_object( $element ) ) {
			return '';
		} elseif ( $element instanceof TypedElementInterface ) {
			if ( property_exists( $element, 'taintedOriginalError' ) ) {
				$line = $element->taintedOriginalError;
			}
		} elseif ( $element instanceof Node ) {
			$pobjs = $this->getPhanObjsForNode( $element );
			foreach ( $pobjs as $elem ) {
				if ( property_exists( $elem, 'taintedOriginalError' ) ) {
					$line .= $elem->taintedOriginalError;
				}
			}
			if ( $line === '' ) {
				// try to dig deeper.
				// This will also include method calls and whatnot.
				// FIXME should we always do this? Is it too spammy.
				$pobjs = $this->getPhanObjsForNode( $element, true );
				foreach ( $pobjs as $elem ) {
					if ( property_exists( $elem, 'taintedOriginalError' ) ) {
						$line .= $elem->taintedOriginalError;
					}
				}
			}
		} else {
			throw new Exception(
				$this->dbgInfo() . "invalid parameter "
				. get_class( $element )
			);
		}
		assert( strlen( $line ) < 8096, " taint error too long $line" );
		if ( $line ) {
			$line = substr( $line, 0, strlen( $line ) - 1 );
			return " (Caused by:$line)";
		} else {
			return '';
		}
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
	 * @return Array numeric keys for each parameter taint and 'overall' key
	 * @suppress PhanTypeMismatchForeach
	 */
	protected function matchTaintToParam(
		$node,
		int $taintedness,
		FunctionInterface $curFunc
	) : array {
		assert( $taintedness >= 0, $taintedness );
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
			$pobjTaintContribution = $this->getTaintedness( $pobj );
			// $this->debug( __METHOD__, "taint for $pobj is $pobjTaintContribution" );
			$links = $pobj->taintedMethodLinks ?? null;
			if ( !$links ) {
				// No method links.
				 $this->debug( __METHOD__, "no method links for " .$curFunc->getFQSEN() );
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
						if ( !isset( $paramTaint[$i] ) ) {
							$paramTaint[$i] = 0;
						}
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
	public function debug( $method, $msg ) {
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
		$line = $method . "\33[1m" . $this->dbgInfo() . " \33[0m" . $msg .     "\n";
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
	 * @return void
	 */
	protected function checkFuncTaint( array $taint ) {
		assert(
			isset( $taint['overall'] )
			&& is_int( $taint['overall'] )
			&& $taint >= 0,
			"Overall taint is wrong " . $this->dbgInfo() . ( $taint['overall'] ?? 'unset' )
		);

		foreach ( $taint as $i => $t ) {
			assert( is_int( $t ) && $t >= 0, "Taint index $i wrong $t" . $this->dbgInfo() );
		}
	}

	/**
	 * Given an AST node that's a callable, try and determine what it is
	 *
	 * This is intended for functions that register callbacks. It will
	 * only really work for callbacks that are basically literals.
	 *
	 * @note $node may not be the current node in $this->context.
	 * @suppress PhanTypeMismatchArgument
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
					$type instanceof CallableType &&
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
				case \ast\AST_CLASS_CONST:
					if (
						$classNode->children['const'] === 'class' &&
						$classNode->children['class']->kind === \ast\AST_NAME &&
						is_string( $classNode->children['class']->children['name'] )
					) {
						$className = $classNode->children['class']->children['name'];
					} else {
						return null;
					}
					break;
				case \ast\AST_VAR:
					$var = $this->getCtxN( $classNode )->getVariable();
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
				case \ast\AST_PROP:
					$var = $this->getCtxN( $classNode )
						->getProperty( $classNode->children['prop'] );
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
	public function maybeEmitIssue( int $lhsTaint, int $rhsTaint, string $msg ) {
		if ( $this->isSafeAssignment( $lhsTaint, $rhsTaint ) ) {
			return;
		}

		$adjustLHS = $this->execToYesTaint( $lhsTaint );
		$combinedTaint = $rhsTaint & $adjustLHS;
		$issueType = 'SecurityCheckMulti';
		$severity = Issue::SEVERITY_NORMAL;
		if (
			( $combinedTaint === 0 &&
			$rhsTaint & SecurityCheckPlugin::UNKNOWN_TAINT ) ||
			$this->plugin->isFalsePositive(
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

		} elseif (
			$combinedTaint === SecurityCheckPlugin::HTML_TAINT
		) {
			$issueType = 'SecurityCheck-XSS';
		} elseif (
			$combinedTaint === SecurityCheckPlugin::SQL_TAINT ||
			$combinedTaint === SecurityCheckPlugin::SQL_NUMKEY_TAINT ||
			$combinedTaint === (
				SecurityCheckPlugin::SQL_TAINT |
				SecurityCheckPlugin::SQL_NUMKEY_TAINT
			)
		) {
			$issueType = 'SecurityCheck-SQLInjection';
			$severity = Issue::SEVERITY_CRITICAL;

		} elseif (
			$combinedTaint === SecurityCheckPlugin::SHELL_TAINT
		) {
			$issueType = 'SecurityCheck-ShellInjection';
			$severity = Issue::SEVERITY_CRITICAL;
		} elseif (
			$combinedTaint === SecurityCheckPlugin::SERIALIZE_TAINT
		) {
			$issueType = 'SecurityCheck-PHPSerializeInjection';
			// For now this is low because it seems to have a lot
			// of false positives.
			$severity = Issue::SEVERITY_LOW;
		} elseif (
			$combinedTaint === SecurityCheckPlugin::CUSTOM1_TAINT
		) {
			$issueType = 'SecurityCheck-CUSTOM1';
		} elseif (
			$combinedTaint === SecurityCheckPlugin::CUSTOM2_TAINT
		) {
			$issueType = 'SecurityCheck-CUSTOM2';
		} elseif (
			$combinedTaint === SecurityCheckPlugin::MISC_TAINT
		) {
			$issueType = 'SecurityCheck-OTHER';
		} else {
			// Multiple taints?
			// Include the taint constants for debugging purposes.
			$msg .= " ($lhsTaint <- $rhsTaint)";
			if (
				$combinedTaint & (
					SecurityCheckPlugin::SHELL_TAINT |
					SecurityCheckPlugin::SQL_TAINT
				)
			) {
				$severity = Issue::SEVERITY_CRITICAL;
			}
		}

		$context = $this->context;
		if ( $this->overrideContext ) {
			// If we are overriding the file/line number,
			// report the original line number as well.
			$msg .= " (Originally at: $this->context)";
			$context = $this->overrideContext;
		}
		$this->plugin->emitIssue(
			$this->code_base,
			$context,
			$issueType,
			$msg,
			$severity
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
	 * @todo Maybe error handling should be elsewhere, so we could
	 *   get rid of the variant where the variables are nulls/strings
	 * @param null|FunctionInterface $func null if no function
	 * @param FQSEN|string $funcName FQSEN for method/function or string description
	 * @param array $taint Taint of function/method
	 * @param Array $args Arguments to function/method
	 * @return int Taint The resulting taint of the expression
	 */
	public function handleMethodCall( $func, $funcName, array $taint, array $args ) : int {
		$oldMem = memory_get_peak_usage();
		$this->checkFuncTaint( $taint );

		// We need to look at the taintedness of the arguments
		// we are passing to the method.
		$overallArgTaint = SecurityCheckPlugin::NO_TAINT;
		foreach ( $args as $i => $argument ) {
			if ( !is_object( $argument ) ) {
				// Literal value
				continue;
			}

			$curArgTaintedness = $this->getTaintednessNode( $argument );
			if (
				isset( $taint[$i] )
				&& ( $taint[$i] & SecurityCheckPlugin::ARRAY_OK )
				&& $this->nodeIsArray( $argument )
			) {
				// This function specifies that arrays are always ok
				// So treat as if untainted.
				$curArgTaintedness = SecurityCheckPlugin::NO_TAINT;
				$effectiveArgTaintedness = SecurityCheckPlugin::NO_TAINT;
			} elseif ( isset( $taint[$i] ) ) {
				if (
					( $taint[$i] & SecurityCheckPlugin::SQL_NUMKEY_EXEC_TAINT )
					&& !$this->nodeIsArray( $argument )
					&& ( $curArgTaintedness & SecurityCheckPlugin::SQL_TAINT )
				) {
					// Special case to make NUMKEY work right for non-array
					// values. Should consider if this is really best
					// approach.
					$curArgTaintedness |= SecurityCheckPlugin::SQL_NUMKEY_TAINT;
				}
				$effectiveArgTaintedness = $curArgTaintedness &
					( $taint[$i] | $this->execToYesTaint( $taint[$i] ) );
				// $this->debug( __METHOD__, "effective $effectiveArgTaintedness"
					// . " via arg $i $funcName" );
			} elseif ( ( $taint['overall'] &
				( SecurityCheckPlugin::PRESERVE_TAINT | SecurityCheckPlugin::UNKNOWN_TAINT )
			) ) {
				// No info for this specific parameter, but
				// the overall function either preserves taint
				// when unspecified or is unknown. So just
				// pass the taint through.
				// FIXME, could maybe check if type is safe like int.
				$effectiveArgTaintedness = $curArgTaintedness;
				# $this->debug( __METHOD__, "effective $effectiveArgTaintedness"
					# . " via preserve or unkown $funcName" );
			} else {
				// This parameter has no taint info.
				// And overall this function doesn't depend on param
				// for taint and isn't unknown.
				// So we consider this argument untainted.
				$effectiveArgTaintedness = SecurityCheckPlugin::NO_TAINT;
				# $this->debug( __METHOD__, "effective $effectiveArgTaintedness"
					# . " via no taint info $funcName" );
			}

			// -------Start complex reference parameter bit--------/
			// FIXME This is ugly and hacky and needs to be refactored.
			// If this is a call by reference parameter,
			// link the taintedness variables.
			$param = $func ? $func->getParameterForCaller( $i ) : null;
			// @todo Internal funcs that pass by reference. Should we
			// assume that their variables are tainted? Most common
			// example is probably preg_match, which may very well be
			// tainted much of the time.
			if ( $param && $param->isPassByReference() && !$func->isInternal() ) {
				if ( !$func->getInternalScope()->hasVariableWithName( $param->getName() ) ) {
					$this->debug( __METHOD__, "Missing variable in scope for arg $i \$" . $param->getName() );
					continue;
				}
				$methodVar = $func->getInternalScope()->getVariableByName( $param->getName() );
				// FIXME: Better to keep a list of dependencies
				// like what we do for methods?
				// Iffy if this will work, because phan replaces
				// the Parameter objects with ParameterPassByReference,
				// and then unreplaces them
				// echo __METHOD__ . $this->dbgInfo() . (string)$param. "\n";

				$pobjs = $this->getPhanObjsForNode( $argument );
				if ( count( $pobjs ) !== 1 ) {
					$this->debug( __METHOD__, "Expected only one $param" );
				}
				foreach ( $pobjs as $pobj ) {
					// FIXME, is unknown right here.
					$combinedTaint = $this->mergeAddTaint(
						$methodVar->taintedness ?? SecurityCheckPlugin::UNKNOWN_TAINT,
						$pobj->taintedness ?? SecurityCheckPlugin::UNKNOWN_TAINT
					);
					$pobj->taintedness = $combinedTaint;
					$methodVar->taintedness =& $pobj->taintedness;
					$methodLinks = $methodVar->taintedMethodLinks ?? new Set;
					$pobjLinks = $pobj->taintedMethodLinks ?? new Set;
					$pobj->taintedMethodLinks = $methodLinks->union( $pobjLinks );
					$methodVar->taintedMethodLinks =& $pobj->taintedMethodLinks;
					$combinedOrig = ( $pobj->taintedOriginalError ?? '' )
						. ( $methodVar->taintedOriginalError ?? '' );
					if ( strlen( $combinedOrig ) > 255 ) {
						$combinedOrig = substr( $combinedOrig, 0, 250 ) . '...';
					}
					$pobj->taintedOriginalError = $combinedOrig;
					$methodVar->taintedOriginalError =& $pobj->taintedOriginalError;
				}
			}
			// ------------END complex by reference parameter bit------

			// We are doing something like someFunc( $evilArg );
			// Propogate that any vars set by someFunc should now be
			// marked tainted.
			// FIXME: We also need to handle the case where
			// someFunc( $execArg ) for pass by reference where
			// the parameter is later executed outside the func.
			if ( $func && $this->isYesTaint( $curArgTaintedness ) ) {
				// $this->debug( __METHOD__, "cur arg $i is YES taint " .
				// "($curArgTaintedness). Marking dependent $funcName" );
				// Mark all dependent vars as tainted.
				$this->markAllDependentVarsYes( $func, $i );
			}

			// We are doing something like evilMethod( $arg );
			// where $arg is a parameter to the current function.
			// So backpropagate that assigning to $arg can cause evilness.
			if ( $this->isExecTaint( $taint[$i] ?? 0 ) ) {
				// $this->debug( __METHOD__, "cur param is EXEC. $funcName" );
				try {
					$phanObjs = $this->getPhanObjsForNode( $argument );
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
				( $func ? $this->getOriginalTaintLine( $func ) : '' ) .
				$this->getOriginalTaintLine( $argument )
			);

			$overallArgTaint |= $effectiveArgTaintedness;
		}

		$containingMethod = $this->getCurrentMethod();
		$this->maybeEmitIssue(
			$taint['overall'],
			$this->execToYesTaint( $taint['overall'] ),
			"Calling Method $funcName in $containingMethod that "
			. "is always unsafe " .
			( $func ? $this->getOriginalTaintLine( $func ) : '' )
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
			SecurityCheckPlugin::EXEC_TAINT );
		return ( $taint['overall'] & $neitherPreserveOrExec )
			| ( $overallArgTaint & ~SecurityCheckPlugin::EXEC_TAINT );
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
		try {
			$type = UnionTypeVisitor::unionTypeFromNode(
				$this->code_base,
				$this->context,
				$node
			);
			if (
				$type->hasArrayLike() &&
				!$type->hasType( MixedType::instance() ) &&
				!$type->hasType( StringType::instance() )
			) {
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
				$node
			);
			if (
				$type->hasType( IntType::instance() ) &&
				$type->typeCount() === 1
			) {
				return true;
			}
		} catch ( Exception $e ) {
			$this->debug( __METHOD__, "Got error " . get_class( $e ) );
		}
		return false;
	}

}
