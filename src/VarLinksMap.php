<?php declare( strict_types=1 );

// @phan-file-suppress PhanParamSignatureMismatchInternal, PhanParamSignaturePHPDocMismatchParamType
// @phan-file-suppress PhanParamSignaturePHPDocMismatchTooManyRequiredParameters

namespace SecurityCheckPlugin;

use Phan\Language\Element\TypedElementInterface;
use SplObjectStorage;

/**
 * Convenience class for better type inference.
 *
 * @extends SplObjectStorage<TypedElementInterface,PreservedTaintedness>
 * @method PreservedTaintedness offsetGet( TypedElementInterface $object )
 * @method offsetSet( TypedElementInterface $object, PreservedTaintedness $data )
 * @method void attach(TypedElementInterface $object, PreservedTaintedness $data)
 * @method TypedElementInterface current()
 */
class VarLinksMap extends SplObjectStorage {
	/**
	 * @codeCoverageIgnore
	 */
	public function __toString(): string {
		$children = [];
		foreach ( $this as $var ) {
			$children[] = $var->getName() . ': ' . $this[$var]->toShortString();
		}
		return '[' . implode( ',', $children ) . ']';
	}

	public function __clone() {
		foreach ( $this as $var ) {
			$this[$var] = clone $this[$var];
		}
	}
}
