<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use ast\Node;

/**
 * Tree-like object of possible offset combinations for partial links to a parameter
 */
class ParamLinksOffsets {
	/** @var int What taint flags are preserved for this offset */
	private $ownFlags;

	/** @var self[] */
	private $dims = [];

	/** @var self|null */
	private $unknown;

	/** @var int */
	private $keysFlags = SecurityCheckPlugin::NO_TAINT;

	/**
	 * @param int $flags
	 */
	public function __construct( int $flags ) {
		$this->ownFlags = $flags;
	}

	/**
	 * @return self
	 */
	public static function newEmpty(): self {
		return new self( SecurityCheckPlugin::NO_TAINT );
	}

	/**
	 * @note This method should be avoided where possible
	 * @return int
	 */
	public function getFlags(): int {
		return $this->ownFlags;
	}

	/**
	 * @param self $other
	 */
	public function mergeWith( self $other ): void {
		$this->ownFlags |= $other->ownFlags;
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
		$this->keysFlags |= $other->keysFlags;
	}

	/**
	 * Pushes $offsets to all leaves.
	 * @param Node|string|int|null $offset
	 */
	public function pushOffset( $offset ): void {
		foreach ( $this->dims as $val ) {
			$val->pushOffset( $offset );
		}
		if ( $this->unknown ) {
			$this->unknown->pushOffset( $offset );
		}

		if ( $this->ownFlags === SecurityCheckPlugin::NO_TAINT ) {
			return;
		}

		$ownFlags = $this->ownFlags;
		$this->ownFlags = SecurityCheckPlugin::NO_TAINT;
		if ( is_scalar( $offset ) && !isset( $this->dims[$offset] ) ) {
			$this->dims[$offset] = new self( $ownFlags );
		} elseif ( !is_scalar( $offset ) && !$this->unknown ) {
			$this->unknown = new self( $ownFlags );
		}
	}

	/**
	 * @return self
	 */
	public function asMovedToKeys(): self {
		$ret = new self( SecurityCheckPlugin::NO_TAINT );

		foreach ( $this->dims as $k => $val ) {
			$ret->dims[$k] = $val->asMovedToKeys();
		}
		if ( $this->unknown ) {
			$ret->unknown = $this->unknown->asMovedToKeys();
		}

		$ret->keysFlags = $this->ownFlags;

		return $ret;
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
	 * @return ParamLinksOffsets[]
	 */
	public function getDims(): array {
		return $this->dims;
	}

	/**
	 * Should only be used in Taintedness::asMovedAtRelevantOffsetsForBackprop
	 * @return ParamLinksOffsets|null
	 */
	public function getUnknown(): ?ParamLinksOffsets {
		return $this->unknown;
	}

	/**
	 * Should only be used in Taintedness::asMovedAtRelevantOffsetsForBackprop
	 * @return int
	 */
	public function getKeysFlags(): int {
		return $this->keysFlags;
	}

	/**
	 * @param Taintedness $taintedness
	 * @return Taintedness
	 */
	public function appliedToTaintedness( Taintedness $taintedness ): Taintedness {
		if ( $this->ownFlags ) {
			$ret = $taintedness->withOnly( $this->ownFlags );
		} else {
			$ret = Taintedness::safeSingleton();
		}
		foreach ( $this->dims as $k => $val ) {
			$ret = $ret->asMergedWith(
				$val->appliedToTaintedness( $taintedness->getTaintednessForOffsetOrWhole( $k ) )
			);
		}
		if ( $this->unknown ) {
			$ret = $ret->asMergedWith(
				$this->unknown->appliedToTaintedness( $taintedness->getTaintednessForOffsetOrWhole( null ) )
			);
		}
		$ret = $ret->with( $taintedness->asKeyForForeach()->withOnly( $this->keysFlags )->get() );
		return $ret;
	}

	public function appliedToTaintednessForBackprop( Taintedness $taintedness ): Taintedness {
		if ( $this->ownFlags ) {
			$ret = $taintedness->withOnly( $this->ownFlags );
		} else {
			$ret = Taintedness::safeSingleton();
		}
		foreach ( $this->dims as $k => $val ) {
			$ret = $ret->withAddedOffsetTaintedness(
				$k,
				$val->appliedToTaintedness( $taintedness->getTaintednessForOffsetOrWhole( $k ) )
			);
		}
		if ( $this->unknown ) {
			$ret = $ret->withAddedOffsetTaintedness(
				null,
				$this->unknown->appliedToTaintedness( $taintedness->getTaintednessForOffsetOrWhole( null ) )
			);
		}
		$ret = $ret->withAddedKeysTaintedness( $taintedness->asKeyForForeach()->withOnly( $this->keysFlags )->get() );
		return $ret;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		$ret = '<(own): ' . SecurityCheckPlugin::taintToString( $this->ownFlags );

		if ( $this->keysFlags ) {
			$ret .= ', keys: ' . SecurityCheckPlugin::taintToString( $this->keysFlags );
		}

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
		return $ret . '>';
	}
}
