<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use ast\Node;

/**
 * Value object used to store taintedness. This should always be used to manipulate taintedness values,
 * instead of directly using taint constants directly (except for comparisons etc.).
 *
 * Note that this class should be used as copy-on-write (like phan's UnionType), so in-place
 * manipulation should never be done on phan objects.
 */
class Taintedness {
	/** @var int Combination of the class constants */
	private $flags;

	/** @var self[] Taintedness for each possible array element */
	private $dimTaint = [];

	/** @var int Taintedness of the array keys */
	private $keysTaint = SecurityCheckPlugin::NO_TAINT;

	/**
	 * @var self|null Taintedness for array elements that we couldn't attribute to any key
	 * @todo Can we store this under a bogus key in self::$dimTaint ?
	 */
	private $unknownDimsTaint;

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
	public static function newSafe(): self {
		return new self( SecurityCheckPlugin::NO_TAINT );
	}

	/**
	 * @return self
	 */
	public static function newInapplicable(): self {
		return new self( SecurityCheckPlugin::INAPPLICABLE_TAINT );
	}

	/**
	 * @return self
	 */
	public static function newUnknown(): self {
		return new self( SecurityCheckPlugin::UNKNOWN_TAINT );
	}

	/**
	 * @return self
	 */
	public static function newTainted(): self {
		return new self( SecurityCheckPlugin::YES_TAINT );
	}

	/**
	 * @param Taintedness[] $values
	 * @return self
	 */
	public static function newFromArray( array $values ): self {
		$ret = self::newSafe();
		foreach ( $values as $key => $value ) {
			assert( $value instanceof self );
			$ret->setOffsetTaintedness( $key, $value );
		}
		return $ret;
	}

	/**
	 * Get a numeric representation of the taint stored in this object. This includes own taint,
	 * array keys and whatnot.
	 * @note This should almost NEVER be used outside of this class! Use accessors as much as possible!
	 *
	 * @return int
	 */
	public function get(): int {
		$ret = $this->flags | $this->getAllKeysTaint() | $this->keysTaint;
		$ret = $this->unknownDimsTaint ? ( $ret | $this->unknownDimsTaint->get() ) : $ret;
		return $ret;
	}

	/**
	 * Get a flattened version of this object, with any taint from keys etc. collapsed into flags
	 * @return $this
	 */
	public function asCollapsed(): self {
		return new self( $this->get() );
	}

	/**
	 * Temporary method, should only be used in getRelevantLinksForTaintedness
	 * @return bool
	 */
	public function hasSomethingOutOfKnownDims(): bool {
		return $this->flags > 0 || $this->keysTaint > 0
			|| ( $this->unknownDimsTaint && !$this->unknownDimsTaint->isSafe() );
	}

	/**
	 * Temporary (?) method, should only be used in getRelevantLinksForTaintedness
	 * @return self[]
	 */
	public function getDimTaint(): array {
		return $this->dimTaint;
	}

	/**
	 * Recursively extract the taintedness from each key.
	 *
	 * @return int
	 */
	private function getAllKeysTaint(): int {
		$ret = SecurityCheckPlugin::NO_TAINT;
		foreach ( $this->dimTaint as $val ) {
			$ret |= $val->get();
		}
		return $ret;
	}

	// Value manipulation

	/**
	 * Add the given taint to this object's flags, *without* creating a clone
	 * @see Taintedness::with() if you need a clone
	 * @see Taintedness::mergeWith() if you want to preserve the whole shape
	 *
	 * @param int $taint
	 */
	public function add( int $taint ): void {
		// TODO: Should this clear UNKNOWN_TAINT if its present only in one of the args?
		$this->flags |= $taint;
	}

	/**
	 * @param Taintedness $other
	 */
	public function addObj( self $other ): void {
		$this->add( $other->get() );
	}

	/**
	 * Returns a copy of this object, with the bits in $other added to flags.
	 * @see Taintedness::add() for the in-place version
	 * @see Taintedness::asMergedWith() if you want to preserve the whole shape
	 *
	 * @param int $other
	 * @return $this
	 */
	public function with( int $other ): self {
		$ret = clone $this;
		$ret->add( $other );
		return $ret;
	}

