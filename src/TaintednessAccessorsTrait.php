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
	protected static function getTaintednessRaw( TypedElementInterface $element ) : ?Taintedness {
		return property_exists( $element, 'taintedness' ) ? clone $element->taintedness : null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param Taintedness $taintedness
	 */
	protected static function setTaintednessRaw( TypedElementInterface $element, Taintedness $taintedness ) : void {
		$element->taintedness = $taintedness;
		if ( $element instanceof PassByReferenceVariable ) {
			self::setTaintednessRef( $element->getElement(), $taintedness );
		}
	}

	/**
	 * @param TypedElementInterface $element
	 * @return array|null
	 * @phan-return list<array{0:Taintedness,1:string}>|null
	 */
	protected static function getCausedByRaw( TypedElementInterface $element ) : ?array {
		return $element->taintedOriginalError ?? null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param array $lines
	 * @phan-param list<array{0:Taintedness,1:string}> $lines
	 */
	protected static function setCausedByRaw( TypedElementInterface $element, array $lines ) : void {
		$element->taintedOriginalError = $lines;
		if ( $element instanceof PassByReferenceVariable ) {
			self::setCausedByRaw(
				$element->getElement(),
				TaintednessBaseVisitor::mergeCausedByLines(
					self::getCausedByRaw( $element->getElement() ) ?? [],
					$lines
				)
			);
		}
	}

	/**
	 * @param TypedElementInterface $element
	 * @return array|null
	 * @phan-return array<int,list<array{0:Taintedness,1:string}>>|null
	 */
	protected static function getAllCausedByArgRaw( TypedElementInterface $element ) : ?array {
		return $element->taintedOriginalErrorByArg ?? null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param int $arg
	 * @return array|null
	 * @phan-return list<array{0:Taintedness,1:string}>|null
	 */
	protected static function getCausedByArgRaw( TypedElementInterface $element, int $arg ) : ?array {
		return $element->taintedOriginalErrorByArg[$arg] ?? null;
	}

	/**
	 * @param TypedElementInterface $element
	 */
	protected static function initCausedByArgRaw( TypedElementInterface $element ) : void {
		$element->taintedOriginalErrorByArg = [];
	}

	/**
	 * @param TypedElementInterface $element
	 * @param int $arg
	 * @param array $lines
	 * @phan-param list<array{0:Taintedness,1:string}> $lines
	 */
	protected static function setCausedByArgRaw( TypedElementInterface $element, int $arg, array $lines ) : void {
		$element->taintedOriginalErrorByArg[$arg] = $lines;
	}

	/**
	 * @param TypedElementInterface $element
	 * @return Set|null
	 */
	protected static function getMethodLinks( TypedElementInterface $element ) : ?Set {
		return property_exists( $element, 'taintedMethodLinks' ) ? clone $element->taintedMethodLinks : null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param Set $links
	 */
	protected static function setMethodLinks( TypedElementInterface $element, Set $links ) : void {
		$element->taintedMethodLinks = $links;
	}

	/**
	 * @param TypedElementInterface $element
	 * @return Set[]|null
	 */
	protected static function getAllVarLinks( TypedElementInterface $element ) : ?array {
		return $element->taintedVarLinks ?? null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param int $index
	 * @return Set|null
	 */
	protected static function getVarLinks( TypedElementInterface $element, int $index ) : ?Set {
		return $element->taintedVarLinks[$index] ?? null;
	}

	/**
	 * @param TypedElementInterface $element
	 */
	protected static function initVarLinks( TypedElementInterface $element ) : void {
		$element->taintedVarLinks = [];
	}

	/**
	 * @param TypedElementInterface $element
	 * @param int $index
	 * @param Set $links
	 */
	protected static function setVarLinks( TypedElementInterface $element, int $index, Set $links ) : void {
		$element->taintedVarLinks[$index] = $links;
	}

	/**
	 * @param TypedElementInterface $element
	 * @return Taintedness|null
	 */
	protected static function getTaintednessRef( TypedElementInterface $element ) : ?Taintedness {
		return property_exists( $element, 'taintednessRef' ) ? clone $element->taintednessRef : null;
	}

	/**
	 * @param TypedElementInterface $element
	 * @param Taintedness $taintedness
	 */
	protected static function setTaintednessRef( TypedElementInterface $element, Taintedness $taintedness ) : void {
		$element->taintednessRef = $taintedness;
	}

	/**
	 * @param TypedElementInterface $element
	 */
	protected static function clearTaintednessRef( TypedElementInterface $element ) : void {
		unset( $element->taintednessRef );
	}

	/**
	 * Clears any previous error on the given element.
	 *
	 * @param TypedElementInterface $elem
	 */
	protected static function clearTaintError( TypedElementInterface $elem ) : void {
		if ( property_exists( $elem, 'taintedOriginalError' ) ) {
			self::setCausedByRaw( $elem, [] );
		}
	}

	/**
	 * Clears any taintedness links on this object
	 *
	 * @param TypedElementInterface $elem
	 */
	protected static function clearTaintLinks( TypedElementInterface $elem ) : void {
		unset( $elem->taintedMethodLinks, $elem->taintedVarLinks );
	}

	/**
	 * Get a copy of $func's taint, or null if not set.
	 *
	 * @param FunctionInterface $func
	 * @return FunctionTaintedness|null
	 */
	protected static function getFuncTaint( FunctionInterface $func ) : ?FunctionTaintedness {
		return isset( $func->funcTaint ) ? clone $func->funcTaint : null;
	}

	/**
	 * @param FunctionInterface $func
	 * @param FunctionTaintedness $funcTaint
	 */
	protected static function doSetFuncTaint( FunctionInterface $func, FunctionTaintedness $funcTaint ) : void {
		$func->funcTaint = $funcTaint;
	}

	/**
	 * @param FunctionInterface $func
	 * @return TypedElementInterface[]|null
	 */
	protected static function getRetObjs( FunctionInterface $func ) : ?array {
		return $func->retObjs ?? null;
	}

	/**
	 * @param FunctionInterface $func
	 * @param TypedElementInterface[] $retObjs
	 * @suppress PhanUnreferencedProtectedMethod Used in TaintednessVisitor
	 */
	protected static function setRetObjs( FunctionInterface $func, array $retObjs ) : void {
		$func->retObjs = $retObjs;
	}

}
