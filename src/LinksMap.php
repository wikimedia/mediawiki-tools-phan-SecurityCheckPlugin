<?php declare( strict_types=1 );

// @phan-file-suppress PhanParamSignatureMismatchInternal, PhanParamSignaturePHPDocMismatchParamType
// @phan-file-suppress PhanParamSignaturePHPDocMismatchTooManyRequiredParameters

namespace SecurityCheckPlugin;

use Phan\Language\Element\FunctionInterface;
use SplObjectStorage;

/**
 * Convenience class for better type inference.
 *
 * @extends SplObjectStorage<FunctionInterface,SingleMethodLinks>
 * @method SingleMethodLinks offsetGet( FunctionInterface $object )
 * @method offsetSet( FunctionInterface $object, SingleMethodLinks $data )
 * @method void attach(FunctionInterface $object, SingleMethodLinks $data)
 * @method FunctionInterface current()
 */
class LinksMap extends SplObjectStorage {
	public function mergeWith( self $other ): void {
		foreach ( $other as $method ) {
			if ( $this->contains( $method ) ) {
				$this[$method] = $this[$method]->asMergedWith( $other[$method] );
			} else {
				$this->attach( $method, $other[$method] );
			}
		}
	}

	public function asMergedWith( self $other ): self {
		$ret = clone $this;
		$ret->mergeWith( $other );
		return $ret;
	}

	public function withoutShape( self $other ): self {
		$ret = clone $this;
		foreach ( $other as $func ) {
			if ( $ret->contains( $func ) ) {
				$newFuncData = $ret[$func]->withoutShape( $other[$func] );
				if ( $newFuncData->getParams() ) {
					$ret[$func] = $newFuncData;
				} else {
					unset( $ret[$func] );
				}
			}
		}
		return $ret;
	}

	public function asAllMovedToKeys(): self {
		$ret = new self;
		foreach ( $this as $func ) {
			$ret[$func] = $this[$func]->asAllParamsMovedToKeys();
		}
		return $ret;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function __toString(): string {
		$children = [];
		foreach ( $this as $func ) {
			$children[] = $func->getFQSEN()->__toString() . ': ' . $this[$func]->__toString();
		}
		return '{ ' . implode( ',', $children ) . ' }';
	}
}
