<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use ast\Node;
use Phan\Language\Element\FunctionInterface;

/**
 * Value object that represents method links.
 * @todo We might store links inside Taintedness, but the memory usage might skyrocket
 */
class MethodLinks {
	/** @var LinksSet */
	private $links;

	/** @var self[] */
	private $dimLinks = [];

	/** @var self|null */
	private $unknownDimLinks;

	/**
	 * @param LinksSet|null $links
	 */
	public function __construct( LinksSet $links = null ) {
		$this->links = $links ?? new LinksSet();
	}

	/**
	 * @return self
	 */
	public static function newEmpty(): self {
		return new self( new LinksSet );
	}

	/**
	 * @note This returns a clone
	 * @param mixed $dim
	 * @return self
	 */
	public function getForDim( $dim ): self {
		if ( !is_scalar( $dim ) ) {
			return $this->asValueFirstLevel()->withAddedOffset( $dim );
		}
		if ( isset( $this->dimLinks[$dim] ) ) {
			$ret = clone $this->dimLinks[$dim];
			$ret->mergeWith( $this->unknownDimLinks ?? self::newEmpty() );
			$ret->links = self::mergeSets( $ret->links, $this->links );
			return $ret->withAddedOffset( $dim );
		}
		$ret = $this->unknownDimLinks ? clone $this->unknownDimLinks : self::newEmpty();
		$ret->links = self::mergeSets( $ret->links, $this->links );
		return $ret->withAddedOffset( $dim );
	}

	/**
	 * @return self
	 */
	public function asValueFirstLevel(): self {
		$ret = new self( $this->links );
		$ret->mergeWith( $this->unknownDimLinks ?? self::newEmpty() );
		foreach ( $this->dimLinks as $links ) {
			$ret->mergeWith( $links );
		}
		return $ret;
	}

	/**
	 * @param mixed $dim
	 * @param MethodLinks $links
	 */
	public function setAtDim( $dim, self $links ): void {
		if ( is_scalar( $dim ) ) {
			$this->dimLinks[$dim] = $links;
		} else {
			$this->unknownDimLinks = $this->unknownDimLinks ?? self::newEmpty();
			$this->unknownDimLinks->mergeWith( $links );
		}
	}

	/**
	 * Temporary method, should only be used in getRelevantLinksForTaintedness
	 * @return bool
	 */
	public function hasSomethingOutOfKnownDims(): bool {
		return count( $this->links ) > 0 || ( $this->unknownDimLinks && !$this->unknownDimLinks->isEmpty() );
	}

	/**
	 * @return self
	 */
	public function asCollapsed(): self {
		$ret = new self( $this->links );
		foreach ( $this->dimLinks as $links ) {
			$ret->mergeWith( $links->asCollapsed() );
		}
		if ( $this->unknownDimLinks ) {
			$ret->mergeWith( $this->unknownDimLinks->asCollapsed() );
		}
		return $ret;
	}

	/**
	 * Merge this object with $other, recursively and without creating a copy.
	 *
	 * @param self $other
	 */
	public function mergeWith( self $other ): void {
		$this->links = self::mergeSets( $this->links, $other->links );
		foreach ( $other->dimLinks as $key => $links ) {
			if ( isset( $this->dimLinks[$key] ) ) {
				$this->dimLinks[$key]->mergeWith( $links );
			} else {
				$this->dimLinks[$key] = $links;
			}
		}
		if ( $other->unknownDimLinks && !$this->unknownDimLinks ) {
			$this->unknownDimLinks = $other->unknownDimLinks;
		} elseif ( $other->unknownDimLinks ) {
			$this->unknownDimLinks->mergeWith( $other->unknownDimLinks );
		}
	}

	/**
	 * Merge this object with $other, recursively, creating a copy.
	 *
	 * @param self $other
	 * @return self
	 */
	public function asMergedWith( self $other ): self {
		$ret = clone $this;
		$ret->mergeWith( $other );
		return $ret;
	}

	/**
	 * @param Node|mixed $offset
	 * @return self
	 */
	public function withAddedOffset( $offset ): self {
		$ret = clone $this;
		foreach ( $ret->links as $func ) {
			$ret->links[$func]->pushOffsetToAll( $offset );
		}
		return $ret;
	}

	/**
	 * @param array $offsets
	 * @phan-param array<Node|mixed> $offsets
	 * @param MethodLinks $links
	 */
	public function setLinksAtOffsetList( array $offsets, self $links ): void {
		assert( (bool)$offsets, 'Should not be empty' );
		$base = $this;
		// Just in case keys are not consecutive
		$offsets = array_values( $offsets );
		$lastIdx = count( $offsets ) - 1;
		foreach ( $offsets as $i => $offset ) {
			$isLast = $i === $lastIdx;
			if ( !is_scalar( $offset ) ) {
				if ( $isLast ) {
					if ( $base->unknownDimLinks ) {
						$base->unknownDimLinks->mergeWith( $links );
					} else {
						$base->unknownDimLinks = $links;
					}
					break;
				}
				if ( !$base->unknownDimLinks ) {
					$base->unknownDimLinks = new self;
				}
				$base = $base->unknownDimLinks;
				continue;
			}

			if ( $isLast ) {
				// Mission accomplished!
				if ( isset( $base->dimLinks[$offset] ) ) {
					$base->dimLinks[$offset]->mergeWith( $links );
				} else {
					$base->dimLinks[$offset] = $links;
				}
				break;
			}

			if ( !isset( $base->dimLinks[$offset] ) ) {
				// Create the element as safe and move on
				$base->dimLinks[$offset] = new self;
			}
			$base = $base->dimLinks[$offset];
		}
		$this->normalize();
	}

