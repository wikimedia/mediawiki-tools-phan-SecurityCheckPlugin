<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use ast\Node;
use Exception;
use Generator;
use Phan\AST\ASTReverter;
use Phan\AST\ContextNode;
use Phan\AST\UnionTypeVisitor;
use Phan\BlockAnalysisVisitor;
use Phan\CodeBase;
use Phan\Debug;
use Phan\Exception\CodeBaseException;
use Phan\Exception\FQSENException;
use Phan\Exception\IssueException;
use Phan\Exception\NodeException;
use Phan\Exception\UnanalyzableException;
use Phan\Issue;
use Phan\Language\Context;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\GlobalVariable;
use Phan\Language\Element\Method;
use Phan\Language\Element\PassByReferenceVariable;
use Phan\Language\Element\Property;
use Phan\Language\Element\TypedElementInterface;
use Phan\Language\Element\Variable;
use Phan\Language\FQSEN\FullyQualifiedClassName;
use Phan\Language\FQSEN\FullyQualifiedFunctionLikeName;
use Phan\Language\FQSEN\FullyQualifiedFunctionName;
use Phan\Language\FQSEN\FullyQualifiedMethodName;
use Phan\Language\Type\GenericArrayType;
use Phan\Language\Type\LiteralTypeInterface;
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
	use TaintednessAccessorsTrait;

	/** @var null|string|bool|resource filehandle to output debug messages */
	private $debugOutput;

	/** @var Context|null Override the file/line number to emit issues */
	protected $overrideContext;

	/**
	 * @var bool[] FQSENs of classes without __toString, map of [ (string)FQSEN => true ]
	 */
	protected static $fqsensWithoutToStringCache = [];

	/**
	 * Merge taintedness of a function/method
	 *
	 * @param FunctionInterface $func
	 * @param FunctionTaintedness $taint
	 */
	protected function addFuncTaint( FunctionInterface $func, FunctionTaintedness $taint ): void {
		$curTaint = self::getFuncTaint( $func );
		if ( $curTaint ) {
			$newTaint = $curTaint->asMergedWith( $taint );
		} else {
			$newTaint = $taint;
		}
		self::doSetFuncTaint( $func, $newTaint );
	}

	/**
	 * Ensure a function-like has its taintedness set and not unknown
	 *
	 * @param FunctionInterface $func
	 */
	protected function ensureFuncTaintIsSet( FunctionInterface $func ): void {
		if ( !self::getFuncTaint( $func ) ) {
			self::doSetFuncTaint( $func, new FunctionTaintedness( Taintedness::newSafe() ) );
		}
	}

	/**
	 * @param FunctionInterface $func
	 * @param Context|string|null $reason To override the caused-by line
	 * @param FunctionTaintedness $addedTaint
	 * @param FunctionTaintedness $allNewTaint
	 * @param MethodLinks|null $returnLinks NOTE: These are only used for preserved params, since for sink params
	 * we're already adding a Taintedness with the expected EXEC bits.
	 */
	private function maybeAddFuncError(
		FunctionInterface $func,
		$reason,
		FunctionTaintedness $addedTaint,
		FunctionTaintedness $allNewTaint,
		MethodLinks $returnLinks = null
	): void {
		if ( !is_string( $reason ) ) {
			$newErrors = [ $this->dbgInfo( $reason ?? $this->context ) ];
		} else {
			$newErrors = [ $reason ];
		}
		if ( $this->overrideContext && !( $this->isHook ?? false ) ) {
			// @phan-suppress-previous-line PhanUndeclaredProperty
			$newErrors[] = $this->dbgInfo( $this->overrideContext );
		}

		$hasReturnLinks = $returnLinks && !$returnLinks->isEmpty();

		// Future TODO: we might consider using PreservedTaintedness from the funcs instead of MethodLinks, but using
		// links is more consistent with what we do for non-function causedby lines.

		$newErr = self::getFuncCausedByRawCloneOrEmpty( $func );

		foreach ( $addedTaint->getSinkParamKeysNoVariadic() as $key ) {
			if ( $reason || $allNewTaint->canOverrideNonVariadicParam( $key ) ) {
				$curTaint = $addedTaint->getParamSinkTaint( $key );
				if ( $curTaint->has( SecurityCheckPlugin::ALL_EXEC_TAINT ) ) {
					$newErr->addParamSinkLines( $key, $newErrors, $curTaint->asExecToYesTaint() );
				}
			}
		}
		foreach ( $addedTaint->getPreserveParamKeysNoVariadic() as $key ) {
			if ( $hasReturnLinks && ( $reason || $allNewTaint->canOverrideNonVariadicParam( $key ) ) ) {
				$newErr->addParamPreservedLines(
					$key,
					$newErrors,
					Taintedness::newSafe(),
					$returnLinks->asFilteredForFuncAndParam( $func, $key )
				);
			}
		}
		$variadicIndex = $addedTaint->getVariadicParamIndex();
		if ( $variadicIndex !== null && ( $reason || $allNewTaint->canOverrideVariadicParam() ) ) {
			$sinkVariadic = $addedTaint->getVariadicParamSinkTaint();
			if ( $sinkVariadic && $sinkVariadic->has( SecurityCheckPlugin::ALL_EXEC_TAINT ) ) {
				$newErr->addVariadicParamSinkLines(
					$variadicIndex,
					$newErrors,
					$sinkVariadic->asExecToYesTaint()
				);
			}
			if ( $hasReturnLinks ) {
				$newErr->addVariadicParamPreservedLines(
					$variadicIndex,
					$newErrors,
					Taintedness::newSafe(),
					$returnLinks->asFilteredForFuncAndParam( $func, $variadicIndex )
				);
			}
		}

		$curTaint = $addedTaint->getOverall();
		if ( ( $reason || $allNewTaint->canOverrideOverall() ) && $curTaint->has( SecurityCheckPlugin::ALL_TAINT ) ) {
			// Note, the generic error shouldn't have any link
			$newErr->addGenericLines( $newErrors, $curTaint );
		}

		self::setFuncCausedByRaw( $func, $newErr );
	}

	/**
	 * Add the given caused-by lines to $element.
	 *
	 * @param TypedElementInterface $element
	 * @param CausedByLines $rightError
	 */
	protected function mergeTaintError( TypedElementInterface $element, CausedByLines $rightError ): void {
		assert( !$element instanceof FunctionInterface, 'Should use mergeFuncTaintError' );

		$curError = self::getCausedByRaw( $element );

		if ( !$curError ) {
			$newLeftError = $rightError;
		} else {
			$newLeftError = $curError->asMergedWith( $rightError );
		}

		self::setCausedByRaw( $element, $newLeftError );
	}

	/**
	 * @param FunctionInterface $func
	 * @param FunctionCausedByLines $newError
	 * @param FunctionTaintedness $allFuncTaint Used to check NO_OVERRIDE
	 */
	protected function mergeFuncError(
		FunctionInterface $func,
		FunctionCausedByLines $newError,
		FunctionTaintedness $allFuncTaint
	): void {
		$funcError = self::getFuncCausedByRawCloneOrEmpty( $func );
		$funcError->mergeWith( $newError, $allFuncTaint );
		self::setFuncCausedByRaw( $func, $funcError );
	}

	/**
	 * Add the current context to taintedOriginalError book-keeping
	 *
	 * This allows us to show users what line caused an issue.
	 *
	 * @param TypedElementInterface $elem Where to put it
	 * @param Taintedness $taintedness
	 * @param MethodLinks|null $links
	 * @param string|null $reason To override the caused by line
	 */
	protected function addTaintError(
		TypedElementInterface $elem,
		Taintedness $taintedness,
		?MethodLinks $links,
		string $reason = null
	): void {
		assert( !$elem instanceof FunctionInterface, 'Should use addFuncTaintError' );

		if ( !$taintedness->has( SecurityCheckPlugin::ALL_TAINT ) && ( !$links || $links->isEmpty() ) ) {
			// Don't add book-keeping if no actual taint was added.
			return;
		}

		$newErrors = $reason !== null ? [ $reason ] : [ $this->dbgInfo() ];
		if ( $this->overrideContext && !( $this->isHook ?? false ) ) {
			// @phan-suppress-previous-line PhanUndeclaredProperty
			$newErrors[] = $this->dbgInfo( $this->overrideContext );
		}

		$newErr = self::getCausedByRawCloneOrEmpty( $elem );
		$newErr->addLines( $newErrors, $taintedness, $links );
		self::setCausedByRaw( $elem, $newErr );
	}

	/**
	 * Ensures that the given variable obj has some taintedness set, initializing to safe if it doesn't.
	 *
	 * @param TypedElementInterface $varObj
	 */
	protected function ensureTaintednessIsSet( TypedElementInterface $varObj ): void {
		if ( !self::getTaintednessRaw( $varObj ) ) {
			self::setTaintednessRaw( $varObj, Taintedness::newSafe() );
		}
		if ( $varObj instanceof GlobalVariable ) {
			$gVarObj = $varObj->getElement();
			if ( !self::getTaintednessRaw( $gVarObj ) ) {
				self::setTaintednessRaw( $gVarObj, Taintedness::newSafe() );
			}
		}
	}

	/**
	 * Change the taintedness of $variableObj.
	 *
	 * @param TypedElementInterface $variableObj
	 * @param Taintedness $taintedness
	 * @param bool $override
	 */
	private function setTaintedness(
		TypedElementInterface $variableObj,
		Taintedness $taintedness,
		bool $override
	): void {
		assert( !$variableObj instanceof FunctionInterface, 'Must use setFuncTaint for functions' );

		if (
			$variableObj instanceof Property &&
			$variableObj->getClassFQSEN() === FullyQualifiedClassName::getStdClassFQSEN()
		) {
			// Phan conflates all stdClass props, see https://github.com/phan/phan/issues/3869
			// Avoid doing the same with taintedness, as that would cause weird issues (see
			// 'stdclassconflation' test).
			// TODO Is it possible to store prop taintedness in the Variable object?
			// that would be similar to a fine-grained handling of arrays.
			return;
		}

		// NOTE: Do NOT merge in place here, as that would change the taintedness for all variable
		// objects of which $variableObj is a clone!
		$curTaint = self::getTaintednessRaw( $variableObj );

		if ( $override || !$curTaint ) {
			$newTaint = $taintedness;
		} else {
			$newTaint = $curTaint->asMergedWith( $taintedness );
		}
		self::setTaintednessRaw( $variableObj, $newTaint );
	}

	/**
	 * Given a func, if it has a defining func different from itself, return that defining func. Returns null otherwise.
	 *
	 * @param FunctionInterface $func
	 * @return FunctionInterface|null
	 */
	private function getDefiningFuncIfDifferent( FunctionInterface $func ): ?FunctionInterface {
		if ( $func instanceof Method && $func->hasDefiningFQSEN() ) {
			$definingFQSEN = $func->getDefiningFQSEN();
			if ( $definingFQSEN !== $func->getFQSEN() ) {
				return $this->code_base->getMethodByFQSEN( $definingFQSEN );
			}
		}
		return null;
	}

	/**
	 * Get a list of places to look for function taint info
	 *
	 * @todo How to handle multiple function definitions (phan "alternates")
	 * @param FunctionInterface $func
	 * @return Generator<FunctionInterface>
	 */
	private function getPossibleFuncDefinitions( FunctionInterface $func ): Generator {
		yield $func;

		// If we don't have a defining func, stay with the same func.
		// definingFunc is used later on during fallback processing.
		$definingFunc = $this->getDefiningFuncIfDifferent( $func );
		if ( $definingFunc ) {
			yield $definingFunc;
		}
		if ( $func instanceof Method ) {
			try {
				$class = $func->getClass( $this->code_base );
			} catch ( CodeBaseException $e ) {
				$this->debug( __METHOD__, "Class not found for func $func: " . $this->getDebugInfo( $e ) );
				return;
			}
			$nonParents = $class->getNonParentAncestorFQSENList();

			foreach ( $nonParents as $nonParentFQSEN ) {
				if ( $this->code_base->hasClassWithFQSEN( $nonParentFQSEN ) ) {
					$nonParent = $this->code_base->getClassByFQSEN( $nonParentFQSEN );
					// TODO Assuming this is a direct invocation, but it doesn't always make sense
					$directInvocation = true;
					if ( $nonParent->hasMethodWithName( $this->code_base, $func->getName(), $directInvocation ) ) {
						yield $nonParent->getMethodByName( $this->code_base, $func->getName() );
					}
				}
			}
		}
	}

	/**
	 * This is also for methods and other function like things
	 * @note This is not guaranteed to return a clone
	 *
	 * @param FunctionInterface $func What function/method to look up
	 * @return FunctionTaintedness Always a clone
	 */
	protected function getTaintOfFunction( FunctionInterface $func ): FunctionTaintedness {
		$funcTaint = self::getFuncTaint( $func );
		if ( $funcTaint !== null ) {
			return $funcTaint;
		}

		$annotatedTaint = $this->getSetKnownTaintOfFunctionWithoutAnalysis( $func );
		if ( $annotatedTaint ) {
			return $annotatedTaint;
		}

		$isPHPInternalFunc = $func->isPHPInternal();
		if ( !$isPHPInternalFunc ) {
			// PHP internal functions cannot be analyzed because they don't have a body.
			$funcToAnalyze = $this->getDefiningFuncIfDifferent( $func ) ?: $func;
			$this->analyzeFunc( $funcToAnalyze );
			$analyzedFuncTaint = self::getFuncTaint( $funcToAnalyze );
			if ( $analyzedFuncTaint !== null ) {
				return $analyzedFuncTaint;
			}
		}

		$taintFromReturnType = $this->getTaintByType( $func->getUnionType() );
		if ( !$isPHPInternalFunc ) {
			// If we haven't seen this function before, first of all check the return type. If it
			// returns a safe type (like int), it's safe.
			$taint = new FunctionTaintedness( $taintFromReturnType );
			self::doSetFuncTaint( $func, $taint );
			$this->maybeAddFuncError( $func, null, $taint, $taint );
		} else {
			// Assume that anything really dangerous we've already hardcoded. So just preserve taint.
			$overall = $taintFromReturnType->isSafe()
				? $taintFromReturnType
				: new Taintedness( SecurityCheckPlugin::PRESERVE_TAINT );
			$taint = new FunctionTaintedness( $overall );
			// We're not adding any error here, since it's presumably unnecessary for PHP internal stuff.
			self::doSetFuncTaint( $func, $taint );
		}
		return $taint;
	}

	/**
	 * Given a function, find out if it has any hardcoded/annotated taint, or whether it should inherit its taint
	 * from an alternate definition. If anything was found, set that taintedness in the func object and return it.
	 * In particular, this does NOT cause $func to be analyzed.
	 *
	 * @param FunctionInterface $func
	 * @return FunctionTaintedness|null
	 */
	private function getSetKnownTaintOfFunctionWithoutAnalysis( FunctionInterface $func ): ?FunctionTaintedness {
		$funcsToTry = $this->getPossibleFuncDefinitions( $func );
		foreach ( $funcsToTry as $trialFunc ) {
			/** @var FunctionInterface $trialFunc */
			if ( !$trialFunc->isPHPInternal() ) {
				// PHP internal functions can't have a docblock.
				$taintData = $this->getDocBlockTaintOfFunc( $trialFunc );
				if ( $taintData !== null ) {
					[ $taint, $methodLinks ] = $taintData;
					self::doSetFuncTaint( $func, $taint );
					$this->maybeAddFuncError( $func, $trialFunc->getContext(), $taint, $taint, $methodLinks );
					return $taint;
				}
			}

			$trialFuncName = $trialFunc->getFQSEN();
			$taint = SecurityCheckPlugin::$pluginInstance->getBuiltinFuncTaint( $trialFuncName );
			if ( $taint !== null ) {
				$taint = clone $taint;
				self::doSetFuncTaint( $func, $taint );
				if ( !$func->isPHPInternal() ) {
					// Caused-by lines are presumably unnecessary for PHP internal stuff.
					$this->maybeAddFuncError( $func, "Builtin-$trialFuncName", $taint, $taint );
				}
				return $taint;
			}
		}

		$definingFunc = $this->getDefiningFuncIfDifferent( $func );
		if ( $definingFunc ) {
			$definingFuncTaint = self::getFuncTaint( $definingFunc );
			if ( $definingFuncTaint !== null ) {
				return $definingFuncTaint;
			}
		}

		return null;
	}

	/**
	 * Analyze a function. This is very similar to Analyzable::analyze, but avoids several checks
	 * used by phan for performance. Phan doesn't know about taintedness, so it may decide to skip
	 * a re-analysis which we need.
	 * @todo This is a bit hacky.
	 * @todo We should implement our own perf checks, e.g. if the method as already called with
	 * the same taintedness, taint links, etc. for all params.
	 * @see \Phan\Analysis\Analyzable::analyze()
	 *
	 * @param FunctionInterface $func
	 */
	public function analyzeFunc( FunctionInterface $func ): void {
		$node = $func->getNode();
		if ( !$node ) {
			return;
		}

		if ( $this->context->isInFunctionLikeScope() && $func->getFQSEN() === $this->context->getFunctionLikeFQSEN() ) {
			// Avoid pointless recursion
			return;
		}

		static $depth = 0;
		// @todo Tune the max depth. Raw benchmarking shows very little difference between e.g.
		// 5 and 10. However, while with higher values we can detect more issues and avoid more
		// false positives, it becomes harder to tell where an issue is coming from.
		// Thus, this value should be increased only when we'll have better error reporting.
		if ( $depth > 5 ) {
			// $this->debug( __METHOD__, 'WARNING: aborting analysis earlier due to max depth' );
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
	 * Obtain taint information from a docblock comment.
	 *
	 * @param FunctionInterface $func The function to check
	 * @return array<FunctionTaintedness|MethodLinks>|null null for no info
	 * @phan-return array{0:FunctionTaintedness,1:MethodLinks}|null
	 */
	protected function getDocBlockTaintOfFunc( FunctionInterface $func ): ?array {
		// Note that we're not using the hashed docblock for caching, because the same docblock
		// may have different meanings in different contexts. E.g. @return self
		$fqsen = (string)$func->getFQSEN();
		if ( isset( SecurityCheckPlugin::$docblockCache[ $fqsen ] ) ) {
			[ $taint, $links ] = SecurityCheckPlugin::$docblockCache[ $fqsen ];
			return [ clone $taint, clone $links ];
		}

		$docBlock = $func->getDocComment();
		if ( $docBlock === null ) {
			return null;
		}
		if ( strpos( $docBlock, '-taint' ) === false ) {
			// Lightweight check for methods that certainly aren't annotated
			return null;
		}
		$lines = explode( "\n", $docBlock );
		/** @param string[] $args */
		$invalidLineIssueEmitter = function ( string $msg, array $args ) use ( $func ): void {
			SecurityCheckPlugin::emitIssue(
				$this->code_base,
				// Emit issues at the line of the signature
				$func->getContext(),
				'SecurityCheckInvalidAnnotation',
				$msg,
				$args
			);
		};
		// Note, not forCaller, as that doesn't see variadic parameters
		$calleeParamList = $func->getParameterList();
		$validTaintEncountered = false;
		// Assume that if some of the taint is specified, then
		// the person would specify all the dangerous taints, so
		// don't set the unknown flag if not taint annotation on
		// @return.
		$funcTaint = new FunctionTaintedness( Taintedness::newSafe() );
		// TODO $fakeMethodLinks here is a bit hacky...
		$fakeMethodLinks = new MethodLinks();
		foreach ( $lines as $line ) {
			$m = [];
			$trimmedLine = ltrim( rtrim( $line ), "* \t/" );
			if ( strpos( $trimmedLine, '@param-taint' ) === 0 ) {
				$matched = preg_match( SecurityCheckPlugin::PARAM_ANNOTATION_REGEX, $trimmedLine, $m );
				if ( !$matched ) {
					$invalidLineIssueEmitter( "Cannot parse taint line '{COMMENT}'", [ $trimmedLine ] );
					continue;
				}

				$paramNumber = null;
				$isVariadic = null;
				foreach ( $calleeParamList as $i => $param ) {
					if ( $m['paramname'] === $param->getName() ) {
						$paramNumber = $i;
						$isVariadic = $param->isVariadic();
						break;
					}
				}
				if ( $paramNumber === null ) {
					$invalidLineIssueEmitter(
						'Annotated parameter ${PARAMETER} not found in the signature',
						[ $m['paramname'] ]
					);
					continue;
				}

				$annotatedAsVariadic = $m['variadic'] !== '';
				if ( $isVariadic !== $annotatedAsVariadic ) {
					$msg = $isVariadic
						? 'Variadic parameter ${PARAMETER} should be annotated as `...${PARAMETER}`'
						: 'Non-variadic parameter ${PARAMETER} should be annotated as `${PARAMETER}`';
					$invalidLineIssueEmitter( $msg, [ $m['paramname'], $m['paramname'] ] );
				}
				$taintData = SecurityCheckPlugin::parseTaintLine( $m['taint'] );
				if ( $taintData === null ) {
					$invalidLineIssueEmitter( "Invalid param taintedness '{COMMENT}'", [ $m['taint'] ] );
					continue;
				}
				/** @var Taintedness $taint */
				[ $taint, $flags ] = $taintData;
				$sinkTaint = $taint->withOnly( SecurityCheckPlugin::ALL_EXEC_TAINT );
				$preserveTaint = $taint->without( SecurityCheckPlugin::ALL_EXEC_TAINT )->asPreservedTaintedness();
				if ( $isVariadic ) {
					$funcTaint->setVariadicParamSinkTaint( $paramNumber, $sinkTaint );
					$funcTaint->setVariadicParamPreservedTaint( $paramNumber, $preserveTaint );
					$funcTaint->addVariadicParamFlags( $flags );
				} else {
					$funcTaint->setParamSinkTaint( $paramNumber, $sinkTaint );
					$funcTaint->setParamPreservedTaint( $paramNumber, $preserveTaint );
					$funcTaint->addParamFlags( $paramNumber, $flags );
				}
				$fakeMethodLinks->initializeParamForFunc( $func, $paramNumber );
				$validTaintEncountered = true;
				if ( ( $taint->get() & SecurityCheckPlugin::ESCAPES_HTML ) === SecurityCheckPlugin::ESCAPES_HTML ) {
					// Special case to auto-set anything that escapes html to detect double escaping.
					$funcTaint->setOverall( $funcTaint->getOverall()->with( SecurityCheckPlugin::ESCAPED_TAINT ) );
				}
			} elseif ( strpos( $trimmedLine, '@return-taint' ) === 0 ) {
				$taintLine = substr( $trimmedLine, strlen( '@return-taint' ) + 1 );
				$taintData = SecurityCheckPlugin::parseTaintLine( $taintLine );
				if ( $taintData === null ) {
					$invalidLineIssueEmitter( "Invalid return taintedness '{COMMENT}'", [ $taintLine ] );
					continue;
				}
				/** @var Taintedness $taint */
				[ $taint, $flags ] = $taintData;
				if ( $taint->has( SecurityCheckPlugin::ALL_EXEC_TAINT ) ) {
					$invalidLineIssueEmitter( "Return taintedness cannot be exec", [] );
					continue;
				}
				$funcTaint->setOverall( $taint );
				$funcTaint->addOverallFlags( $flags );
				$validTaintEncountered = true;
			}
		}

		if ( !$validTaintEncountered ) {
			$this->debug( __METHOD__, 'Possibly wrong taint annotation in docblock: ' . json_encode( $docBlock ) );
		}

		SecurityCheckPlugin::$docblockCache[ $fqsen ] = $validTaintEncountered
			? [ clone $funcTaint, clone $fakeMethodLinks ]
			: null;
		return SecurityCheckPlugin::$docblockCache[ $fqsen ];
	}

	/**
	 * Given a type, determine what type of taint
	 *
	 * e.g. Integers are probably untainted since its hard to do evil
	 * with them, but mark strings as unknown since we don't know.
	 *
	 * Only use as a fallback
	 * @param UnionType $types The types
	 * @return Taintedness
	 */
	protected function getTaintByType( UnionType $types ): Taintedness {
		// NOTE: This flattens intersection types
		$typelist = $types->getUniqueFlattenedTypeSet();
		if ( !$typelist ) {
			// $this->debug( __METHOD__, "Setting type unknown due to no type info." );
			return new Taintedness( SecurityCheckPlugin::UNKNOWN_TAINT );
		}

		$taint = new Taintedness( SecurityCheckPlugin::NO_TAINT );
		foreach ( $typelist as $type ) {
			if ( $type instanceof LiteralTypeInterface ) {
				// We're going to assume that literals aren't tainted...
				continue;
			}
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
				case 'non-null-mixed':
					// $this->debug( __METHOD__, "Taint set unknown due to type '$type'." );
					$taint->add( SecurityCheckPlugin::UNKNOWN_TAINT );
					break;
				default:
					if ( $type->hasTemplateTypeRecursive() ) {
						// TODO Can we do better for template types?
						$taint->add( SecurityCheckPlugin::UNKNOWN_TAINT );
						break;
					}

					if ( !$type->isObjectWithKnownFQSEN() ) {
						// Likely some phan-specific types not included above
						$this->debug( __METHOD__, " $type (" . get_class( $type ) . ') not a class?' );
						$taint->add( SecurityCheckPlugin::UNKNOWN_TAINT );
						break;
					}

					$fqsenStr = $type->asFQSEN()->__toString();
					if ( isset( self::$fqsensWithoutToStringCache[$fqsenStr] ) ) {
						$taint->add( SecurityCheckPlugin::UNKNOWN_TAINT );
						break;
					}

					// This means specific class, so look up __toString()
					$toStringFQSEN = FullyQualifiedMethodName::fromStringInContext(
						$fqsenStr . '::__toString',
						$this->context
					);
					if ( !$this->code_base->hasMethodWithFQSEN( $toStringFQSEN ) ) {
						// This is common in a void context.
						// e.g. code like $this->foo() will reach this
						// check.
						self::$fqsensWithoutToStringCache[$fqsenStr] = true;
						$taint->add( SecurityCheckPlugin::UNKNOWN_TAINT );
						break;
					}
					$toString = $this->code_base->getMethodByFQSEN( $toStringFQSEN );
					$toStringTaint = $this->getTaintOfFunction( $toString );
					$taint->mergeWith( $toStringTaint->getOverall()->without(
						SecurityCheckPlugin::PRESERVE_TAINT | SecurityCheckPlugin::ALL_EXEC_TAINT
					) );
			}
		}
		return $taint;
	}

	/**
	 * @param Node $node
	 * @param string $caller
	 * @return iterable<mixed,FunctionInterface>
	 */
	protected function getFuncsFromNode( Node $node, $caller = __METHOD__ ): iterable {
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
	 * Get what taint types are allowed on a typed element (i.e. use its type to rule out
	 * impossible taint types).
	 *
	 * @param TypedElementInterface $var
	 * @return Taintedness|null Null means all taints, checking for null is faster than ORing
	 */
	protected function getTaintMaskForTypedElement( TypedElementInterface $var ): ?Taintedness {
		if ( $var instanceof GlobalVariable ) {
			// TODO We wouldn't need to do this if phan didn't infer real types for global variables.
			// See https://github.com/phan/phan/issues/4518
			$var = $var->getElement();
		}
		// Note, we must use the real union type because:
		// 1 - The non-real type might be wrong
		// 2 - The non-real type might be incomplete (e.g. when analysing a func without docblock
		// we still don't know all the possible types of the params).
		return $this->getTaintMaskForType( $var->getUnionType()->getRealUnionType() );
	}

	/**
	 * Get what taint types are allowed on an element with the given type.
	 *
	 * @param UnionType $type
	 * @return Taintedness|null Null for all flags
	 */
	protected function getTaintMaskForType( UnionType $type ): ?Taintedness {
		$typeTaint = $this->getTaintByType( $type );

		if ( $typeTaint->has( SecurityCheckPlugin::UNKNOWN_TAINT ) ) {
			return null;
		}
		return $typeTaint;
	}

	/**
	 * Get what taint the element could have in the future. For instance, a func parameter may initially
	 * have no taint, but it may become tainted depending on the argument.
	 * @todo Ensure this won't miss any case (aside from when phan infers a wrong real type)
	 *
	 * @param TypedElementInterface $el
	 * @return Taintedness|null Null for all taints
	 */
	protected function getPossibleFutureTaintOfElement( TypedElementInterface $el ): ?Taintedness {
		return $this->getTaintMaskForTypedElement( $el );
	}

	/**
	 * Get name of current method (for debugging purposes)
	 *
	 * @return string Name of method or "[no method]"
	 */
	protected function getCurrentMethod(): string {
		return $this->context->isInFunctionLikeScope() ?
			(string)$this->context->getFunctionLikeFQSEN() : '[no method]';
	}

	/**
	 * Get the taintedness of something from the AST tree.
	 *
	 * @param mixed $expr An expression from the AST tree.
	 * @return TaintednessWithError
	 */
	protected function getTaintedness( $expr ): TaintednessWithError {
		if ( $expr instanceof Node ) {
			return $this->getTaintednessNode( $expr );
		}

		assert( is_scalar( $expr ) || $expr === null );
		// Optim: avoid using TaintednessWithError::newEmpty()
		return new TaintednessWithError(
			new Taintedness( SecurityCheckPlugin::NO_TAINT ),
			new CausedByLines(),
			new MethodLinks()
		);
	}

	/**
	 * Give an AST node, find its taint. This always returns a copy.
	 *
	 * @param Node $node
	 * @return TaintednessWithError
	 * @suppress PhanUndeclaredProperty
	 */
	protected function getTaintednessNode( Node $node ): TaintednessWithError {
		// Performance: use isset(), not property_exists()
		if ( isset( $node->taint ) ) {
			// Return cached result. Cache hit ratio should ideally be 100%, because we should never have to retrieve
			// the taintedness of a node without having analyzed it first. For now the ratio is lower because
			// we don't cache the result of cheap nodes.
			return $node->taint;
		}
		// TODO This might just a return a default if no cached data.

		// Debug::printNode( $node );
		// Make sure to update the line number, or the same issue may be reported
		// more than once on different lines (see test 'multilineissue').
		$oldLine = $this->context->getLineNumberStart();
		$this->context->setLineNumberStart( $node->lineno );

		$visitor = new TaintednessVisitor( $this->code_base, $this->context );
		try {
			return $visitor->analyzeNodeAndGetTaintedness( $node );
		} finally {
			$this->context->setLineNumberStart( $oldLine );
		}
	}

	/**
	 * Given a phan object (not method/function) find its taint. This always returns a copy
	 * for existing objects.
	 *
	 * @param TypedElementInterface $variableObj
	 * @return Taintedness
	 */
	protected function getTaintednessPhanObj( TypedElementInterface $variableObj ): Taintedness {
		assert( !$variableObj instanceof FunctionInterface, "This method cannot be used with methods" );
		$taintOrNull = self::getTaintednessRaw( $variableObj );
		if ( $taintOrNull !== null ) {
			$mask = $this->getTaintMaskForTypedElement( $variableObj );
			$taintedness = $mask !== null ? $taintOrNull->withOnly( $mask->get() ) : clone $taintOrNull;
			// echo "$varName has taintedness $taintedness due to last time\n";
		} else {
			$type = $variableObj->getUnionType();
			$taintedness = $this->getTaintByType( $type );
			// $this->debug( " \$" . $variableObj->getName() . " first sight."
			// . " taintedness set to $taintedness due to type $type\n";
		}
		return $taintedness;
	}

	/**
	 * Shortcut to resolve array offsets, which includes:
	 *  - Ensuring that the value is not null: null is used for implicit dims like in `$a[] = $b`; we can't say
	 *    for sure what the offset will be, and this method would return null (interpreted as offset 0), which is
	 *    most likely wrong.
	 *  - Casting floats to integers, since using a float as array key raises a warning (and crashes taint-check)
	 *    in PHP 8.1 (T307504)
	 *  - Letting nodes that represent resources (e.g. `STDIN`) pass through, since they're not scalar and certainly
	 *    not valid offsets (see https://github.com/phan/phan/issues/4659).
	 *
	 * @param Node|mixed $rawOffset
	 * @return Node|mixed
	 */
	protected function resolveOffset( $rawOffset ) {
		assert( $rawOffset !== null );
		$resolved = $this->resolveValue( $rawOffset );
		// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.is_resource
		if ( is_resource( $resolved ) ) {
			return $rawOffset;
		}
		return is_float( $resolved ) ? (int)$resolved : $resolved;
	}

	/**
	 * Shortcut to try and turn an AST element (Node or already literal) into an equivalent PHP
	 * scalar value.
	 *
	 * @param Node|mixed $value A Node or a scalar value from the AST
	 * @return Node|mixed An equivalent scalar PHP value, or $value if it cannot be resolved
	 */
	protected function resolveValue( $value ) {
		if ( !$value instanceof Node ) {
			return $value;
		}
		return $this->getCtxN( $value )->getEquivalentPHPScalarValue();
	}

	/**
	 * Get a property by name in the current scope, failing hard if it cannot be found.
	 * @param string $propName
	 * @return Property
	 */
	private function getPropInCurrentScopeByName( string $propName ): Property {
		assert( $this->context->isInClassScope() );
		$clazz = $this->context->getClassInScope( $this->code_base );

		assert( $clazz->hasPropertyWithName( $this->code_base, $propName ) );
		return $clazz->getPropertyByName( $this->code_base, $propName );
	}

	/**
	 * Quick wrapper to get the ContextNode for a node
	 *
	 * @param Node|mixed $node
	 * @return ContextNode
	 */
	protected function getCtxN( $node ): ContextNode {
		return new ContextNode(
			$this->code_base,
			$this->context,
			$node
		);
	}

	/**
	 * Given a node, return the Phan variable objects that
	 * correspond to that node to which we can backpropagate a NUMKEY taintedness.
	 *
	 * @todo This should be handled together with the non-numkey case
	 *
	 * @param Node $node AST node in question
	 * @return TypedElementInterface[] Array of various phan objects corresponding to $node
	 */
	protected function getObjsForNodeForNumkeyBackprop( Node $node ): array {
		$cn = $this->getCtxN( $node );

		// TODO For now we only backprop in the simple case, to avoid tons of false positives, unless
		// the env flag is set (chiefly for tests)
		$definitelyNumkey = !getenv( 'SECCHECK_NUMKEY_SPERIMENTAL' );

		switch ( $node->kind ) {
			case \ast\AST_PROP:
			case \ast\AST_NULLSAFE_PROP:
			case \ast\AST_STATIC_PROP:
				$prop = $this->getPropFromNode( $node );
				return $prop && $this->elementCanBeNumkey( $prop, $definitelyNumkey ) ? [ $prop ] : [];
			case \ast\AST_VAR:
			case \ast\AST_CLOSURE_VAR:
				if ( Variable::isHardcodedGlobalVariableWithName( $cn->getVariableName() ) ) {
					return [];
				} else {
					try {
						$var = $cn->getVariable();
						return $this->elementCanBeNumkey( $var, $definitelyNumkey ) ? [ $var ] : [];
					} catch ( NodeException | IssueException $e ) {
						$this->debug( __METHOD__, "variable not in scope?? " . $this->getDebugInfo( $e ) );
						return [];
					}
					// return [];
				}
			case \ast\AST_ENCAPS_LIST:
			case \ast\AST_ARRAY:
				$results = [];
				foreach ( $node->children as $child ) {
					if ( !is_object( $child ) ) {
						continue;
					}

					if (
						$node->kind === \ast\AST_ARRAY &&
						$child->children['key'] !== null && !$this->nodeCanBeIntKey( $child->children['key'] )
					) {
						continue;
					}
					$results = array_merge( $this->getObjsForNodeForNumkeyBackprop( $child ), $results );
				}
				return $results;
			case \ast\AST_ARRAY_ELEM:
				$results = [];
				if ( is_object( $node->children['key'] ) ) {
					$results = array_merge(
						$this->getObjsForNodeForNumkeyBackprop( $node->children['key'] ),
						$results
					);
				}
				if ( is_object( $node->children['value'] ) ) {
					$results = array_merge(
						$this->getObjsForNodeForNumkeyBackprop( $node->children['value'] ),
						$results
					);
				}
				return $results;
			case \ast\AST_CAST:
				// Future todo might be to ignore casts to ints, since
				// such things should be safe. Unclear if that makes
				// sense in all circumstances.
				if ( $node->children['expr'] instanceof Node ) {
					return $this->getObjsForNodeForNumkeyBackprop( $node->children['expr'] );
				}
				return [];
			case \ast\AST_DIM:
				if ( $node->children['expr'] instanceof Node ) {
					// For now just consider the outermost array.
					// FIXME. doesn't handle tainted array keys!
					return $this->getObjsForNodeForNumkeyBackprop( $node->children['expr'] );
				}
				return [];
			case \ast\AST_UNARY_OP:
				$var = $node->children['expr'];
				return $var instanceof Node ? $this->getObjsForNodeForNumkeyBackprop( $var ) : [];
			case \ast\AST_BINARY_OP:
				$left = $node->children['left'];
				$right = $node->children['right'];
				$leftObj = $left instanceof Node ? $this->getObjsForNodeForNumkeyBackprop( $left ) : [];
				$rightObj = $right instanceof Node ? $this->getObjsForNodeForNumkeyBackprop( $right ) : [];
				return array_merge( $leftObj, $rightObj );
			case \ast\AST_CONDITIONAL:
				$t = $node->children['true'];
				$f = $node->children['false'];
				$tObj = $t instanceof Node ? $this->getObjsForNodeForNumkeyBackprop( $t ) : [];
				$fObj = $f instanceof Node ? $this->getObjsForNodeForNumkeyBackprop( $f ) : [];
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
			case \ast\AST_NULLSAFE_METHOD_CALL:
				if ( $definitelyNumkey ) {
					// This case is too hard for now.
					return [];
				}
				$ctxNode = $this->getCtxN( $node );
				// @todo Future todo might be to still return arguments when catching an exception.
				if ( $node->kind === \ast\AST_CALL ) {
					if ( $node->children['expr']->kind !== \ast\AST_NAME ) {
						// TODO Handle this case!
						return [];
					}
					try {
						$func = $ctxNode->getFunction( $node->children['expr']->children['name'] );
					} catch ( IssueException | FQSENException $e ) {
						$this->debug( __METHOD__, "FIXME func not found: " . $this->getDebugInfo( $e ) );
						return [];
					}
				} else {
					$methodName = $node->children['method'];
					try {
						$func = $ctxNode->getMethod( $methodName, $node->kind === \ast\AST_STATIC_CALL, true );
					} catch ( NodeException | CodeBaseException | IssueException $e ) {
						$this->debug( __METHOD__, "FIXME method not found: " . $this->getDebugInfo( $e ) );
						return [];
					}
				}
				// intentionally resetting options to []
				// here to ensure we don't recurse beyond
				// a depth of 1.
				try {
					return $this->getReturnObjsOfFunc( $func );
				} catch ( Exception $e ) {
					$this->debug( __METHOD__, "FIXME: " . $this->getDebugInfo( $e ) );
					return [];
				}
			case \ast\AST_PRE_INC:
			case \ast\AST_PRE_DEC:
			case \ast\AST_POST_INC:
			case \ast\AST_POST_DEC:
				$children = $node->children;
				assert( count( $children ) === 1 );
				return $this->getObjsForNodeForNumkeyBackprop( reset( $children ) );
			default:
				// TODO Should probably handle AST_MATCH & friends
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
	 * @param Node $node
	 * @return Property|null
	 */
	protected function getPropFromNode( Node $node ): ?Property {
		try {
			return $this->getCtxN( $node )->getProperty( $node->kind === \ast\AST_STATIC_PROP );
		} catch ( NodeException | IssueException | UnanalyzableException $e ) {
			$this->debug( __METHOD__, "Cannot determine " .
				"property [3] (Maybe don't know what class) - " .
				$this->getDebugInfo( $e )
			);
			return null;
		}
	}

	/**
	 * Extract some useful debug data from an exception
	 * @param Exception $e
	 * @return string
	 */
	protected function getDebugInfo( Exception $e ): string {
		return $e instanceof IssueException
			? $e->getIssueInstance()->__toString()
			: ( get_class( $e ) . " {$e->getMessage()}" );
	}

	/**
	 * Get the current filename and line.
	 *
	 * @param Context|null $context Override the context to make debug info for
	 * @return string path/to/file +linenumber
	 */
	protected function dbgInfo( Context $context = null ): string {
		$ctx = $context ?: $this->context;
		// Using a + instead of : so that I can just copy and paste
		// into a vim command line.
		return $ctx->getFile() . ' +' . $ctx->getLineNumberStart();
	}

	/**
	 * Link together a Method and its parameters,the idea being if the method gets called with something evil
	 * later, we can traceback anything it might affect.
	 * Note that we don't do this for functions with hardcoded taint, in which case we assume that any dangerous
	 * association was already hardcoded. This is also good for performance, because hardcoded function tend to be
	 * used a lot (for MW, think of methods in Database or in Html).
	 *
	 * @param Variable $param The variable object for the parameter. This can also be
	 *  instance of Parameter (subclass of Variable).
	 * @param FunctionInterface $func The function/method in question
	 * @param int $i Which argument number is $param
	 */
	protected function linkParamAndFunc( Variable $param, FunctionInterface $func, int $i ): void {
		// $this->debug( __METHOD__, "Linking '$param' to '$func' arg $i" );

		// TODO Use $func's builtin/annotated taintedness (available in PreTaintednessVisitor) to check this per
		// parameter (looking at NO_OVERRIDE)
		$canLinkParam = !SecurityCheckPlugin::$pluginInstance->builtinFuncHasTaint( $func->getFQSEN() );
		if ( !$canLinkParam ) {
			return;
		}

		self::ensureVarLinksForArgExist( $func, $i );

		$paramLinks = self::getMethodLinksCloneOrEmpty( $param );
		$paramLinks->initializeParamForFunc( $func, $i );
		self::setMethodLinks( $param, $paramLinks );
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
	 * @param MethodLinks $rhsLinks New links
	 * @param bool $override
	 */
	protected function mergeTaintDependencies(
		TypedElementInterface $lhs,
		MethodLinks $rhsLinks,
		bool $override
	): void {
		// So if we have $a = $b;
		// First we find out all the methods that can set $b
		// Then we add $a to the list of variables that those methods can set.
		// Last we add these methods to $a's list of all methods that can set it.
		if ( $lhs instanceof Property || $lhs instanceof GlobalVariable || $lhs instanceof PassByReferenceVariable ) {
			// Don't attach things like Variable and Parameter. These are local elements, and setting taint
			// on them in markAllDependentVarsYes would have no effect. Additionally, since phan creates a new
			// Parameter object for each analysis, we will end up with duplicated links that do nothing but
			// eating memory.
			foreach ( $rhsLinks->getMethodAndParamTuples() as [ $method, $index ] ) {
				$varLinks = self::getVarLinks( $method, $index );
				assert( $varLinks instanceof Set );
				// $this->debug( __METHOD__, "During assignment, we link $lhs to $method($index)" );
				$varLinks->attach( $lhs );
			}
		}

		$curLinks = self::getMethodLinks( $lhs );
		if ( $override || !$curLinks ) {
			$newLinks = $rhsLinks;
		} else {
			$newLinks = $curLinks->asMergedWith( $rhsLinks );
		}
		self::setMethodLinks( $lhs, $newLinks );
	}

	/**
	 * Mark any function setting a specific variable as EXEC taint
	 *
	 * If you do something like echo $this->foo;
	 * This method is called to make all things that set $this->foo
	 * as TAINT_EXEC.
	 *
	 * @note This might have annoying false positives with widely used properties
	 * that are used with different levels of escaping, which is not a good idea anyway.
	 *
	 * @param TypedElementInterface $var The variable in question
	 * @param Taintedness $taint What taint to mark them as.
	 * @param CausedByLines|null $additionalError Any extra caused-by lines to add
	 */
	protected function markAllDependentMethodsExec(
		TypedElementInterface $var,
		Taintedness $taint,
		CausedByLines $additionalError = null
	): void {
		$futureTaint = $this->getPossibleFutureTaintOfElement( $var );
		if ( $futureTaint !== null && !$futureTaint->has( $taint->get() ) ) {
			return;
		}
		// Ensure we only set exec bits, not normal taint bits.
		$taint = $taint->withOnly( SecurityCheckPlugin::BACKPROP_TAINTS );
		if ( $taint->isSafe() || $this->isIssueSuppressedOrFalsePositive( $taint ) ) {
			return;
		}

		$varLinks = self::getMethodLinks( $var );
		if ( $varLinks === null || $varLinks->isEmpty() ) {
			return;
		}
		$backpropError = self::getCausedByRawCloneOrEmpty( $var );
		if ( $additionalError ) {
			$backpropError->mergeWith( $additionalError );
		}

		// $this->debug( __METHOD__, "Setting {$var->getName()} exec {$taint->toShortString()}" );
		$oldMem = memory_get_peak_usage();

		foreach ( self::getRelevantLinksForTaintedness( $varLinks, $taint ) as [ $curLinks, $curTaint ] ) {
			/** @var MethodLinks $curLinks */
			/** @var Taintedness $curTaint */
			$curLinksAll = $curLinks->getLinks();
			foreach ( $curLinksAll as $method ) {
				$paramInfo = $curLinksAll[$method];
				// Note, not forCaller, as that doesn't see variadic parameters
				$calleeParamList = $method->getParameterList();
				$paramTaint = new FunctionTaintedness( Taintedness::newSafe() );
				$funcError = new FunctionCausedByLines();
				foreach ( $paramInfo->getParams() as $i => $paramOffsets ) {
					$curParTaint = $curTaint->asMovedAtRelevantOffsetsForBackprop( $paramOffsets );
					if ( isset( $calleeParamList[$i] ) && $calleeParamList[$i]->isVariadic() ) {
						$paramTaint->setVariadicParamSinkTaint( $i, $curParTaint );
						$funcError->setVariadicParamSinkLines( $i, $backpropError );
					} else {
						$paramTaint->setParamSinkTaint( $i, $curParTaint );
						$funcError->setParamSinkLines( $i, $backpropError );
					}
					// $this->debug( __METHOD__, "Setting method $method arg $i as $taint due to dependency on $var" );
				}
				$this->addFuncTaint( $method, $paramTaint );
				$newFuncTaint = self::getFuncTaint( $method );
				assert( $newFuncTaint !== null );
				$this->maybeAddFuncError( $method, null, $paramTaint, $newFuncTaint );
				$this->mergeFuncError( $method, $funcError, $newFuncTaint );
			}
		}

		$newMem = memory_get_peak_usage();
		$diffMem = round( ( $newMem - $oldMem ) / ( 1024 * 1024 ) );
		if ( $diffMem > 2 ) {
			$this->debug( __METHOD__, "Memory spike $diffMem for variable " . $var->getName() );
		}
	}

	/**
	 * @param MethodLinks $allLinks
	 * @param Taintedness $taintedness
	 * @return array[]
	 * @phan-return array<array{0:MethodLinks,1:Taintedness}>
	 */
	private static function getRelevantLinksForTaintedness( MethodLinks $allLinks, Taintedness $taintedness ): array {
		if ( $taintedness->hasSomethingOutOfKnownDims() || $allLinks->hasSomethingOutOfKnownDims() ) {
			// TODO Improve this case (e.g. unknown offsets).
			return [ [ $allLinks, $taintedness ] ];
		}
		$pairs = [];
		foreach ( $taintedness->getDimTaint() as $k => $dimTaint ) {
			$pairs = array_merge(
				$pairs,
				self::getRelevantLinksForTaintedness( $allLinks->getForDim( $k ), $dimTaint )
			);
		}
		return $pairs;
	}

	/**
	 * Mark any function setting a specific variable as EXEC taint
	 *
	 * If you do something like echo $this->foo;
	 * This method is called to make all things that set $this->foo
	 * as TAINT_EXEC.
	 *
	 * @note This might have annoying false positives with widely used properties
	 * that are used with different levels of escaping, which is not a good idea anyway.
	 *
	 * @param Node $node
	 * @param Taintedness $taint What taint to mark them as.
	 * @param CausedByLines|null $additionalError Additional caused-by lines to propagate
	 * @param bool $tempNumkey Temporary param
	 */
	protected function markAllDependentMethodsExecForNode(
		Node $node,
		Taintedness $taint,
		CausedByLines $additionalError = null,
		bool $tempNumkey = false
	): void {
		if ( !$tempNumkey ) {
			$backpropVisitor = new TaintednessBackpropVisitor(
				$this->code_base,
				$this->context,
				$taint,
				$additionalError
			);
			$backpropVisitor( $node );
			return;
		}
		$phanObjs = $this->getObjsForNodeForNumkeyBackprop( $node );
		foreach ( array_unique( $phanObjs ) as $phanObj ) {
			$this->markAllDependentMethodsExec( $phanObj, $taint, $additionalError );
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
	 * @param Taintedness $taint The taint to apply.
	 * @param CausedByLines $error Caused-by lines to propagate
	 */
	protected function markAllDependentVarsYes(
		FunctionInterface $method,
		int $i,
		Taintedness $taint,
		CausedByLines $error
	): void {
		if ( $method->isPHPInternal() ) {
			return;
		}
		$varLinks = self::getVarLinks( $method, $i );
		if ( $varLinks === null ) {
			return;
		}

		$taintAdjusted = $taint->withOnly( SecurityCheckPlugin::ALL_TAINT );

		foreach ( $varLinks as $var ) {
			if ( $var instanceof PassByReferenceVariable ) {
				// TODO This should become unnecessary once the TODO in handleMethodCall about postponing
				// handlePassByRef is resolved.
				$var = $var->getElement();
			}
			assert( $var instanceof TypedElementInterface );

			$this->setTaintedness( $var, $taintAdjusted, false );
			$this->addTaintError( $var, $taintAdjusted, null );
			if ( $var instanceof GlobalVariable ) {
				$globalVar = $var->getElement();
				$this->setTaintedness( $globalVar, $taintAdjusted, false );
				$this->addTaintError( $globalVar, $taintAdjusted, null );
			}
			$this->mergeTaintError( $var, $error );
		}
	}

	/**
	 * Get the original cause of taint for the given func
	 *
	 * @param FunctionInterface $element
	 * @return FunctionCausedByLines
	 */
	private function getCausedByLinesForFunc( FunctionInterface $element ): FunctionCausedByLines {
		$element = $this->getActualFuncWithCausedBy( $element );
		return self::getFuncCausedByRawCloneOrEmpty( $element );
	}

	/**
	 * Given a phan element, get the actual element where caused-by data is stored. For instance, for methods, this
	 * returns the defining methods.
	 *
	 * @param FunctionInterface $element
	 * @return FunctionInterface
	 */
	private function getActualFuncWithCausedBy( FunctionInterface $element ): FunctionInterface {
		if ( SecurityCheckPlugin::$pluginInstance->builtinFuncHasTaint( $element->getFQSEN() ) ) {
			return $element;
		}
		$definingFunc = $this->getDefiningFuncIfDifferent( $element );
		return $definingFunc ?? $element;
	}

	/**
	 * Output a debug message to stdout.
	 *
	 * @param string $method __METHOD__ in question
	 * @param string $msg debug message
	 */
	public function debug( $method, $msg ): void {
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
		$line = $method . "\33[1m " . $this->dbgInfo() . " \33[0m" . $msg . "\n";
		if ( $this->debugOutput && $this->debugOutput !== '-' ) {
			fwrite(
				$this->debugOutput,
				$line
			);
		} elseif ( $this->debugOutput === '-' ) {
			// @phan-suppress-next-line PhanPluginRemoveDebugEcho This is the only wanted debug echo
			echo $line;
		}
	}

	/**
	 * Given an AST node that's a callable, try and determine what it is
	 *
	 * This is intended for functions that register callbacks.
	 *
	 * @param Node|mixed $node The thingy from AST expected to be a Callable
	 * @return FunctionInterface|null
	 */
	protected function getCallableFromNode( $node ): ?FunctionInterface {
		if ( is_string( $node ) ) {
			// Easy case, 'Foo::Bar'
			// NOTE: ContextNode::getFunctionFromNode has a TODO about returning something here.
			// And also NOTE: 'self::methodname()' is not valid PHP.
			// TODO: We should probably emit a non-security issue in the missing case
			if ( strpos( $node, '::' ) === false ) {
				$callback = FullyQualifiedFunctionName::fromFullyQualifiedString( $node );
				return $this->code_base->hasFunctionWithFQSEN( $callback )
					? $this->code_base->getFunctionByFQSEN( $callback )
					: null;
			}
			$callback = FullyQualifiedMethodName::fromFullyQualifiedString( $node );
			return $this->code_base->hasMethodWithFQSEN( $callback )
				? $this->code_base->getMethodByFQSEN( $callback )
				: null;
		}
		if ( !$node instanceof Node ) {
			return null;
		}
		if (
			$node->kind === \ast\AST_CLOSURE ||
			$node->kind === \ast\AST_VAR ||
			( $node->kind === \ast\AST_ARRAY && count( $node->children ) === 2 )
		) {
			// Note: intentionally emitting any issues here.
			$funcs = $this->getCtxN( $node )->getFunctionFromNode();
			return self::getFirstElmFromArrayOrGenerator( $funcs );
		}
		return null;
	}

	/**
	 * Utility function to get the first element from an iterable that can be either an array or a generator
	 * @phan-template T
	 * @param iterable $iter
	 * @phan-param iterable<T> $iter
	 * @return mixed|null Null if $iter is empty
	 * @phan-return T|null
	 */
	protected static function getFirstElmFromArrayOrGenerator( iterable $iter ) {
		if ( is_array( $iter ) ) {
			return $iter ? $iter[0] : null;
		}
		assert( $iter instanceof Generator );
		return $iter->current() ?: null;
	}

	/**
	 * Get the issue name and severity given a taint
	 *
	 * @param int $combinedTaint The taint to warn for. I.e. The exec flags
	 *   from LHS shifted to non-exec bitwise AND'd with the rhs taint.
	 * @return array Issue type and severity
	 * @phan-return array{0:string,1:int}
	 */
	public function taintToIssueAndSeverity( int $combinedTaint ): array {
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
			case SecurityCheckPlugin::PATH_TAINT:
				$issueType = 'SecurityCheck-PathTraversal';
				break;
			case SecurityCheckPlugin::CODE_TAINT:
				$issueType = 'SecurityCheck-RCE';
				break;
			case SecurityCheckPlugin::REGEX_TAINT:
				$issueType = 'SecurityCheck-ReDoS';
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
	 * Simplified version of maybeEmitIssue which makes the following assumptions:
	 *  - The caller would compute the RHS taint only to feed it to maybeEmitIssue
	 *  - The message should be followed by caused-by lines
	 *  - These caused-by lines should be taken from the same object passed as RHS
	 *  - Only caused-by lines having the LHS taint should be included
	 * If these conditions hold true, then this method should be preferred.
	 *
	 * @warning DO NOT use this method if the caller already needs to compute the RHS
	 * taintedness! The taint would be computed twice!
	 *
	 * @param Taintedness $lhsTaint
	 * @param mixed $rhsElement
	 * @param string $msg
	 * @param array $params Additional parameters for the message template
	 * @phan-param list<string|FullyQualifiedFunctionLikeName> $params
	 * @throws Exception
	 */
	public function maybeEmitIssueSimplified(
		Taintedness $lhsTaint,
		$rhsElement,
		string $msg,
		array $params = []
	): void {
		$rhsTaint = $this->getTaintedness( $rhsElement );
		$this->maybeEmitIssue(
			$lhsTaint,
			$rhsTaint->getTaintedness(),
			$msg . '{DETAILS}',
			array_merge( $params, [ $rhsTaint->getError() ] )
		);
	}

	/**
	 * Emit an issue using the appropriate issue type
	 *
	 * If $this->overrideContext is set, it will use that for the
	 * file/line number to report. This is meant as a hack, so that
	 * in MW we can force hook related issues to be in the extension
	 * instead of where the hook is called from in MW core.
	 *
	 * @param Taintedness $lhsTaint Taint of left hand side (or equivalent)
	 * @param Taintedness $rhsTaint Taint of right hand side (or equivalent)
	 * @param string $msg Issue description
	 * @param array $msgParams Message parameters passed to emitIssue
	 * @phan-param list $msgParams
	 */
	public function maybeEmitIssue(
		Taintedness $lhsTaint,
		Taintedness $rhsTaint,
		string $msg,
		array $msgParams
	): void {
		$rhsIsUnknown = $rhsTaint->has( SecurityCheckPlugin::UNKNOWN_TAINT );
		if ( $rhsIsUnknown && $lhsTaint->has( SecurityCheckPlugin::ALL_EXEC_TAINT ) ) {
			$combinedTaint = Taintedness::newSafe();
			$combinedTaintInt = SecurityCheckPlugin::NO_TAINT;
		} else {
			$combinedTaint = Taintedness::intersectForSink( $lhsTaint, $rhsTaint );
			if ( $combinedTaint->isSafe() ) {
				return;
			}
			$combinedTaintInt = Taintedness::flagsAsExecToYesTaint( $combinedTaint->get() );
		}

		if (
			( $combinedTaintInt === SecurityCheckPlugin::NO_TAINT && $rhsIsUnknown ) ||
			SecurityCheckPlugin::$pluginInstance->isFalsePositive(
				$combinedTaintInt,
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
				$combinedTaintInt
			);
		}

		// If we have multiple, include what types.
		if ( $issueType === 'SecurityCheckMulti' ) {
			$msg .= ' (' . SecurityCheckPlugin::taintToString( $lhsTaint->get() ) .
				' <- ' . SecurityCheckPlugin::taintToString( $rhsTaint->get() ) . ')';
		}

		$context = $this->context;
		if ( $this->overrideContext ) {
			// If we are overriding the file/line number,
			// report the original line number as well.
			$msg .= " (Originally at: $this->context)";
			$context = $this->overrideContext;
		}

		foreach ( $msgParams as $i => $par ) {
			if ( $par instanceof CausedByLines ) {
				$msgParams[$i] = $par->toStringForIssue( $combinedTaint );
			}
		}

		SecurityCheckPlugin::emitIssue(
			$this->code_base,
			$context,
			$issueType,
			$msg,
			$msgParams,
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
	 * @param Taintedness $lhsTaint Must have at least one EXEC flag set
	 * @return bool
	 */
	public function isIssueSuppressedOrFalsePositive( Taintedness $lhsTaint ): bool {
		$lhsTaintInt = $lhsTaint->get();
		assert( ( $lhsTaintInt & SecurityCheckPlugin::ALL_EXEC_TAINT ) !== SecurityCheckPlugin::NO_TAINT );
		$combinedTaint = Taintedness::flagsAsExecToYesTaint( $lhsTaintInt );
		$issueType = $this->taintToIssueAndSeverity( $combinedTaint )[0];

		$context = $this->overrideContext ?: $this->context;
		if ( $context->hasSuppressIssue( $this->code_base, $issueType ) ) {
			return true;
		}

		$msg = "[dummy msg for false positive check]";
		return SecurityCheckPlugin::$pluginInstance->isFalsePositive(
			$combinedTaint,
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
	 * @phan-param array<Node|mixed> $args
	 * @param bool $computePreserve Whether the caller wants to know which taintedness is preserved by this call
	 * @param bool $isHookHandler Whether we're analyzing a hook handler for a Hooks::run call.
	 *   FIXME This is MW-specific
	 * @return TaintednessWithError|null Taint The resulting taint of the expression, or null if
	 *   $computePreserve is false
	 */
	public function handleMethodCall(
		FunctionInterface $func,
		FullyQualifiedFunctionLikeName $funcName,
		array $args,
		bool $computePreserve = true,
		$isHookHandler = false
	): ?TaintednessWithError {
		$taint = $this->getTaintOfFunction( $func );
		$containingMethod = $this->getCurrentMethod();
		$funcError = $this->getCausedByLinesForFunc( $func );

		if ( $computePreserve ) {
			$overallArgTaint = Taintedness::newSafe();
			$argErrors = new CausedByLines();
		}

		foreach ( $args as $i => $argument ) {
			if ( !( $argument instanceof Node ) ) {
				// Literal value
				continue;
			}
			$curParFlags = $taint->getParamFlags( $i );
			if ( ( $curParFlags & SecurityCheckPlugin::ARRAY_OK ) && $this->nodeIsArray( $argument ) ) {
				// This function specifies that arrays are always ok, so skip.
				continue;
			}

			if ( $argument->kind === \ast\AST_NAMED_ARG ) {
				[ $i, $argument, $argName ] = $this->translateNamedArg( $argument, $func );
				if ( $i === null || !$argument instanceof Node ) {
					// Cannot find argument or it's literal
					continue;
				}
				$argName = "`$argName`";
			} else {
				$argName = '#' . ( $i + 1 );
			}

			$paramSinkTaint = $taint->getParamSinkTaint( $i );

			$argTaintWithError = $this->getTaintednessNode( $argument );
			$curArgTaintedness = $argTaintWithError->getTaintedness();
			$baseArgError = $argTaintWithError->getError();
			if (
				$paramSinkTaint->has( SecurityCheckPlugin::SQL_NUMKEY_EXEC_TAINT )
				&& $curArgTaintedness->has( SecurityCheckPlugin::SQL_TAINT )
				&& $this->nodeCanBeString( $argument )
			) {
				// Special case to make NUMKEY work right for non-array values.
				// TODO Should consider if this is really best approach.
				$curArgTaintedness->add( SecurityCheckPlugin::SQL_NUMKEY_TAINT );
			}

			$isRawParam = ( $curParFlags & SecurityCheckPlugin::RAW_PARAM ) !== 0;

			// Add a hook in order to special case for codebases. This is primarily used as a hack so that in mediawiki
			// the Message class doesn't have double escape taint if method takes Message|string.
			// TODO This is quite hacky.
			$curArgTaintedness = SecurityCheckPlugin::$pluginInstance->modifyArgTaint(
				$curArgTaintedness,
				$argument,
				$i,
				$func,
				$taint,
				$this->context,
				$this->code_base
			);

			// TODO: We also need to handle the case where someFunc( $execArg ) for pass by reference where
			// the parameter is later executed outside the func.
			if ( $curArgTaintedness->has( SecurityCheckPlugin::ALL_TAINT ) ) {
				$this->markAllDependentVarsYes( $func, $i, $curArgTaintedness, $baseArgError );
			}

			// We are doing something like evilMethod( $arg ); where $arg is a parameter to the current function.
			// So backpropagate that assigning to $arg can cause evilness.
			if ( !$isRawParam && !$paramSinkTaint->isSafe() ) {
				$this->backpropagateArgTaint( $argument, $paramSinkTaint, $funcError->getParamSinkLines( $i ) );
			}

			$param = $func->getParameterForCaller( $i );
			// @todo Internal funcs that pass by reference. Should we assume that their variables are tainted? Most
			// common example is probably preg_match, which may very well be tainted much of the time.
			// TODO: Ideally this should happen after all args have been processed, so it would account for any
			// last-minute modification of the dependent elements (e.g. markAllDependentVarsYes) and would see the
			// "final" value for refTaint. Right now this is not possible because links tracked by
			// markAllDependentVarsYes are imprecise and would introduce false positives.
			if ( $param && $param->isPassByReference() && !$func->isPHPInternal() ) {
				$this->handlePassByRef( $func, $argument, $i, $isHookHandler );
			}

			// Always include the ordinal (it helps for repeated arguments)
			$taintedArg = $argName;
			$argStr = ASTReverter::toShortString( $argument );
			if ( !( $argStr instanceof Node ) && strlen( $argStr ) < 25 ) {
				// If we have a short representation of the arg, include it as well.
				$taintedArg .= " (`$argStr`)";
			}

			$this->maybeEmitIssue(
				$paramSinkTaint,
				$curArgTaintedness,
				"Calling method {FUNCTIONLIKE}() in {FUNCTIONLIKE}" .
				" that outputs using tainted argument {CODE}.{DETAILS}{DETAILS}{DETAILS}",
				[
					$funcName,
					$containingMethod,
					$taintedArg,
					$funcError->getParamSinkLines( $i ),
					$baseArgError,
					$isRawParam ? ' (Param is raw)' : ''
				]
			);

			if ( $computePreserve ) {
				$preserveOrUnknown = SecurityCheckPlugin::PRESERVE_TAINT | SecurityCheckPlugin::UNKNOWN_TAINT;
				if ( $taint->hasParamPreserve( $i ) ) {
					$parTaint = $taint->getParamPreservedTaint( $i );
					$effectiveArgTaintedness = $parTaint->asTaintednessForArgument( $curArgTaintedness );
					$curArgLinks = MethodLinks::newEmpty();
				} elseif ( $taint->getOverall()->has( $preserveOrUnknown ) ) {
					// No info for this specific parameter, but the overall function either preserves taint
					// when unspecified or is unknown. So just pass the taint through.
					$effectiveArgTaintedness = $this->getNewPreservedTaintForParam( $func, $curArgTaintedness, $i );
					$curArgLinks = MethodLinks::newEmpty();
				} else {
					// This parameter has no taint info. And overall this function doesn't depend on param
					// for taint and isn't unknown. So we consider this argument untainted.
					continue;
				}

				'@phan-var Taintedness $overallArgTaint';
				'@phan-var CausedByLines $argErrors';
				$overallArgTaint->mergeWith( $effectiveArgTaintedness );
				$curArgError = $baseArgError->asIntersectedWithTaintedness( $effectiveArgTaintedness );
				$relevantParamError = $funcError->getParamPreservedLines( $i )
					->asPreservingTaintednessAndLinks( $effectiveArgTaintedness, $curArgLinks );
				$curArgError->mergeWith( $relevantParamError );
				// NOTE: If any line inside the callee's body is responsible for preserving the taintedness of more
				// than one argument, it will appear once per preserved argument in the overall caused-by of the
				// call expression. This is probably a good thing, but can increase the length of caused-by lines.
				// TODO Something like T291379 might help here.
				$argErrors->mergeWith( $curArgError );
			}
		}

		if ( !$computePreserve ) {
			return null;
		}
		'@phan-var Taintedness $overallArgTaint';
		'@phan-var CausedByLines $argErrors';

		$overallTaint = $taint->getOverall()->without(
			SecurityCheckPlugin::PRESERVE_TAINT | SecurityCheckPlugin::ALL_EXEC_TAINT
		);
		$overallArgTaint->remove( SecurityCheckPlugin::ALL_EXEC_TAINT );
		$callTaintedness = $overallTaint->asMergedWith( $overallArgTaint );
		$callError = $funcError->getGenericLines()->asMergedWith( $argErrors );
		return new TaintednessWithError( $callTaintedness, $callError, MethodLinks::newEmpty() );
	}

	/**
	 * @todo This should possibly be part of the public interface upstream
	 * @see \Phan\Analysis\ArgumentType::analyzeParameterListForCallback
	 * @param Node $argument
	 * @param FunctionInterface $func
	 * @return array
	 * @phan-return array{0:int|null,1:Node|mixed,2:?string}
	 */
	private function translateNamedArg( Node $argument, FunctionInterface $func ): array {
		[ 'name' => $argName, 'expr' => $argExpr ] = $argument->children;
		assert( $argExpr !== null );

		foreach ( $func->getRealParameterList() as $i => $parameter ) {
			if ( $parameter->getName() === $argName ) {
				return [ $i, $argExpr, $argName ];
			}
		}
		return [ null, null, null ];
	}

	/**
	 * @param Node $argument
	 * @param Taintedness $taint
	 * @param CausedByLines|null $funcError
	 *
	 * @todo This has false negatives, because we don't collect function arguments in
	 * getPhanObjsForNode (we'd have to pass option 'all'), so we can't handle e.g. array_merge
	 * right now. However, collecting all args would create false positives with functions where
	 * the arg taint isn't propagated to the return value. Ideally, we'd want to include an argument
	 * iff the corresponding parameter passes $taint through.
	 *
	 * @note It's important that we don't backpropagate taintedness to every returned object in case
	 * of function calls, but just props and the like (so excluding vars). See test 'toomanydeps'.
	 */
	protected function backpropagateArgTaint(
		Node $argument,
		Taintedness $taint,
		CausedByLines $funcError = null
	): void {
		if ( $taint->has( SecurityCheckPlugin::SQL_NUMKEY_EXEC_TAINT ) ) {
			// Special case for numkey, we need to "filter" the argument.
			// TODO This doesn't return arrays with mixed keys. Currently, doing so would result
			// in arrays being considered as a unit, and the taint would be backpropagated to all
			// values, even ones with string keys. See TODO in elementCanBeNumkey

			// TODO This should be limited to the outer array, see TODO in backpropnumkey test
			// Note that this is true in general for NUMKEY taint, not just when backpropagating it
			$numkeyTaint = $taint->withOnly( SecurityCheckPlugin::SQL_NUMKEY_EXEC_TAINT );
			$this->markAllDependentMethodsExecForNode( $argument, $numkeyTaint, $funcError, true );
			$taint = $taint->without( SecurityCheckPlugin::SQL_NUMKEY_EXEC_TAINT );
		}

		$this->markAllDependentMethodsExecForNode( $argument, $taint, $funcError );
	}

	/**
	 * Handle pass-by-ref params when examining a function call. Phan handles passbyref by reanalyzing
	 * the method with PassByReferenceVariable objects instead of Parameters. These objects contain
	 * the info about the param, but proxy all calls to the underlying argument object.
	 * We cannot 100% copy that behaviour: inside the function body, the local variable for the pbr param
	 * would have the same taintedness as the argument, and things like `echo $pbr` would emit an issue
	 * inside the function, which is unwanted for now. Additionally, it's unclear how we'd add a caused-by
	 * entry for the line of the function call.
	 * Hence, instead of adding taintedness to the underlying argument, we put it in a separate prop, which is only
	 * written but never read inside the function body. Then after the call was analyzed, this method moves
	 * the taintedness from the "special" prop onto the normal taintedness prop. We do the same thing for links,
	 * so as to infer which taintedness from the argument is preserved by the function.
	 * TODO In the future we might want to really copy phan's approach, as that would allow us to delete some hacks,
	 *   and handle conditionals inside the function body more accurately.
	 *
	 * @param FunctionInterface $func
	 * @param Node $argument
	 * @param int $i Position of the param
	 * @param bool $isHookHandler Whether we're analyzing a hook handler for a Hooks::run call.
	 *   FIXME This is MW-specific
	 * @throws Exception
	 */
	private function handlePassByRef(
		FunctionInterface $func,
		Node $argument,
		int $i,
		bool $isHookHandler
	): void {
		$argObj = $this->getPassByRefObjFromNode( $argument );
		if ( !$argObj ) {
			return;
		}
		$refTaint = self::getTaintednessRef( $argObj );
		if ( !$refTaint ) {
			// If no ref taint was set, it's likely due to a recursive call or another instance where phan is not
			// reanalyzing the callee with PassByReferenceVariable objects.
			return;
		}

		$globalVarObj = $argObj instanceof GlobalVariable ? $argObj->getElement() : null;
		// Move the ref taintedness to the "actual" taintedness of the object
		// Note: We assume that the order in which hook handlers are called is nondeterministic, thus
		// we never override arg taint for reference params in this case.
		$overrideTaint = !( $argObj instanceof Property || $globalVarObj || $isHookHandler );
		// Note, the call itself is only responsible if it adds some taintedness
		$errTaint = clone $refTaint;
		$refLinks = self::getMethodLinksRef( $argObj );
		if ( $refLinks && $refLinks->hasDataForFuncAndParam( $func, $i ) ) {
			$addedTaint = $refLinks->asPreservedTaintednessForFuncParam( $func, $i )
				->asTaintednessForArgument( $this->getTaintednessPhanObj( $argObj ) );
			$refTaint->mergeWith( $addedTaint );
		}

		$this->setTaintedness( $argObj, $refTaint, $overrideTaint );
		$this->addTaintError( $argObj, $errTaint, null );
		if ( $globalVarObj ) {
			$this->setTaintedness( $globalVarObj, $refTaint, false );
			$this->addTaintError( $globalVarObj, $errTaint, null );
		}
		// We clear method links since the by-ref call might have modified them, and precise tracking is not
		// trivial to implement, and most probably not worth the effort.
		self::setMethodLinks( $argObj, MethodLinks::newEmpty() );
		self::clearRefData( $argObj );
	}

	/**
	 * Given the node of an argument that is passed by reference, return a list of phan objects
	 * corresponding to that node.
	 *
	 * @param Node $node
	 * @return TypedElementInterface|null
	 */
	private function getPassByRefObjFromNode( Node $node ): ?TypedElementInterface {
		$cn = $this->getCtxN( $node );

		switch ( $node->kind ) {
			case \ast\AST_PROP:
			case \ast\AST_STATIC_PROP:
				return $this->getPropFromNode( $node );
			case \ast\AST_VAR:
				if ( Variable::isHardcodedGlobalVariableWithName( $cn->getVariableName() ) ) {
					return null;
				}
				try {
					return $cn->getVariable();
				} catch ( NodeException | IssueException $_ ) {
					return null;
				}
			case \ast\AST_DIM:
				// Phan doesn't handle this case with PassByReferenceVariable objects, so nothing we can do anyway.
				return null;
			default:
				$this->debug( __METHOD__, 'Unhandled pass-by-ref case: ' . Debug::nodeName( $node ) );
				return null;
		}
	}

	/**
	 * Get the effect of $func on the shape of $curArgTaint (which is argument to param $paramIdx).
	 * Note, this is for the return value, and not e.g. for passbyref effects.
	 *
	 * @param FunctionInterface $func
	 * @param Taintedness $curArgTaint
	 * @param int $paramIdx
	 * @return Taintedness
	 */
	protected function getNewPreservedTaintForParam(
		FunctionInterface $func,
		Taintedness $curArgTaint,
		int $paramIdx
	): Taintedness {
		if ( !$func->isPHPInternal() ) {
			return $curArgTaint->asCollapsed();
		}

		switch ( ltrim( $func->getName(), '\\' ) ) {
			// These return one or more elements (first param; no other params should be provided, but who knows)
			case 'array_pop':
			case 'array_shift':
			case 'current':
			case 'end':
			case 'next':
			case 'pos':
			case 'prev':
			case 'reset':
				return $paramIdx === 0 ? $curArgTaint->asValueFirstLevel() : $curArgTaint->asCollapsed();
			case 'array_values':
				if ( $paramIdx === 0 ) {
					$ret = $curArgTaint->withoutKeys();
					return $ret->has( SecurityCheckPlugin::SQL_TAINT )
						? $ret->with( SecurityCheckPlugin::SQL_NUMKEY_TAINT )
						: $ret;
				}
				return $curArgTaint->asCollapsed();
			// These return one or more keys
			case 'key':
			case 'array_key_first':
			case 'array_key_last':
			case 'array_keys':
				return $paramIdx === 0 ? $curArgTaint->asKeyForForeach() : $curArgTaint->asCollapsed();
			// No effect on the shape, and second param is safe
			case 'array_change_key_case':
				return $paramIdx === 0 ? clone $curArgTaint : Taintedness::newSafe();
			// TODO For now, we assume that all functions in this case preserve the shape
			// TODO Handling these ones should be easywith diff() and intersect() methods in Taintedness.
			case 'array_diff':
			case 'array_diff_assoc':
			case 'array_intersect':
			case 'array_intersect_assoc':
			case 'array_intersect_key':
			// TODO Last parameter of these is a callback, so probably hard to handle. They're also variadic,
			// so we'd need to know the arg type to determine whether we have a callback. Note that we're
			// currently cloning the taint for cb params.
			case 'array_diff_uassoc':
			case 'array_diff_ukey':
			case 'array_intersect_uassoc':
			case 'array_intersect_ukey':
			case 'array_udiff':
			case 'array_udiff_assoc':
			case 'array_uintersect':
			case 'array_uintersect_assoc':
			// TODO Last two params of these are callbacks, so twice as hard
			case 'array_udiff_uassoc':
			case 'array_uintersect_uassoc':
				return clone $curArgTaint;
			case 'array_flip':
				$ret = $curArgTaint->asKeyForForeach();
				$ret->addKeysTaintedness( $curArgTaint->asValueFirstLevel()->get() );
				return $ret;
			case 'join':
				return $curArgTaint->withoutKeys()->asCollapsed();
			case 'implode':
				// Arg 0 shouldn't be shaped, but who knows...
				return $paramIdx === 0 ? $curArgTaint->asCollapsed() : $curArgTaint->withoutKeys()->asCollapsed();
			case 'array_fill':
				// TODO: We cannot build a shape yet
				return $paramIdx === 2 ? $curArgTaint->asCollapsed() : Taintedness::newSafe();
			case 'array_fill_keys':
				// TODO: We cannot build a shape yet
				return $paramIdx === 0 ? $curArgTaint->asValueFirstLevel() : $curArgTaint->asCollapsed();
			case 'array_combine':
				if ( $paramIdx === 0 ) {
					$ret = Taintedness::newSafe();
					$ret->addKeysTaintedness( $curArgTaint->withoutKeys()->get() );
					return $ret;
				}
				return $curArgTaint->withoutKeys();
			// TODO These would really require knowing the other args
			case 'unset':
			case 'array_merge':
			case 'array_merge_recursive':
			case 'array_replace':
			case 'array_replace_recursive':
			case 'array_pad':
			case 'array_reverse':
			case 'array_slice':
			case 'array_map':
			case 'array_filter':
			case 'array_reduce':
			// We can't tell what gets removed
			case 'array_unique':
			default:
				return $curArgTaint->asCollapsed();
		}
	}

	/**
	 * Given a binary operator, compute which taint will be preserved. Safe ops don't preserve
	 * any taint, whereas unsafe ops will preserve all taints. The taint of a binop is basically
	 * ( lhs_taint | rhs_taint ) & taint_mask
	 *
	 * @warning This method should avoid computing the taint of $lhs and $rhs, because it might be
	 * called in preorder, but it would trigger a postorder visit.
	 *
	 * @param Node $opNode
	 * @param Node|mixed $lhs Either a Node or a scalar
	 * @param Node|mixed $rhs Either a Node or a scalar
	 * @return int
	 */
	protected function getBinOpTaintMask( Node $opNode, $lhs, $rhs ): int {
		static $safeBinOps = [
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
			\ast\flags\BINARY_IS_GREATER_OR_EQUAL,
			\ast\flags\BINARY_SHIFT_LEFT,
			\ast\flags\BINARY_SHIFT_RIGHT,
			\ast\flags\BINARY_SPACESHIP,
		];

		// This list is mostly used for debugging purposes
		static $knownUnsafeOps = [
			\ast\flags\BINARY_ADD,
			\ast\flags\BINARY_CONCAT,
			\ast\flags\BINARY_COALESCE,
			// The result of bitwise ops can be a string, so we err on the side of caution.
			\ast\flags\BINARY_BITWISE_AND,
			\ast\flags\BINARY_BITWISE_OR,
			\ast\flags\BINARY_BITWISE_XOR,
		];

		if ( in_array( $opNode->flags, $safeBinOps, true ) ) {
			return SecurityCheckPlugin::NO_TAINT;
		}
		if (
			$opNode->flags === \ast\flags\BINARY_ADD &&
			( !$this->nodeCanBeArray( $lhs ) || !$this->nodeCanBeArray( $rhs ) )
		) {
			// Array addition is the only way `+` can preserve taintedness; if at least one operand
			// is definitely NOT an array, then the result will be an integer, or a fatal error will
			// occurr (depending on the other operand). Note that if we cannot be 100% sure that the
			// node cannot be an array (e.g. if it has mixed type), we err on the side of caution and
			// consider it potentially tainted.
			return SecurityCheckPlugin::NO_TAINT;
		}

		if ( !in_array( $opNode->flags, $knownUnsafeOps, true ) ) {
			$this->debug(
				__METHOD__,
				'Unhandled binop ' . Debug::astFlagDescription( $opNode->flags, $opNode->kind )
			);
		}

		return SecurityCheckPlugin::ALL_TAINT_FLAGS;
	}

	/**
	 * Get the possible UnionType of a node, without emitting issues.
	 *
	 * @param Node $node
	 * @return UnionType|null
	 */
	protected function getNodeType( Node $node ): ?UnionType {
		// Don't emit issues, as this method might be called e.g. on a LHS (see T249647).
		// FIXME Improve this. Is it still necessary now that we cache taintedness?
		$catchIssueException = false;
		// And since we don't emit issues, use a cloned context so phan won't cache any union type. In particular,
		// in the event of possibly-undefined union types, the issue about a variable being possibly undeclared would
		// get lost, because we don't emit it, and phan will cache the union type without the undefined bit.
		$ctx = clone $this->context;
		try {
			return UnionTypeVisitor::unionTypeFromNode(
				$this->code_base,
				$ctx,
				$node,
				$catchIssueException
			);
		} catch ( IssueException $e ) {
			$this->debug( __METHOD__, "Got error " . $this->getDebugInfo( $e ) );
			return null;
		}
	}

	/**
	 * Given a Node, is it an array? (And definitely not a string)
	 *
	 * @param Node|mixed $node A node object or simple value from AST tree
	 * @return bool Is it an array?
	 */
	protected function nodeIsArray( $node ): bool {
		if ( !( $node instanceof Node ) ) {
			// simple literal
			return false;
		}
		if ( $node->kind === \ast\AST_ARRAY ) {
			// Exit early in the simple case.
			return true;
		}
		$type = $this->getNodeType( $node );
		return $type && $type->hasArrayLike( $this->code_base ) &&
			!$type->hasMixedOrNonEmptyMixedType() && !$type->hasStringType();
	}

	/**
	 * Can $node potentially be an array?
	 *
	 * @param Node|mixed $node
	 * @return bool
	 */
	protected function nodeCanBeArray( $node ): bool {
		if ( !( $node instanceof Node ) ) {
			return is_array( $node );
		}
		$type = $this->getNodeType( $node );
		if ( !$type ) {
			return true;
		}
		$type = $type->getRealUnionType();
		return $type->hasArrayLike( $this->code_base ) || $type->hasMixedOrNonEmptyMixedType() || $type->isEmpty();
	}

	/**
	 * Given a Node, is it a string?
	 *
	 * @todo Unclear if this should return true for things that can
	 *   autocast to a string (e.g. ints)
	 * @param Node|mixed $node A node object or simple value from AST tree
	 * @return bool Is it a string?
	 */
	protected function nodeCanBeString( $node ): bool {
		if ( !( $node instanceof Node ) ) {
			// simple literal
			return is_string( $node );
		}
		$type = $this->getNodeType( $node );
		// @todo Should having mixed type result in returning false here?
		return $type && $type->hasStringType();
	}

	/**
	 * @param TypedElementInterface $el
	 * @param bool $definitely Whether $el is *definitely* numkey, not just possibly
	 * @return bool
	 */
	protected function elementCanBeNumkey( TypedElementInterface $el, bool $definitely ): bool {
		$type = $el->getUnionType()->getRealUnionType();
		if ( $type->hasMixedOrNonEmptyMixedType() || $type->isEmpty() ) {
			return !$definitely;
		}
		if ( !$type->hasArray() ) {
			return false;
		}

		$keyTypes = GenericArrayType::keyUnionTypeFromTypeSetStrict( $el->getUnionType()->getRealTypeSet() );
		// NOTE: This might lead to false positives if the array has mixed keys, but since we're talking about
		// SQLi, we prefer false positives. Also, the mixed keys case isn't fully handled, see backpropagateArgTaint
		return $definitely
			? $keyTypes === GenericArrayType::KEY_INT
			: ( $keyTypes & GenericArrayType::KEY_INT ) !== 0;
	}

	/**
	 * Given a Node that is used as array key, can the key be integer?
	 * Floats are not considered ints here.
	 * Note: this method cannot be 100% accurate. First, we don't use the real type, so we may have a false positive
	 * if e.g. a parameter is annotated as string but the argument is an int. Second, even if something has a real type
	 * and is not an integer, it could be a string that gets autocast to an integer.
	 *
	 * @param Node|mixed $node A node object or simple value from AST tree
	 * @return bool Is it an int?
	 * @fixme A lot of duplication with other similar methods...
	 */
	protected function nodeCanBeIntKey( $node ): bool {
		if ( !( $node instanceof Node ) ) {
			// simple number; make sure to include float here for PHP 8.1 compat: T307504
			if ( is_int( $node ) || is_float( $node ) ) {
				return true;
			}
			// Strings that are canonical representation of numbers are coerced to int keys.
			$testArr = [ $node => 'foo' ];
			$key = key( $testArr );
			return is_int( $key );
		}
		$type = $this->getNodeType( $node );
		if ( !$type ) {
			return true;
		}
		return $type->hasIntType() || $type->hasMixedOrNonEmptyMixedType() || $type->isEmpty();
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
	 * escaped, as all the different instances are treated the same.
	 *
	 * It needs the return statement to be trivial (e.g. return $this->foo;). It
	 * will not work even with something as simple as $a = $this->foo; return $a;
	 * However, this code path will only happen if the plugin encounters the
	 * code to output the value prior to reading the code that sets the value to
	 * something evil. The other code path where the set happens first is much
	 * more robust and hopefully the more common code path.
	 *
	 * @param FunctionInterface $func The function/method. Must use Analyzable trait
	 * @return TypedElementInterface[] An array of phan objects
	 */
	public function getReturnObjsOfFunc( FunctionInterface $func ): array {
		$retObjs = self::getRetObjs( $func );
		if ( $retObjs === null ) {
			// We still have to see the function. Analyze it now.
			$this->analyzeFunc( $func );
			$retObjs = self::getRetObjs( $func );
			if ( $retObjs === null ) {
				// If it still doesn't exist, perhaps we reached the recursion limit, or it may be a recursive
				// function, or a kind of function that we can't handle.
				return [];
			}
		}

		// Note that if a function is recursively calling itself, this list might be incomplete.
		// This could be remediated with another dynamic property (e.g. retObjsCollected), initialized
		// inside visitMethod in preorder, and set to true inside visitMethod in postorder.
		// It would be pointless, though, as returning a partial list is better than returning no list.
		return array_filter(
			$retObjs,
			static function ( TypedElementInterface $el ): bool {
				return !( $el instanceof Variable );
			}
		);
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
	): bool {
		return $child->asType()->asExpandedTypes( $codeBase )->hasType( $parent->asType() );
	}
}
