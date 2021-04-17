<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use Phan\Language\Element\FunctionInterface;
use Phan\Library\Set;

/**
 * Convenience class for better type inference.
 *
 * @method SingleMethodLinks offsetGet( \Phan\Language\Element\FunctionInterface $object )
 * @method offsetSet( \Phan\Language\Element\FunctionInterface $object, SingleMethodLinks $data )
 * @method void attach(FunctionInterface $object, SingleMethodLinks $data)
 * @method FunctionInterface current()
 * @phan-file-suppress PhanParamSignatureMismatch,PhanParamSignaturePHPDocMismatchParamType
 * @phan-file-suppress PhanParamSignaturePHPDocMismatchTooManyRequiredParameters
 */
class LinksSet extends Set {
	/**
	 * Reimplementation of parent::union, which doesn't use `static`
	 * @param self $other
	 * @return self
	 */
	public function unionWith( self $other ) : self {
		$set = new self();
		$set->addAll( $this );
		$set->addAll( $other );
		return $set;
	}

	public function __toString() : string {
		$children = [];
		foreach ( $this as $func ) {
			$children[] = $func->getFQSEN()->__toString() . ': ' . $this[$func]->__toString();
		}
		return '[' . implode( ',', $children ) . ']';
	}

	public function __clone() {
		foreach ( $this as $func ) {
			$this[$func] = clone $this[$func];
		}
	}
}