	/**
	 * Remove offset links which are already present in the "main" links. This is done for performance
	 * (see test backpropoffsets-blowup).
	 *
	 * @todo Improve (e.g. recurse)
	 * @todo Might happen sometime earlier
	 */
	private function normalize(): void {
		if ( !count( $this->links ) ) {
			return;
		}
		foreach ( $this->dimLinks as $k => $links ) {
			foreach ( $links->links as $func ) {
				if ( $this->links->contains( $func ) ) {
					$dimParams = array_keys( $links->links[$func]->getParams() );
					$thisParams = array_keys( $this->links[$func]->getParams() );
					$keepParams = array_diff( $dimParams, $thisParams );
					if ( !$keepParams ) {
						unset( $links->links[$func] );
					} else {
						$links->links[$func]->keepOnlyParams( $keepParams );
					}
				}
			}
			if ( $links->isEmpty() ) {
				unset( $this->dimLinks[$k] );
			}
		}
		if ( $this->unknownDimLinks ) {
			foreach ( $this->unknownDimLinks->links as $func ) {
				if ( $this->links->contains( $func ) ) {
					$dimParams = array_keys( $this->unknownDimLinks->links[$func]->getParams() );
					$thisParams = array_keys( $this->links[$func]->getParams() );
					$keepParams = array_diff( $dimParams, $thisParams );
					if ( !$keepParams ) {
						unset( $this->unknownDimLinks->links[$func] );
					} else {
						$this->unknownDimLinks->links[$func]->keepOnlyParams( $keepParams );
					}
				}
			}
			if ( $this->unknownDimLinks->isEmpty() ) {
				$this->unknownDimLinks = null;
			}
		}
	}

	/**
	 * Make sure to clone member variables, too.
	 */
	public function __clone() {
		$this->links = clone $this->links;
		foreach ( $this->dimLinks as $k => $links ) {
			$this->dimLinks[$k] = clone $links;
		}
		if ( $this->unknownDimLinks ) {
			$this->unknownDimLinks = clone $this->unknownDimLinks;
		}
	}

	/**
	 * Temporary method until proper handlers are created.
	 * @note This doesn't return a clone
	 *
	 * @return LinksSet
	 */
	public function getLinks(): LinksSet {
		$ret = $this->links;
		foreach ( $this->dimLinks as $link ) {
			$ret = self::mergeSets( $ret, $link->getLinks() );
		}
		if ( $this->unknownDimLinks ) {
			$ret = self::mergeSets( $ret, $this->unknownDimLinks->getLinks() );
		}
		return $ret;
	}

	/**
	 * @return bool
	 */
	public function isEmpty(): bool {
		if ( count( $this->links ) ) {
			return false;
		}
		foreach ( $this->dimLinks as $links ) {
			if ( !$links->isEmpty() ) {
				return false;
			}
		}
		if ( $this->unknownDimLinks && !$this->unknownDimLinks->isEmpty() ) {
			return false;
		}
		return true;
	}

	/**
	 * @param FunctionInterface $func
	 * @param int $i
	 */
	public function initializeParamForFunc( FunctionInterface $func, int $i ): void {
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
	private static function mergeSets( LinksSet $l1, LinksSet $l2 ): LinksSet {
		$ret = new LinksSet();
		$ret->addAll( $l1 );
		foreach ( $l2 as $method ) {
			if ( $ret->contains( $method ) ) {
				$ret[$method]->mergeWith( $l2[$method] );
			} else {
				$ret->attach( $method, $l2[$method] );
			}
		}
		return $ret;
	}

	/**
	 * @todo Store it here?
	 * @param FunctionInterface $func
	 * @param int $param
	 * @return PreservedTaintedness
	 */
	public function asPreservedTaintednessForFuncParam( FunctionInterface $func, int $param ): PreservedTaintedness {
		$ret = null;
		if ( $this->links->contains( $func ) ) {
			$ownInfo = $this->links[$func];
			if ( $ownInfo->hasParam( $param ) ) {
				$ret = new PreservedTaintedness( $ownInfo->getParamOffsets( $param ) );
			}
		}
		if ( !$ret ) {
			$ret = new PreservedTaintedness( ParamLinksOffsets::newEmpty() );
		}
		foreach ( $this->dimLinks as $dim => $dimLinks ) {
			$ret->setOffsetTaintedness( $dim, $dimLinks->asPreservedTaintednessForFuncParam( $func, $param ) );
		}
		if ( $this->unknownDimLinks ) {
			$ret->setOffsetTaintedness(
				null,
				$this->unknownDimLinks->asPreservedTaintednessForFuncParam( $func, $param )
			);
		}
		return $ret;
	}

	/**
	 * @param string $indent
	 * @return string
	 */
	public function toString( $indent = '' ): string {
		$ret = 'OWN: ' . $this->links->__toString() . ';';
		if ( !$this->dimLinks ) {
			return $ret;
		}
		$ret .= ' CHILDREN: ';
		$childrenIndent = $indent . "\t";
		foreach ( $this->dimLinks as $key => $links ) {
			$ret .= "\n$childrenIndent$key: " . $links->toString( $childrenIndent );
		}
		if ( $this->unknownDimLinks ) {
			$ret .= "\n\t(UNKNOWN): " . $this->unknownDimLinks->toString( $childrenIndent );
		}
		return $ret;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->toString();
	}
}
