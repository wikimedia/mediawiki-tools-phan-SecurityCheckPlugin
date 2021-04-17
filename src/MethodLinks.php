<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use Phan\Language\Element\FunctionInterface;

/**
 * Value object that represents method links.
 * @todo We might store links inside Taintedness, but the memory usage might skyrocket
 */
class MethodLinks {
	/** @var LinksSet */
	private $links;

	/**
	 * @param LinksSet $links
	 */
	private function __construct( LinksSet $links ) {
		$this->links = $links;
	}

	/**
	 * @return self
	 */
	public static function newEmpty() : self {
		return new self( new LinksSet );
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
		foreach ( $this->links as $method ) {
			$this->links[$method] = clone $this->links[$method];
		}
	}

	/**
	 * Temporary method until proper handlers are created.
	 *
	 * @return LinksSet
	 */
	public function getLinks() : LinksSet {
		return $this->links;
	}

	/**
	 * @return bool
	 */
	public function isEmpty() : bool {
		return count( $this->links ) === 0;
	}

	/**
	 * @param FunctionInterface $func
	 * @param int $i
	 */
	public function initializeParamForFunc( FunctionInterface $func, int $i ) : void {
		if ( $this->links->contains( $func ) ) {
			$this->links[$func]->addParam( $i );
		} else {
			$this->links[$func] = SingleMethodLinks::newWithParam( $i );
		}
	}

	/**
	 * @param LinksSet $l1
	 * @param LinksSet $l2
	 * @return LinksSet
	 */
	private static function mergeSets( LinksSet $l1, LinksSet $l2 ) : LinksSet {
		$ret = $l1;
		$remainingL2 = new LinksSet;
		foreach ( $l2 as $method ) {
			if ( $ret->contains( $method ) ) {
				$ret[$method]->mergeWith( $l2[$method] );
			} else {
				$remainingL2->attach( $method, $l2[$method] );
			}
		}
		return $ret->unionWith( $remainingL2 );
	}

	// TODO __toString
}