	/**
	 * @param Taintedness $other
	 * @return $this
	 */
	public function withObj( self $other ): self {
		return $this->with( $other->get() );
	}

	/**
	 * Recursively remove the given taint from this object, *without* creating a clone
	 * @see Taintedness::without() if you need a clone
	 *
	 * @param int $other
	 */
	public function remove( int $other ): void {
		$this->keepOnly( ~$other );
	}

	/**
	 * Returns a copy of this object, with the bits in $other removed recursively.
	 * @see Taintedness::remove() for the in-place version
	 *
	 * @param int $other
	 * @return $this
	 */
	public function without( int $other ): self {
		$ret = clone $this;
		$ret->remove( $other );
		return $ret;
	}

	/**
	 * @todo Should this act on the shape?
	 * @param Taintedness $other
	 * @return $this
	 */
	public function withoutObj( self $other ): self {
		return $this->without( $other->get() );
	}

	/**
	 * Check whether this object has the given flag, recursively.
	 * @note If $taint has more than one flag, this will check for at least one, not all.
	 *
	 * @param int $taint
	 * @return bool
	 */
	public function has( int $taint ): bool {
		// Avoid using get() for performance
		if ( ( $this->flags & $taint ) !== SecurityCheckPlugin::NO_TAINT ) {
			return true;
		}
		if ( ( $this->keysTaint & $taint ) !== SecurityCheckPlugin::NO_TAINT ) {
			return true;
		}
		if ( $this->unknownDimsTaint && $this->unknownDimsTaint->has( $taint ) ) {
			return true;
		}
		foreach ( $this->dimTaint as $val ) {
			if ( $val->has( $taint ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Keep only the taint in $taint, recursively, preserving the shape and without creating a copy.
	 * @see Taintedness::withOnly if you need a clone
	 *
	 * @param int $taint
	 */
	public function keepOnly( int $taint ): void {
		$this->flags &= $taint;
		if ( $this->unknownDimsTaint ) {
			$this->unknownDimsTaint->keepOnly( $taint );
		}
		$this->keysTaint &= $taint;
		foreach ( $this->dimTaint as $val ) {
			$val->keepOnly( $taint );
		}
	}

	/**
	 * Returns a copy of this object, with only the taint in $taint kept (recursively, preserving the shape)
	 * @see Taintedness::keepOnly() for the in-place version
	 *
	 * @param int $other
	 * @return $this
	 */
	public function withOnly( int $other ): self {
		$ret = clone $this;
		$ret->keepOnly( $other );
		return $ret;
	}

	/**
	 * @param Taintedness $other
	 * @return $this
	 */
	public function withOnlyObj( self $other ): self {
		return $this->withOnly( $other->get() );
	}

	/**
	 * Intersect the taintedness of a value against that of a sink, to later determine whether the
	 * expression is safe. In case of function calls, $sink is the param taint and $value is the arg taint.
	 *
	 * @note The order of the arguments is important! This method preserves the shape of $sink, not $value.
	 *
	 * @note The order of the arguments is important! This method preserves the shape of $sink, not $value.
	 *
	 * @param Taintedness $sink
	 * @param Taintedness $value
	 * @return self
	 */
	public static function intersectForSink( self $sink, self $value ): self {
		$intersect = new self( SecurityCheckPlugin::NO_TAINT );
		// If the sink has non-zero flags, intersect it with the whole other side. This particularly preserves
		// the shape of $sink, discarding anything from $value if the sink has a NO_TAINT in that position.
		if ( $sink->flags ) {
			$intersect->flags = $sink->flags & $value->get();
		}
		if ( $sink->unknownDimsTaint ) {
			$intersect->unknownDimsTaint = self::intersectForSink(
				$sink->unknownDimsTaint,
				$value->asValueFirstLevel()
			);
		}
		$intersect->keysTaint = $sink->keysTaint & $value->keysTaint;
		foreach ( $sink->dimTaint as $key => $dTaint ) {
			$intersect->dimTaint[$key] = self::intersectForSink(
				$dTaint,
				$value->getTaintednessForOffsetOrWhole( $key )
			);
		}
		return $intersect;
	}

	/**
	 * Merge this object with $other, recursively and without creating a copy.
	 * @see Taintedness::asMergedWith() if you need a copy
	 *
	 * @param Taintedness $other
	 */
	public function mergeWith( self $other ): void {
		$this->flags |= $other->flags;
		if ( $other->unknownDimsTaint && !$this->unknownDimsTaint ) {
			$this->unknownDimsTaint = $other->unknownDimsTaint;
		} elseif ( $other->unknownDimsTaint ) {
			$this->unknownDimsTaint->mergeWith( $other->unknownDimsTaint );
		}
		$this->keysTaint |= $other->keysTaint;
		foreach ( $other->dimTaint as $key => $val ) {
			if ( !isset( $this->dimTaint[$key] ) ) {
				$this->dimTaint[$key] = clone $val;
			} else {
				$this->dimTaint[$key]->mergeWith( $val );
			}
		}
	}

	/**
	 * Merge this object with $other, recursively, creating a copy.
	 * @see Taintedness::mergeWith() for in-place merge
	 *
	 * @param Taintedness $other
	 * @return $this
	 */
	public function asMergedWith( self $other ): self {
		$ret = clone $this;
		$ret->mergeWith( $other );
		return $ret;
	}

	// Offsets taintedness

	/**
	 * Set the taintedness for $offset to $value, in place
	 *
	 * @param Node|mixed $offset Node or a scalar value, already resolved
	 * @param Taintedness $value
	 */
	public function setOffsetTaintedness( $offset, self $value ): void {
		if ( is_scalar( $offset ) ) {
			$this->dimTaint[$offset] = $value;
		} else {
			$this->unknownDimsTaint = $this->unknownDimsTaint ?? self::newSafe();
			$this->unknownDimsTaint->mergeWith( $value );
		}
	}

	/**
	 * Adds the bits in $value to the taintedness of the keys
	 * @param int $value
	 */
	public function addKeysTaintedness( int $value ): void {
		$this->keysTaint |= $value;
	}

	/**
	 * Set the taintedness of $val after the list of offsets given by $offsets, with or without override.
	 *
	 * @param array $offsets This is an integer-keyed, ordered list of offsets. E.g. the list
	 *  [ 'a', 'b', 'c' ] means assigning to $var['a']['b']['c']. This must NOT be empty.
	 * @phan-param non-empty-list<mixed> $offsets
	 * @param Taintedness[] $offsetsTaint Taintedness for each offset in $offsets
	 * @param Taintedness $val
	 * @param bool $override
	 */
	public function setTaintednessAtOffsetList(
		array $offsets,
		array $offsetsTaint,
		self $val,
		bool $override
	): void {
		assert( (bool)$offsets, 'Should not be empty' );
		$base = $this;
		// Just in case keys are not consecutive
		$offsets = array_values( $offsets );
		$lastIdx = count( $offsets ) - 1;
		foreach ( $offsets as $i => $offset ) {
			$isLast = $i === $lastIdx;
			if ( !is_scalar( $offset ) ) {
				// Note, if the offset is scalar its taint is NO_TAINT
				$base->keysTaint |= $offsetsTaint[$i]->get();

				// NOTE: This is intendedly done for Nodes AND null. We assume that null here means
				// "implicit" dim (`$a[] = 'b'`), aka unknown dim.

				if ( $isLast ) {
					if ( !$base->unknownDimsTaint || $override ) {
						$base->unknownDimsTaint = $val;
					} else {
						$base->unknownDimsTaint->mergeWith( $val );
					}
					return;
				}

				if ( !$base->unknownDimsTaint ) {
					$base->unknownDimsTaint = self::newSafe();
				}
				$base = $base->unknownDimsTaint;
				continue;
			}

			if ( $isLast ) {
				// Mission accomplished!
				if ( !isset( $base->dimTaint[$offset] ) || $override ) {
					$base->dimTaint[$offset] = $val;
				} else {
					$base->dimTaint[$offset]->mergeWith( $val );
				}
				return;
			}

			if ( !isset( $base->dimTaint[$offset] ) ) {
				// Create the element as safe and move on
				$base->dimTaint[$offset] = self::newSafe();
			}
			$base = $base->dimTaint[$offset];
		}
	}

	/**
	 * Apply an array addition with $other
	 *
	 * @param Taintedness $other
	 */
	public function arrayPlus( self $other ): void {
		$this->flags |= $other->flags;
		if ( $other->unknownDimsTaint && !$this->unknownDimsTaint ) {
			$this->unknownDimsTaint = $other->unknownDimsTaint;
		} elseif ( $other->unknownDimsTaint ) {
			$this->unknownDimsTaint->mergeWith( $other->unknownDimsTaint );
		}
		$this->keysTaint |= $other->keysTaint;
		// This is not recursive because array addition isn't
		$this->dimTaint += $other->dimTaint;
	}

	/**
	 * Apply the effect of array addition and return a clone of $this
	 *
	 * @param Taintedness $other
	 * @return $this
	 */
	public function asArrayPlusWith( self $other ): self {
		$ret = clone $this;
		$ret->arrayPlus( $other );
		return $ret;
	}

	/**
	 * Get the taintedness for the given offset, if set. If $offset could not be resolved, this
	 * will return the whole object, with taint from unknown keys added. If the offset is not known,
	 * it will return a new Taintedness object without the original shape, and with taint from
	 * unknown keys added.
	 *
	 * @param Node|string|int|bool|float|null $offset
	 * @return self Always a copy
	 */
	public function getTaintednessForOffsetOrWhole( $offset ): self {
		if ( !is_scalar( $offset ) ) {
			return $this->asValueFirstLevel();
		}
		if ( isset( $this->dimTaint[$offset] ) ) {
			if ( $this->unknownDimsTaint ) {
				$ret = $this->dimTaint[$offset]->asMergedWith( $this->unknownDimsTaint );
			} else {
				$ret = $this->dimTaint[$offset];
			}
		} elseif ( $this->unknownDimsTaint ) {
			$ret = $this->unknownDimsTaint;
		} else {
			return new self( $this->flags );
		}
		$ret->flags |= $this->flags;
		return $ret;
	}

	/**
	 * Create a new object with $this at the given $offset (if scalar) or as unknown object.
	 *
	 * @param Node|string|int|bool|float|null $offset
	 * @return self Always a copy
	 */
	public function asMaybeMovedAtOffset( $offset ): self {
		$ret = self::newSafe();
		if ( $offset instanceof Node || $offset === null ) {
			$ret->unknownDimsTaint = clone $this;
			$ret->flags = $this->flags;
			return $ret;
		}
		$ret->dimTaint[$offset] = clone $this;
		return $ret;
	}

	/**
	 * Get a representation of this taint at the first depth level. For instance, this can be used in a foreach
	 * assignment for the value. Own taint and unknown keys taint are preserved, and then we merge in recursively
	 * all the current keys.
	 *
	 * @return $this
	 */
	public function asValueFirstLevel(): self {
		$ret = new self( $this->flags );
		if ( $this->unknownDimsTaint ) {
			$ret->mergeWith( $this->unknownDimsTaint );
		}
		foreach ( $this->dimTaint as $val ) {
			$ret->mergeWith( $val );
		}
		return $ret;
	}

	/**
	 * Creates a copy of this object without known offsets, and without keysTaint
	 * @return $this
	 */
	public function withoutKeys(): self {
		$ret = clone $this;
		$ret->keysTaint = SecurityCheckPlugin::NO_TAINT;
		if ( !$ret->dimTaint ) {
			return $ret;
		}
		$ret->unknownDimsTaint = $ret->unknownDimsTaint ?? self::newSafe();
		foreach ( $ret->dimTaint as $dim => $taint ) {
			$ret->unknownDimsTaint->mergeWith( $taint );
			unset( $ret->dimTaint[$dim] );
		}
		return $ret;
	}

	/**
	 * Get a representation of this taint to be used in a foreach assignment for the key
	 *
	 * @return $this
	 */
	public function asKeyForForeach(): self {
		return new self( $this->keysTaint | $this->flags );
	}

	// Conversion/checks shortcuts

	/**
	 * Are any of the positive (i.e HTML_TAINT) taint flags set
	 *
	 * @return bool If the variable has known (non-execute taint)
	 */
	public function isAllTaint(): bool {
		return $this->has( SecurityCheckPlugin::ALL_TAINT );
	}

	/**
	 * Combination of isExecTaint and isAllTaint
	 * @return bool
	 */
	public function isExecOrAllTaint(): bool {
		return $this->has( SecurityCheckPlugin::ALL_TAINT | SecurityCheckPlugin::ALL_EXEC_TAINT );
	}

	/**
	 * Check whether this object has no taintedness.
	 *
	 * @return bool
	 */
	public function isSafe(): bool {
		// Don't use get() for performance
		if ( $this->flags !== SecurityCheckPlugin::NO_TAINT ) {
			return false;
		}
		if ( $this->keysTaint !== SecurityCheckPlugin::NO_TAINT ) {
			return false;
		}
		if ( $this->unknownDimsTaint && !$this->unknownDimsTaint->isSafe() ) {
			return false;
		}
		foreach ( $this->dimTaint as $val ) {
			if ( !$val->isSafe() ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Convert exec to yes taint recursively. Special flags like UNKNOWN or INAPPLICABLE are discarded.
	 * Any YES flags are also discarded. Note that this returns a copy of the
	 * original object. The shape is preserved.
	 *
	 * @warning This function is nilpotent: f^2(x) = 0
	 *
	 * @return self
	 */
	public function asExecToYesTaint(): self {
		$ret = new self( ( $this->flags & SecurityCheckPlugin::ALL_EXEC_TAINT ) >> 1 );
		if ( $this->unknownDimsTaint ) {
			$ret->unknownDimsTaint = $this->unknownDimsTaint->asExecToYesTaint();
		}
		$ret->keysTaint = ( $this->keysTaint & SecurityCheckPlugin::ALL_EXEC_TAINT ) >> 1;
		foreach ( $this->dimTaint as $k => $val ) {
			$ret->dimTaint[$k] = $val->asExecToYesTaint();
		}
		return $ret;
	}

	/**
	 * Similar to asExecToYesTaint, but preserves existing YES flags
	 *
	 * @return self
	 */
	public function withExecToYesTaint(): self {
		$flags = ( $this->flags & SecurityCheckPlugin::ALL_TAINT ) |
			( ( $this->flags & SecurityCheckPlugin::ALL_EXEC_TAINT ) >> 1 );
		$ret = new self( $flags );
		if ( $this->unknownDimsTaint ) {
			$ret->unknownDimsTaint = $this->unknownDimsTaint->withExecToYesTaint();
		}
		$ret->keysTaint = ( $this->keysTaint & SecurityCheckPlugin::ALL_TAINT ) |
			( ( $this->keysTaint & SecurityCheckPlugin::ALL_EXEC_TAINT ) >> 1 );
		foreach ( $this->dimTaint as $k => $val ) {
			$ret->dimTaint[$k] = $val->withExecToYesTaint();
		}
		return $ret;
	}

	/**
	 * Add SQL_TAINT wherever SQL_NUMKEY_TAINT is set
	 */
	public function addSqlToNumkey(): void {
		if ( $this->flags & SecurityCheckPlugin::SQL_NUMKEY_TAINT ) {
			$this->flags |= SecurityCheckPlugin::SQL_TAINT;
		}
		if ( $this->keysTaint & SecurityCheckPlugin::SQL_NUMKEY_TAINT ) {
			$this->keysTaint |= SecurityCheckPlugin::SQL_TAINT;
		}
		if ( $this->unknownDimsTaint ) {
			$this->unknownDimsTaint->addSqlToNumkey();
		}
		foreach ( $this->dimTaint as $val ) {
			$val->addSqlToNumkey();
		}
	}

	/**
	 * Convert the yes taint bits to corresponding exec taint bits recursively.
	 * Any UNKNOWN_TAINT or INAPPLICABLE_TAINT is discarded. Note that this returns a copy of the
	 * original object. The shape is preserved.
	 *
	 * @warning This function is nilpotent: f^2(x) = 0
	 *
	 * @return self
	 */
	public function asYesToExecTaint(): self {
		$ret = new self( ( $this->flags & SecurityCheckPlugin::ALL_TAINT ) << 1 );
		if ( $this->unknownDimsTaint ) {
			$ret->unknownDimsTaint = $this->unknownDimsTaint->asYesToExecTaint();
		}
		$ret->keysTaint = ( $this->keysTaint & SecurityCheckPlugin::ALL_TAINT ) << 1;
		foreach ( $this->dimTaint as $k => $val ) {
			$ret->dimTaint[$k] = $val->asYesToExecTaint();
		}
		return $ret;
	}

	/**
	 * @param ParamLinksOffsets $offsets
	 * @return self
	 */
	public function asMovedAtRelevantOffsets( ParamLinksOffsets $offsets ): self {
		$ret = $offsets->getOwn() ? clone $this : self::newSafe();
		foreach ( $offsets->getDims() as $k => $val ) {
			$newVal = $this->asMovedAtRelevantOffsets( $val );
			if ( isset( $ret->dimTaint[$k] ) ) {
				$ret->dimTaint[$k]->mergeWith( $newVal );
			} else {
				$ret->dimTaint[$k] = $newVal;
			}
		}
		$unknownOffs = $offsets->getUnknown();
		if ( $unknownOffs ) {
			$newVal = $this->asMovedAtRelevantOffsets( $unknownOffs );
			if ( $ret->unknownDimsTaint ) {
				$ret->unknownDimsTaint->mergeWith( $newVal );
			} else {
				$ret->unknownDimsTaint = $newVal;
			}
		}
		return $ret;
	}

	/**
	 * Utility method to convert some flags from EXEC to YES. Note that this is not used internally
	 * to avoid the unnecessary overhead of a function call in hot code.
	 *
	 * @param int $flags
	 * @return int
	 */
	public static function flagsAsExecToYesTaint( int $flags ): int {
		return ( $flags & SecurityCheckPlugin::ALL_EXEC_TAINT ) >> 1;
	}

	/**
	 * @todo This method shouldn't be necessary (ideally)
	 * @return PreservedTaintedness
	 */
	public function asPreservedTaintedness(): PreservedTaintedness {
		$ret = new PreservedTaintedness( new ParamLinksOffsets( true, $this->flags ) );
		foreach ( $this->dimTaint as $k => $val ) {
			$ret->setOffsetTaintedness( $k, $val->asPreservedTaintedness() );
		}
		if ( $this->unknownDimsTaint ) {
			$ret->setOffsetTaintedness( null, $this->unknownDimsTaint->asPreservedTaintedness() );
		}
		return $ret;
	}

	/**
	 * Get a stringified representation of this taintedness, useful for debugging etc.
	 *
	 * @param string $indent
	 * @return string
	 */
	public function toString( $indent = '' ): string {
		$flags = SecurityCheckPlugin::taintToString( $this->flags );
		$keys = SecurityCheckPlugin::taintToString( $this->keysTaint );
		$ret = <<<EOT
{
$indent    Own taint: $flags
$indent    Keys: $keys
$indent    Elements: {
EOT;

		$kIndent = "$indent    ";
		$first = "\n";
		$last = '';
		foreach ( $this->dimTaint as $key => $taint ) {
			$ret .= "$first$kIndent    $key => " . $taint->toString( "$kIndent    " ) . "\n";
			$first = '';
			$last = $kIndent;
		}
		if ( $this->unknownDimsTaint ) {
			$ret .= "$first$kIndent    UNKNOWN => " . $this->unknownDimsTaint->toString( "$kIndent    " ) . "\n";
			$last = $kIndent;
		}
		$ret .= "$last}\n$indent}";
		return $ret;
	}

	/**
	 * Get a stringified representation of this taintedness suitable for the debug annotation
	 *
	 * @return string
	 */
	public function toShortString(): string {
		$flags = SecurityCheckPlugin::taintToString( $this->flags );
		$ret = "{Own: $flags";
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
			$keyParts[] = 'UNKNOWN => ' . $this->unknownDimsTaint->toShortString();
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
	public function __toString(): string {
		return $this->toString();
	}
}
