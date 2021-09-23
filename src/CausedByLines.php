<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use Phan\Language\Element\FunctionInterface;

/**
 * Value object used to store caused-by lines.
 *
 * @note `clone` will not deep-clone instances of this class. That'd be more correct in theory, but it's
 * tremendously expensive for this class (+35s and +600MB for MW core).
 *
 * @todo Keep per-offset caused-by lines
 */
class CausedByLines {
	private const MAX_LINES_PER_ISSUE = 15;
	// XXX Hack: Enforce a hard limit, or things may explode
	private const LINES_HARD_LIMIT = 30;

	/**
	 * @var array<array<Taintedness|string|MethodLinks>>
	 * @phan-var list<array{0:Taintedness,1:string,2:MethodLinks}>
	 */
	private $lines = [];

	/**
	 * @param Taintedness $taintedness
	 * @param string $line
	 * @param MethodLinks|null $links
	 */
	public function addLine( Taintedness $taintedness, string $line, MethodLinks $links = null ): void {
		$links = $links ?? MethodLinks::newEmpty();
		if ( !$this->lines ) {
			$this->lines = [ [ $taintedness, $line, $links ] ];
			return;
		}
		if ( count( $this->lines ) >= self::LINES_HARD_LIMIT ) {
			return;
		}

		$idx = array_search( $line, array_column( $this->lines, 1 ), true );
		if ( $idx !== false ) {
			$this->lines[ $idx ][0] = $this->lines[ $idx ][0]->withObj( $taintedness );
			$this->lines[ $idx ][2] = $this->lines[ $idx ][2]->asMergedWith( $links );
		} else {
			$this->lines[] = [ $taintedness, $line, $links ];
		}
	}

	/**
	 * Move any possibly preserved taintedness stored in the method links to the actual taintedness of this line.
	 * @param Taintedness $taintedness
	 * @return self
	 */
	public function asPreservingTaintedness( Taintedness $taintedness ): self {
		$ret = new self;
		$curTaint = $taintedness->get();
		foreach ( $this->lines as [ $_, $eLine, $links ] ) {
			if ( $links->canPreserveTaintFlags( $curTaint ) ) {
				$ret->lines[] = [ new Taintedness( $curTaint ), $eLine, MethodLinks::newEmpty() ];
			}
		}
		return $ret;
	}

	/**
	 * @param Taintedness $taintedness
	 * @return self
	 */
	public function asIntersectedWithTaintedness( Taintedness $taintedness ): self {
		$ret = new self;
		$curTaint = $taintedness->get();
		foreach ( $this->lines as [ $eTaint, $eLine, $links ] ) {
			$ret->lines[] = [ $eTaint->withOnly( $curTaint ), $eLine, $links ];
		}
		return $ret;
	}

	/**
	 * @param FunctionInterface $func
	 * @param int $param
	 * @return self
	 */
	public function asFilteredForFuncAndParam( FunctionInterface $func, int $param ): self {
		$ret = new self;
		foreach ( $this->lines as $line ) {
			if ( $line[2]->hasDataForFuncAndParam( $func, $param ) ) {
				$ret->lines[] = $line;
			}
		}
		return $ret;
	}

	/**
	 * @return self
	 */
	public function getLinesForGenericReturn(): self {
		$ret = new self;
		foreach ( $this->lines as [ $lineTaint, $lineLine, $_ ] ) {
			if ( !$lineTaint->isSafe() ) {
				// For generic lines, links don't matter
				$ret->lines[] = [ $lineTaint, $lineLine, MethodLinks::newEmpty() ];
			}
		}
		return $ret;
	}

	/**
	 * @param self $other
	 */
	public function mergeWith( self $other ): void {
		$this->lines = self::mergeRaw( $this->lines, $other->lines );
	}

	/**
	 * @param self $other
	 * @return self
	 */
	public function asMergedWith( self $other ): self {
		$ret = clone $this;
		$ret->mergeWith( $other );
		return $ret;
	}

