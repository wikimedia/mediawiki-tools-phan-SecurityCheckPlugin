<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use ast\Node;

/**
 * This class represents what taintedness is passed through (=preserved) by a function parameter
 */
class PreservedTaintedness {
	/** @var int Combination of the class constants */
	private $flags;

	/** @var ParamLinksOffsets */
	private $ownOffsets;

	/** @var self[] Taintedness for each possible array element */
	private $dimTaint = [];

	/** @var int Taintedness of the array keys */
	private $keysTaint = SecurityCheckPlugin::NO_TAINT;

	/**
	 * @var self|null Taintedness for array elements that we couldn't attribute to any key
	 */
	private $unknownDimsTaint;

	/**
	 * @param int $flags
	 * @param ParamLinksOffsets|null $offsets
	 */
	public function __construct( int $flags, ParamLinksOffsets $offsets = null ) {
		$this->flags = $flags;
		$this->ownOffsets = $offsets ?? new ParamLinksOffsets();
	}

	/**
	 * @return self
	 */
	public static function newEmpty() : self {
		return new self( SecurityCheckPlugin::NO_TAINT );
	}

	/**
	 * @return Taintedness
	 */
	public function asTaintedness() : Taintedness {
		$ret = new Taintedness( $this->flags );
		foreach ( $this->dimTaint as $k => $val ) {
			$ret->setOffsetTaintedness( $k, $val->asTaintedness() );
		}
		if ( $this->unknownDimsTaint ) {
			$ret->setOffsetTaintedness( null, $this->unknownDimsTaint->asTaintedness() );
		}
		return $ret;
	}

	/**
	 * Set the taintedness for $offset to $value, in place
	 *
	 * @param Node|mixed $offset Node or a scalar value, already resolved
	 * @param self $value
	 */
	public function setOffsetTaintedness( $offset, self $value ) : void {
		if ( is_scalar( $offset ) ) {
			$this->dimTaint[$offset] = $value;
		} else {
			$this->unknownDimsTaint = $this->unknownDimsTaint ?? self::newEmpty();
			$this->unknownDimsTaint->mergeWith( $value );
		}
	}

	/**
	 * @param self $other
	 */
	public function mergeWith( self $other ) : void {
		$this->flags |= $other->flags;
		$this->ownOffsets->mergeWith( $other->ownOffsets );
		$this->keysTaint |= $other->keysTaint;
		if ( $other->unknownDimsTaint && !$this->unknownDimsTaint ) {
			$this->unknownDimsTaint = $other->unknownDimsTaint;
		} elseif ( $other->unknownDimsTaint ) {
			$this->unknownDimsTaint->mergeWith( $other->unknownDimsTaint );
		}
		foreach ( $other->dimTaint as $key => $val ) {
			if ( !array_key_exists( $key, $this->dimTaint ) ) {
				$this->dimTaint[$key] = clone $val;
			} else {
				$this->dimTaint[$key]->mergeWith( $val );
			}
		}
	}

	/**
	 * @param self $other
	 * @return self
	 */
	public function asMergedWith( self $other ) : self {
		$ret = clone $this;
		$ret->mergeWith( $other );
		return $ret;
	}

	/**
	 * @param Taintedness $argTaint
	 * @return Taintedness
	 */
	public function asTaintednessForArgument( Taintedness $argTaint ) : Taintedness {
		if ( $this->flags & SecurityCheckPlugin::PRESERVE_TAINT ) {
			$ret = $this->ownOffsets->appliedToTaintedness( $argTaint );
		} elseif ( $this->flags ) {
			$ret = $argTaint->withOnly( $this->flags );
		} else {
			$ret = new Taintedness( SecurityCheckPlugin::NO_TAINT );
		}

		foreach ( $this->dimTaint as $k => $val ) {
			$ret->setOffsetTaintedness( $k, $val->asTaintednessForArgument( $argTaint ) );
		}
		if ( $this->unknownDimsTaint ) {
			$ret->setOffsetTaintedness( null, $this->unknownDimsTaint->asTaintednessForArgument( $argTaint ) );
		}
		if ( $this->keysTaint & SecurityCheckPlugin::PRESERVE_TAINT ) {
			$ret->addKeysTaintedness( $argTaint->get() );
		} elseif ( $this->keysTaint ) {
			$ret->addKeysTaintedness( $argTaint->get() & $this->keysTaint );
		}
		return $ret;
	}

	/**
	 * Get a stringified representation of this taintedness suitable for the debug annotation
	 *
	 * @return string
	 */
	public function toShortString() : string {
		$flags = SecurityCheckPlugin::taintToString( $this->flags );
		$ret = "{Own: $flags, Offsets: " . $this->ownOffsets->__toString();
		if ( $this->keysTaint ) {
			$ret .= '; Keys: ' . SecurityCheckPlugin::taintToString( $this->keysTaint );
		}
		$keyParts = [];
		if ( $this->dimTaint ) {
			foreach ( $this->dimTaint as $key => $taint ) {
				$keyParts[] = "$key => " . $taint->toShortString();
			}
		}
		if ( $this->unknownDimsTaint ) {
			$keyParts[] = 'Unknown => ' . $this->unknownDimsTaint->toShortString();
		}
		if ( $keyParts ) {
			$ret .= '; Elements: {' . implode( '; ', $keyParts ) . '}';
		}
		$ret .= '}';
		return $ret;
	}

	/**
	 * Make sure to clone member variables, too.
	 */
	public function __clone() {
		$this->ownOffsets = clone $this->ownOffsets;
		if ( $this->unknownDimsTaint ) {
			$this->unknownDimsTaint = clone $this->unknownDimsTaint;
		}
		foreach ( $this->dimTaint as $k => $v ) {
			$this->dimTaint[$k] = clone $v;
		}
	}

	/**
	 * @return string
	 */
	public function __toString() : string {
		return $this->toShortString();
	}
}