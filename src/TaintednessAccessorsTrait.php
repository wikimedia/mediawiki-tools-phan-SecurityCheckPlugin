<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\PassByReferenceVariable;
use Phan\Language\Element\TypedElementInterface;
use Phan\Library\Set;

/**
 * Accessors to read and write taintedness props stored inside phan objects. This trait exists to avoid duplicating
 * dynamic property names, to have better type inference, to enable phan checks for undeclared props on the other
 * files, to keep track of props usage etc.
 * @phan-file-suppress PhanUndeclaredProperty
 */
trait TaintednessAccessorsTrait {
	/**
	 * @param TypedElementInterface $element
	 * @return Taintedness|null
	 */
	protected static function getTaintednessRaw( TypedElementInterface $element ): ?Taintedness {
		return $element->taintedness ?? null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @return Taintedness|null
	 * @suppress PhanUnreferencedProtectedMethod False positive
	 */
	protected static function getTaintednessRawClone( TypedElementInterface $element ): ?Taintedness {
		// Performance: use isset(), not property_exists()
		return isset( $element->taintedness ) ? clone $element->taintedness : null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param Taintedness $taintedness
	 */
	protected static function setTaintednessRaw( TypedElementInterface $element, Taintedness $taintedness ): void {
		$element->taintedness = $taintedness;
		if ( $element instanceof PassByReferenceVariable ) {
			self::setTaintednessRef( $element->getElement(), $taintedness );
		}
	}

	/**
	 * @param TypedElementInterface $element
	 * @return CausedByLines|null
	 */
	protected static function getCausedByRaw( TypedElementInterface $element ): ?CausedByLines {
		return $element->taintedOriginalError ?? null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @return CausedByLines
	 */
	protected static function getCausedByRawCloneOrEmpty( TypedElementInterface $element ): CausedByLines {
		return isset( $element->taintedOriginalError ) ? clone $element->taintedOriginalError : new CausedByLines();
	}

	/**
	 * @param TypedElementInterface $element
	 * @param CausedByLines $lines
	 */
	protected static function setCausedByRaw( TypedElementInterface $element, CausedByLines $lines ): void {
		$element->taintedOriginalError = $lines;
		if ( $element instanceof PassByReferenceVariable ) {
			$curCausedBy = self::getCausedByRaw( $element->getElement() );
			$newCausedBy = $curCausedBy ? $curCausedBy->asMergedWith( $lines ) : $lines;
			self::setCausedByRaw( $element->getElement(), $newCausedBy );
		}
	}

	/**
	 * @param TypedElementInterface $element
	 */
	protected static function ensureCausedByRawExists( TypedElementInterface $element ): void {
		$element->taintedOriginalError = $element->taintedOriginalError ?? new CausedByLines();
		if ( $element instanceof PassByReferenceVariable ) {
			$realElement = $element->getElement();
			$realElement->taintedOriginalError = $realElement->taintedOriginalError ?? new CausedByLines();
		}
	}

	/**
	 * @param TypedElementInterface $element
	 * @param int $arg
	 */
	protected static function ensureCausedByArgRawExists( TypedElementInterface $element, int $arg ): void {
		$element->taintedOriginalErrorByArg = $element->taintedOriginalErrorByArg ?? [];
		$element->taintedOriginalErrorByArg[$arg] = $element->taintedOriginalErrorByArg[$arg] ?? new CausedByLines();
	}

	/**
	 * @param TypedElementInterface $element
	 * @return CausedByLines[]|null
	 */
	protected static function getAllCausedByArgRaw( TypedElementInterface $element ): ?array {
		return $element->taintedOriginalErrorByArg ?? null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param int $arg
	 * @return CausedByLines|null
	 */
	protected static function getCausedByArgRaw( TypedElementInterface $element, int $arg ): ?CausedByLines {
		return $element->taintedOriginalErrorByArg[$arg] ?? null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param int $arg
	 * @param CausedByLines $lines
	 */
	protected static function setCausedByArgRaw(
		TypedElementInterface $element,
		int $arg,
		CausedByLines $lines
	): void {
		$element->taintedOriginalErrorByArg[$arg] = $lines;
	}

	/**
	 * @note This doesn't return a clone
	 *
	 * @param TypedElementInterface $element
	 * @return MethodLinks|null
	 */
	protected static function getMethodLinks( TypedElementInterface $element ): ?MethodLinks {
		return $element->taintedMethodLinks ?? null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @return MethodLinks
	 */
	protected static function getMethodLinksCloneOrEmpty( TypedElementInterface $element ): MethodLinks {
		// Performance: use isset(), not property_exists()
		return isset( $element->taintedMethodLinks ) ? clone $element->taintedMethodLinks : new MethodLinks();
	}

	/**
	 * @param TypedElementInterface $element
	 * @param MethodLinks $links
	 */
	protected static function setMethodLinks( TypedElementInterface $element, MethodLinks $links ): void {
		$element->taintedMethodLinks = $links;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param int $index
	 * @return Set|null
	 */
	protected static function getVarLinks( TypedElementInterface $element, int $index ): ?Set {
		return $element->taintedVarLinks[$index] ?? null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param int $arg
	 */
	protected static function ensureVarLinksForArgExist( TypedElementInterface $element, int $arg ): void {
		$element->taintedVarLinks = $element->taintedVarLinks ?? [];
		$element->taintedVarLinks[$arg] = $element->taintedVarLinks[$arg] ?? new Set;
	}

	/**
	 * @param TypedElementInterface $element
	 * @return Taintedness|null
	 */
	protected static function getTaintednessRef( TypedElementInterface $element ): ?Taintedness {
		// Performance: use isset(), not property_exists()
		return isset( $element->taintednessRef ) ? clone $element->taintednessRef : null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param Taintedness $taintedness
	 */
	protected static function setTaintednessRef( TypedElementInterface $element, Taintedness $taintedness ): void {
		$element->taintednessRef = $taintedness;
	}

	/**
	 * @param TypedElementInterface $element
	 */
	protected static function clearTaintednessRef( TypedElementInterface $element ): void {
		unset( $element->taintednessRef );
	}

	/**
	 * Clears any previous error on the given element.
	 *
	 * @param TypedElementInterface $elem
	 */
	protected static function clearTaintError( TypedElementInterface $elem ): void {
		unset( $elem->taintedOriginalError );
	}

	/**
	 * Clears any taintedness links on this object
	 *
	 * @param TypedElementInterface $elem
	 */
	protected static function clearTaintLinks( TypedElementInterface $elem ): void {
		unset( $elem->taintedMethodLinks, $elem->taintedVarLinks );
	}

	/**
	 * Get $func's taint, or null if not set. NOTE: This doesn't create a clone.
	 *
	 * @param FunctionInterface $func
	 * @return FunctionTaintedness|null
	 */
	protected static function getFuncTaint( FunctionInterface $func ): ?FunctionTaintedness {
		return $func->funcTaint ?? null;
	}

	/**
	 * @param FunctionInterface $func
	 * @param FunctionTaintedness $funcTaint
	 */
	protected static function doSetFuncTaint( FunctionInterface $func, FunctionTaintedness $funcTaint ): void {
		$func->funcTaint = $funcTaint;
	}

	/**
	 * @param FunctionInterface $func
	 * @return TypedElementInterface[]|null
	 */
	protected static function getRetObjs( FunctionInterface $func ): ?array {
		return $func->retObjs ?? null;
	}

	/**
	 * @param FunctionInterface $func
	 * @param TypedElementInterface[] $retObjs
	 * @suppress PhanUnreferencedProtectedMethod Used in TaintednessVisitor
	 */
	protected static function addRetObjs( FunctionInterface $func, array $retObjs ): void {
		$func->retObjs = array_merge( $func->retObjs ?? [], $retObjs );
	}

	/**
	 * @param FunctionInterface $func
	 */
	protected static function initRetObjs( FunctionInterface $func ): void {
		$func->retObjs = $func->retObjs ?? [];
	}

}