	/**
	 * Merges two caused-by arrays. Note that this isn't a merge operation like
	 * array_merge. What this method does is:
	 * 1 - if $second is a subset of $first, return $first;
	 * 2 - update taintedness values in $first if the *lines* (not taint values) in $second
	 *   are a subset of the lines in $first;
	 * 3 - if an upper set of $first *lines* is also a lower set of $second *lines*, remove that upper
	 *   set from $first and merge the rest with $second;
	 * 4 - array_merge otherwise;
	 *
	 * Step 2 is very important, because otherwise, caused-by lines can grow exponentially if
	 * even a single taintedness value in $first changes.
	 *
	 * @param array<array<Taintedness|string|MethodLinks>> $first
	 * @phan-param list<array{0:Taintedness,1:string,2:MethodLinks}> $first
	 * @param array<array<Taintedness|string|MethodLinks>> $second
	 * @phan-param list<array{0:Taintedness,1:string,2:MethodLinks}> $second
	 * @return array<array<Taintedness|string|MethodLinks>>
	 * @phan-return list<array{0:Taintedness,1:string,2:MethodLinks}>
	 */
	private static function mergeRaw( array $first, array $second ): array {
		if ( !$first ) {
			return $second;
		}
		if ( !$second || self::getArraySubsetIdx( $first, $second ) !== false ) {
			return $first;
		}

		$baseLines = array_column( $first, 1 );
		$newLines = array_column( $second, 1 );
		$subsIdx = self::getArraySubsetIdx( $baseLines, $newLines );
		if ( $subsIdx !== false ) {
			foreach ( $second as $i => $cur ) {
				$first[ $i + $subsIdx ][0] = $first[ $i + $subsIdx ][0]->withObj( $cur[0] );
				$first[ $i + $subsIdx ][2] = $first[ $i + $subsIdx ][2]->asMergedWith( $cur[2] );
			}
			return $first;
		}

		$ret = null;
		$baseLen = count( $first );
		$newLen = count( $second );
		// NOTE: array_shift is O(n), and O(n^2) over all iterations, because it reindexes the whole array.
		// So reverse the arrays, that is O(n) twice, and use array_pop which is O(1) (O(n) for all iterations)
		$remaining = array_reverse( $baseLines );
		$newRev = array_reverse( $newLines );
		// Assuming the lines as posets with the "natural" order used by PHP (that is, not the keys):
		// since we're working with reversed arrays, remaining lines should be an upper set of the reversed
		// new lines; which is to say, a lower set of the non-reversed new lines.
		$expectedIndex = $newLen - $baseLen;
		do {
			if ( $expectedIndex >= 0 && self::getArraySubsetIdx( $newRev, $remaining ) === $expectedIndex ) {
				$startIdx = $baseLen - $newLen + $expectedIndex;
				for ( $j = $startIdx; $j < $baseLen; $j++ ) {
					$first[$j][0] = $first[$j][0]->withObj( $second[$j - $startIdx][0] );
					$first[$j][2] = $first[$j][2]->asMergedWith( $second[$j - $startIdx][2] );
				}
				$ret = array_merge( $first, array_slice( $second, $newLen - $expectedIndex ) );
				break;
			}
			array_pop( $remaining );
			$expectedIndex++;
		} while ( $remaining );
		$ret = $ret ?? array_merge( $first, $second );

		return array_slice( $ret, 0, self::LINES_HARD_LIMIT );
	}

