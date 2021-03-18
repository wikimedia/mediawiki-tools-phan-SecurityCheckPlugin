<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use ast\Node;

/**
 * Tree-like object of possible offset combinations for partial links to a parameter
 */
class ParamLinksOffsets {
	/** @var bool */
	private $own = true;

	/** @var self[] */
	private $dims = [];

	/** @var self|null */
	private $unknown;

	/**
	 * @param self $other
	 */
	public function mergeWith( self $other ) : void {
		$this->own = $this->own || $other->own;
		if ( $other->unknown && !$this->unknown ) {
			$this->unknown = $other->unknown;
		} elseif ( $other->unknown ) {
			$this->unknown->mergeWith( $other->unknown );
		}
		foreach ( $other->dims as $key => $val ) {
			if ( !isset( $this->dims[$key] ) ) {
				$this->dims[$key] = clone $val;
			} else {
				$this->dims[$key]->mergeWith( $val );
			}
		}
	}

	/**
	 * Pushes $offsets to all leafs.
	 * @param Node|string|int|null $offset
	 */
	public function pushOffset( $offset ) : void {
		foreach ( $this->dims as $val ) {
			$val->pushOffset( $offset );
		}
		if ( $this->unknown ) {
			$this->unknown->pushOffset( $offset );
		}
		if ( $this->own ) {
			$this->own = false;
			if ( is_scalar( $offset ) && !isset( $this->dims[$offset] ) ) {
				$this->dims[$offset] = new self;
			} elseif ( !is_scalar( $offset ) && !$this->unknown ) {
				$this->unknown = new self;
			}
		}
	}

	public function __clone() {
		foreach ( $this->dims as $k => $v ) {
			$this->dims[$k] = clone $v;
		}
		if ( $this->unknown ) {
			$this->unknown = clone $this->unknown;
		}
	}

	/**
	 * Should only be used in Taintedness::asMovedAtRelevantOffsets
	 * @return bool
	 */
	public function getOwn() : bool {
		return $this->own;
	}

	/**
	 * Should only be used in Taintedness::asMovedAtRelevantOffsets
	 * @return ParamLinksOffsets[]
	 */
	public function getDims() : array {
		return $this->dims;
	}

	/**
	 * Should only be used in Taintedness::asMovedAtRelevantOffsets
	 * @return ParamLinksOffsets|null
	 */
	public function getUnknown() : ?ParamLinksOffsets {
		return $this->unknown;
	}

	/**
	 * @return string
	 */
	public function __toString() : string {
		$ret = '(own): ' . ( $this->own ? 'Y' : 'N' );
		if ( $this->dims || $this->unknown ) {
			$ret .= ', dims: [';
			$dimBits = [];
			foreach ( $this->dims as $k => $val ) {
				$dimBits[] = "$k => " . $val->__toString();
			}
			if ( $this->unknown ) {
				$dimBits[] = '(unknown): ' . $this->unknown->__toString();
			}
			$ret .= implode( ', ', $dimBits ) . ']';
		}
		return $ret;
	}
}
