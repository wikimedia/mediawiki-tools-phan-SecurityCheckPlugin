<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

/**
 * Value object used to store taintedness. This should always be used to manipulate taintedness values,
 * instead of directly using taint constants directly (except for comparisons etc.).
 *
 * All methods in this class take taint constants, and some of them can also take Taintedness object.
 * This is just for convenience, and to avoid using ->get() at the call site.
 *
 * Note that this class should be used as copy-on-write (like phan's UnionType), so in-place
 * manipulation should never be done on phan objects.
 */
class Taintedness {
	/** @var int Combination of the class constants */
	private $flags;

	/**
	 * @param int $val One of the class constants
	 */
	public function __construct( int $val ) {
		$this->flags = $val;
	}

	// Common creation shortcuts

	/**
	 * @return self
	 */
	public static function newSafe() : self {
		return new self( SecurityCheckPlugin::NO_TAINT );
	}

	/**
	 * @return self
	 */
	public static function newInapplicable() : self {
		return new self( SecurityCheckPlugin::INAPPLICABLE_TAINT );
	}

	/**
	 * @return self
	 */
	public static function newUnknown() : self {
		return new self( SecurityCheckPlugin::UNKNOWN_TAINT );
	}

	/**
	 * @return self
	 */
	public static function newTainted() : self {
		return new self( SecurityCheckPlugin::YES_TAINT );
	}

	/**
	 * @note This should almost NEVER be used! Use accessors as much as possible!
	 *
	 * @return int
	 */
	public function get() : int {
		return $this->flags;
	}

	// Value manipulation

	/**
	 * Add the given taint to this object, *without* creating a clone
	 * @see Taintedness::with() if you need a clone
	 *
	 * @param self|int $taint
	 */
	public function add( $taint ) : void {
		$addedTaint = $taint instanceof self ? $taint->flags : $taint;
		// TODO: Should this clear UNKNOWN_TAINT if its present
		// only in one of the args?
		$this->flags |= $addedTaint;
	}

	/**
	 * Returns a copy of this object, with the bits in $other added.
	 * @see Taintedness::add() for the in-place version
	 *
	 * @param self|int $other
	 * @return $this
	 */
	public function with( $other ) : self {
		$ret = clone $this;
		$taint = $other instanceof self ? $other->get() : $other;
		$ret->add( $taint );
		return $ret;
	}

	/**
	 * Remove the given taint from this object, *without* creating a clone
	 * @see Taintedness::without() if you need a clone
	 *
	 * @param self|int $other
	 */
	public function remove( $other ) : void {
		$taint = $other instanceof self ? $other->flags : $other;
		$this->keepOnly( ~$taint );
	}

	/**
	 * Returns a copy of this object, with the bits in $other removed.
	 * @see Taintedness::remove() for the in-place version
	 *
	 * @param self|int $other
	 * @return $this
	 */
	public function without( $other ) : self {
		$ret = clone $this;
		$taint = $other instanceof self ? $other->get() : $other;
		$ret->remove( $taint );
		return $ret;
	}

	/**
	 * Check whether this object has the given flag.
	 *
	 * @param int $taint
	 * @param bool $all For composite flags, this determines whether we should check for
	 *  a subset of $taint, or all of the flags in $taint should also be in $this->taintedness.
	 * @return bool
	 */
	public function has( int $taint, bool $all = false ) : bool {
		return $all
			? ( $this->get() & $taint ) === $taint
			: ( $this->get() & $taint ) !== SecurityCheckPlugin::NO_TAINT;
	}

	/**
	 * Check whether this object does NOT have any of the given flags.
	 *
	 * @param int $taint
	 * @return bool
	 */
	public function lacks( int $taint ) : bool {
		return ( $this->get() & $taint ) === SecurityCheckPlugin::NO_TAINT;
	}

	/**
	 * Keep only the taint in $taint, without creating a copy.
	 * @see Taintedness::withOnly if you need a clone
	 *
	 * @param self|int $other
	 */
	public function keepOnly( $other ) : void {
		$taint = $other instanceof self ? $other->flags : $other;
		$this->flags &= $taint;
	}

	/**
	 * Returns a copy of this object, with only the taint in $taint kept.
	 * @see Taintedness::keepOnly() for the in-place version
	 *
	 * @param self|int $other
	 * @return $this
	 */
	public function withOnly( $other ) : self {
		$ret = clone $this;
		$taint = $other instanceof self ? $other->get() : $other;
		$ret->keepOnly( $taint );
		return $ret;
	}

	// Conversion/checks shortcuts

	/**
	 * Does the taint have one of EXEC flags set
	 *
	 * @return bool If the variable has any exec taint
	 */
	public function isExecTaint() : bool {
		return $this->has( SecurityCheckPlugin::ALL_EXEC_TAINT );
	}

	/**
	 * Are any of the positive (i.e HTML_TAINT) taint flags set
	 *
	 * @return bool If the variable has known (non-execute taint)
	 */
	public function isAllTaint() : bool {
		return $this->has( SecurityCheckPlugin::ALL_TAINT );
	}

	/**
	 * Check whether this object has no taintedness.
	 *
	 * @return bool
	 */
	public function isSafe() : bool {
		return $this->get() === SecurityCheckPlugin::NO_TAINT;
	}

	/**
	 * Convert exec to yes taint. Special flags like UNKNOWN or INAPPLICABLE are discarded.
	 * Any YES flags are also discarded. Note that this returns a copy of the
	 * original object.
	 *
	 * @return self
	 */
	public function asExecToYesTaint() : self {
		$ret = clone $this;
		$ret->flags = ( $ret->flags & SecurityCheckPlugin::ALL_EXEC_TAINT ) >> 1;
		return $ret;
	}

	/**
	 * Convert the yes taint bits to corresponding exec taint bits.
	 * Any UNKNOWN_TAINT or INAPPLICABLE_TAINT is discarded. Note that this returns a copy of the
	 * original object.
	 *
	 * @return self
	 */
	public function asYesToExecTaint() : self {
		$ret = clone $this;
		$ret->flags = ( $ret->flags & SecurityCheckPlugin::ALL_TAINT ) << 1;
		return $ret;
	}

	/**
	 * This is to ease debugging etc.
	 * @return string
	 */
	public function __toString() : string {
		return (string)$this->get();
	}
}