	/**
	 * Check whether $needle is subset of $haystack, regardless of the keys, and returns
	 * the starting index of the subset in the $haystack array. If the subset occurs multiple
	 * times, this will just find the first one.
	 *
	 * @param array[] $haystack
	 * @phan-param list<array{0:Taintedness,1:string,2:MethodLinks}> $haystack
	 * @param array[] $needle
	 * @phan-param list<array{0:Taintedness,1:string,2:MethodLinks}> $needle
	 * @return false|int False if not a subset, the starting index if it is.
	 * @note Use strict comparisons with the return value!
	 */
	private static function getArraySubsetIdx( array $haystack, array $needle ) {
		if ( !$needle || !$haystack ) {
			// For our needs, the empty array is not a subset of anything
			return false;
		}

		$needleLength = count( $needle );
		$haystackLength = count( $haystack );
		if ( $haystackLength < $needleLength ) {
			return false;
		}
		$curIdx = 0;
		foreach ( $haystack as $i => $el ) {
			if ( $el === $needle[ $curIdx ] ) {
				$curIdx++;
			} else {
				$curIdx = 0;
			}
			if ( $curIdx === $needleLength ) {
				return $i - ( $needleLength - 1 );
			}
		}
		return false;
	}

	/**
	 * @param self $other
	 * @return bool
	 */
	public function isSupersetOf( self $other ): bool {
		return self::getArraySubsetIdx( $this->lines, $other->lines ) !== false;
	}

	public function isEmpty(): bool {
		return $this->lines === [];
	}

	/**
	 * Return a truncated, stringified representation of these lines to be used when reporting issues.
	 *
	 * @todo Perhaps this should include the first and last X lines, not the first 2X. However,
	 *   doing so would make phan emit a new issue for the same line whenever new caused-by
	 *   lines are added to the array.
	 *
	 * @param Taintedness|null $taintedness
	 * @return string
	 */
	public function toStringForIssue( ?Taintedness $taintedness ): string {
		$filteredLines = $this->getRelevantLinesForTaintedness( $taintedness );
		if ( !$filteredLines ) {
			return '';
		}

		if ( count( $filteredLines ) <= self::MAX_LINES_PER_ISSUE ) {
			$linesPart = implode( '; ', $filteredLines );
		} else {
			$linesPart = implode( '; ', array_slice( $filteredLines, 0, self::MAX_LINES_PER_ISSUE ) ) . '; ...';
		}
		return ' (Caused by: ' . $linesPart . ')';
	}

	/**
	 * @param Taintedness|null $taintedness
	 * @return string[]
	 */
	private function getRelevantLinesForTaintedness( ?Taintedness $taintedness ): array {
		if ( $taintedness === null ) {
			return array_column( $this->lines, 1 );
		}

		$taintedness = $this->normalizeTaintForCausedBy( $taintedness )->get();
		$ret = [];
		foreach ( $this->lines as [ $lineTaint, $lineText, $lineLinks ] ) {
			// Don't check for equality, as that would fail with MultiTaint
			if ( $lineTaint->has( $taintedness ) || $lineLinks->canPreserveTaintFlags( $taintedness ) ) {
				$ret[] = $lineText;
			}
		}
		return $ret;
	}

	/**
	 * Normalize a taintedness value for caused-by lookup
	 *
	 * @param Taintedness $taintedness
	 * @return Taintedness
	 */
	private function normalizeTaintForCausedBy( Taintedness $taintedness ): Taintedness {
		$taintedness = $taintedness->withExecToYesTaint();
		// Special case: we assume the bad case, preferring false positives over false negatives
		$taintedness->addSqlToNumkey();
		return $taintedness;
	}

	/**
	 * @return string[]
	 * @suppress PhanUnreferencedPublicMethod
	 */
	public function toLinesArray(): array {
		return array_column( $this->lines, 1 );
	}

	/**
	 * @return string
	 * @suppress PhanUnreferencedPublicMethod
	 */
	public function toDebugString(): string {
		$r = [];
		foreach ( $this->lines as [ $t, $line, $links ] ) {
			$r[] = "\t[\n\t\tT: " . $t->toShortString() . "\n\t\tL: " . $line . "\n\t\tLinks: " . $links . "\n\t]";
		}
		return "[\n" . implode( ",\n", $r ) . "\n]";
	}
}
