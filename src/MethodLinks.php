<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use Phan\Library\Set;

/**
 * Value object that represents method links.
 * @todo We might store links inside Taintedness, but the memory usage might skyrocket
 */
class MethodLinks {
	/** @var Set */
	private $links;

	/**
	 * @param Set $links
	 */
	public function __construct( Set $links ) {
		$this->links = $links;
	}

	/**
	 * @return self
	 */
	public static function newEmpty() : self {
		return new self( new Set );
	}

	/**
	 * Merge this object with $other, recursively and without creating a copy.
	 *
	 * @param self $other
	 */
	public function mergeWith( self $other ) : void {
		$this->links = self::mergeSets( $this->links, $other->links );
	}

	/**
	 * Merge this object with $other, recursively, creating a copy.
	 *
	 * @param self $other
	 * @return self
	 */
	public function asMergedWith( self $other ) : self {
		$ret = clone $this;
		$ret->mergeWith( $other );
		return $ret;
	}

	/**
	 * Make sure to clone member variables, too.
	 */
	public function __clone() {
		$this->links = clone $this->links;
	}

	/**
	 * Temporary method until proper handlers are created.
	 *
	 * @return Set
	 */
	public function getLinks() : Set {
		return $this->links;
	}

	/**
	 * @return bool
	 */
	public function isEmpty() : bool {
		return count( $this->links ) === 0;
	}

	/**
	 * @param Set $l1
	 * @param Set $l2
	 * @return Set
	 */
	private static function mergeSets( Set $l1, Set $l2 ) : Set {
		$ret = $l1;
		$remainingL2 = new Set;
		foreach ( $l2 as $method ) {
			if ( $ret->contains( $method ) ) {
				$leftLinks = $ret[$method];
				$rightLinks = $l2[$method];
				foreach ( $rightLinks as $k => $val ) {
					$leftLinks[$k] = ( $leftLinks[$k] ?? false ) || $val;
				}
				$ret[$method] = $leftLinks;
			} else {
				$remainingL2->attach( $method, $l2[$method] );
			}
		}
		return $ret->union( $remainingL2 );
	}

	// TODO __toString
}
