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

	/** @var LinksSet|null */
	private $keysLinks;

	/**
	 * @param LinksSet|null $links
	 */
	public function __construct( ?LinksSet $links = null ) {
		$this->links = $links ?? new LinksSet();
	}

	/**
	 * @return self
	 */
	public static function emptySingleton(): self {
		static $singleton;
		if ( !$singleton ) {
			$singleton = new self( new LinksSet );
		}
		return $singleton;
	}

	/**
	 * @note This returns a clone
	 * @param mixed $dim
	 * @param bool $pushOffsets
	 * @return self
	 */
	public function getForDim( $dim, bool $pushOffsets = true ): self {
		if ( $this === self::emptySingleton() ) {
			return $this;
		}
		if ( !is_scalar( $dim ) ) {
			$ret = ( new self( $this->links ) );
			if ( $pushOffsets ) {
				$ret = $ret->withAddedOffset( $dim );
			}
			if ( $this->unknownDimLinks ) {
				$ret = $ret->asMergedWith( $this->unknownDimLinks );
			}
			foreach ( $this->dimLinks as $links ) {
				$ret = $ret->asMergedWith( $links );
			}
			return $ret;
		}
		if ( isset( $this->dimLinks[$dim] ) ) {
			$ret = ( new self( $this->links ) );
			if ( $pushOffsets ) {
				$ret = $ret->withAddedOffset( $dim );
			}
			if ( $this->unknownDimLinks ) {
				$offsetLinks = $this->dimLinks[$dim]->asMergedWith( $this->unknownDimLinks );
			} else {
				$offsetLinks = $this->dimLinks[$dim];
			}
			return $ret->asMergedWith( $offsetLinks );
		}
		if ( $this->unknownDimLinks ) {
			$ret = clone $this->unknownDimLinks;
			$ret->links = clone $ret->links;
			$ret->links->mergeWith( $this->links );
		} else {
			$ret = new self( $this->links );
		}

		return $pushOffsets ? $ret->withAddedOffset( $dim ) : $ret;
	}

	/**
	 * @return self
	 */
	public function asValueFirstLevel(): self {
		if ( $this === self::emptySingleton() ) {
			return $this;
		}
		$ret = ( new self( clone $this->links ) )->withAddedOffset( null );
		if ( $this->unknownDimLinks ) {
			$ret = $ret->asMergedWith( $this->unknownDimLinks );
		}
		foreach ( $this->dimLinks as $links ) {
			$ret = $ret->asMergedWith( $links );
		}
		return $ret;
	}

	/**
	 * @return self
	 */
	public function asKeyForForeach(): self {
		if ( $this === self::emptySingleton() ) {
			return $this;
		}
		$links = $this->links->asAllMovedToKeys();
		if ( $this->keysLinks ) {
			$links = $links->asMergedWith( $this->keysLinks );
		}
		return new self( $links );
	}

	/**
	 * @param mixed $dim
	 * @param MethodLinks $links
	 */
	public function withLinksAtDim( $dim, self $links ): self {
		$ret = clone $this;
		if ( is_scalar( $dim ) ) {
			$ret->dimLinks[$dim] = $links;
		} elseif ( $ret->unknownDimLinks ) {
			$ret->unknownDimLinks = $ret->unknownDimLinks->asMergedWith( $links );
		} else {
			$ret->unknownDimLinks = $links;
		}
		return $ret;
	}

	public function withKeysLinks( LinksSet $links ): self {
		if ( !count( $links ) ) {
			return $this;
		}
		$ret = clone $this;
		if ( !$ret->keysLinks ) {
			$ret->keysLinks = $links;
		} else {
			$ret->keysLinks = $ret->keysLinks->asMergedWith( $links );
		}
		return $ret;
	}

	/**
	 * @return self
	 */
	public function asCollapsed(): self {
		if ( $this === self::emptySingleton() ) {
			return $this;
		}
		$ret = new self( $this->links );
		foreach ( $this->dimLinks as $links ) {
			$ret = $ret->asMergedWith( $links->asCollapsed() );
		}
		if ( $this->unknownDimLinks ) {
			$ret = $ret->asMergedWith( $this->unknownDimLinks->asCollapsed() );
		}
		return $ret;
	}

	/**
	 * Merge this object with $other, recursively, creating a copy.
	 *
	 * @param self $other
	 * @return self
	 */
	public function asMergedWith( self $other ): self {
		$emptySingleton = self::emptySingleton();
		if ( $other === $emptySingleton ) {
			return $this;
		}
		if ( $this === $emptySingleton ) {
			return $other;
		}
		$ret = clone $this;

		$ret->links = clone $ret->links;
		$ret->links->mergeWith( $other->links );
		foreach ( $other->dimLinks as $key => $links ) {
			if ( isset( $ret->dimLinks[$key] ) ) {
				$ret->dimLinks[$key] = $ret->dimLinks[$key]->asMergedWith( $links );
			} else {
				$ret->dimLinks[$key] = $links;
			}
		}
		if ( $other->unknownDimLinks && !$ret->unknownDimLinks ) {
			$ret->unknownDimLinks = $other->unknownDimLinks;
		} elseif ( $other->unknownDimLinks ) {
			$ret->unknownDimLinks = $ret->unknownDimLinks->asMergedWith( $other->unknownDimLinks );
		}
		if ( $other->keysLinks && !$ret->keysLinks ) {
			$ret->keysLinks = $other->keysLinks;
		} elseif ( $other->keysLinks ) {
			$ret->keysLinks = clone $ret->keysLinks;
			$ret->keysLinks->mergeWith( $other->keysLinks );
		}

		return $ret;
	}

	/**
	 * @param Node|mixed $offset
	 * @return self
	 */
	public function withAddedOffset( $offset ): self {
		$ret = clone $this;
		$ret->links = clone $ret->links;
		foreach ( $ret->links as $func ) {
			$ret->links[$func]->pushOffsetToAll( $offset );
		}
		return $ret;
	}

	/**
	 * Create a new object with $this at the given $offset (if scalar) or as unknown object.
	 *
	 * @param Node|string|int|bool|float|null $offset
	 * @param LinksSet|null $keyLinks
	 * @return self Always a copy
	 */
	public function asMaybeMovedAtOffset( $offset, ?LinksSet $keyLinks = null ): self {
		$ret = new self;
		if ( $offset instanceof Node || $offset === null ) {
			$ret->unknownDimLinks = $this;
		} else {
			$ret->dimLinks[$offset] = $this;
		}
		$ret->keysLinks = $keyLinks;
		return $ret;
	}

	public function asMovedToKeys(): self {
		$ret = new self;
		$ret->keysLinks = $this->getLinksCollapsing();
		return $ret;
	}

	/**
	 * @param self $other
	 * @param int $depth
	 * @return self
	 */
	public function asMergedForAssignment( self $other, int $depth ): self {
		if ( $depth === 0 ) {
			return $other;
		}
		$ret = clone $this;
		$ret->links = clone $ret->links;
		$ret->links->mergeWith( $other->links );
		if ( !$ret->keysLinks ) {
			$ret->keysLinks = $other->keysLinks;
		} elseif ( $other->keysLinks ) {
			$ret->keysLinks = clone $ret->keysLinks;
			$ret->keysLinks->mergeWith( $other->keysLinks );
		}
		if ( !$ret->unknownDimLinks ) {
			$ret->unknownDimLinks = $other->unknownDimLinks;
		} elseif ( $other->unknownDimLinks ) {
			$ret->unknownDimLinks = $ret->unknownDimLinks->asMergedWith( $other->unknownDimLinks );
		}
		foreach ( $other->dimLinks as $k => $v ) {
			$ret->dimLinks[$k] = isset( $ret->dimLinks[$k] )
				? $ret->dimLinks[$k]->asMergedForAssignment( $v, $depth - 1 )
				: $v;
		}
		$ret->normalize();
		return $ret;
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
			$alreadyCloned = false;
			foreach ( $links->links as $func ) {
				if ( $this->links->contains( $func ) ) {
					$dimParams = array_keys( $links->links[$func]->getParams() );
					$thisParams = array_keys( $this->links[$func]->getParams() );
					$keepParams = array_diff( $dimParams, $thisParams );
					if ( !$alreadyCloned ) {
						$this->dimLinks[$k] = clone $links;
						$this->dimLinks[$k]->links = clone $links->links;
						$alreadyCloned = true;
					}
					if ( !$keepParams ) {
						unset( $this->dimLinks[$k]->links[$func] );
					} else {
						$this->dimLinks[$k]->links[$func]->keepOnlyParams( $keepParams );
					}
				}
			}
			if ( $this->dimLinks[$k]->isEmpty() ) {
				unset( $this->dimLinks[$k] );
			}
		}
		if ( $this->unknownDimLinks ) {
			$alreadyCloned = false;
			foreach ( $this->unknownDimLinks->links as $func ) {
				if ( $this->links->contains( $func ) ) {
					$dimParams = array_keys( $this->unknownDimLinks->links[$func]->getParams() );
					$thisParams = array_keys( $this->links[$func]->getParams() );
					$keepParams = array_diff( $dimParams, $thisParams );
					if ( !$alreadyCloned ) {
						$this->unknownDimLinks = clone $this->unknownDimLinks;
						$this->unknownDimLinks->links = clone $this->unknownDimLinks->links;
						$alreadyCloned = true;
					}
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
	 * Returns all the links stored in this object as a single LinkSet object, destroying the shape. This should only
	 * be used when the shape is not relevant.
	 *
	 * @return LinksSet
	 */
	public function getLinksCollapsing(): LinksSet {
		$ret = clone $this->links;
		foreach ( $this->dimLinks as $link ) {
			$ret->mergeWith( $link->getLinksCollapsing() );
		}
		if ( $this->unknownDimLinks ) {
			$ret->mergeWith( $this->unknownDimLinks->getLinksCollapsing() );
		}
		if ( $this->keysLinks ) {
			$ret->mergeWith( $this->keysLinks );
		}
		return $ret;
	}

	/**
	 * @return array[]
	 * @phan-return array<array{0:FunctionInterface,1:int}>
	 */
	public function getMethodAndParamTuples(): array {
		$ret = [];
		foreach ( $this->links as $func ) {
			$info = $this->links[$func];
			foreach ( $info->getParams() as $i => $_ ) {
				$ret[] = [ $func, $i ];
			}
		}
		foreach ( $this->dimLinks as $link ) {
			$ret = array_merge( $ret, $link->getMethodAndParamTuples() );
		}
		if ( $this->unknownDimLinks ) {
			$ret = array_merge( $ret, $this->unknownDimLinks->getMethodAndParamTuples() );
		}
		foreach ( $this->keysLinks ?? [] as $func ) {
			$info = $this->keysLinks[$func];
			foreach ( $info->getParams() as $i => $_ ) {
				$ret[] = [ $func, $i ];
			}
		}
		return array_unique( $ret, SORT_REGULAR );
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
		if ( $this->keysLinks && count( $this->keysLinks ) ) {
			return false;
		}
		return true;
	}

	/**
	 * @param FunctionInterface $func
	 * @param int $i
	 * @return bool
	 */
	public function hasDataForFuncAndParam( FunctionInterface $func, int $i ): bool {
		if ( $this->links->contains( $func ) && $this->links[$func]->hasParam( $i ) ) {
			return true;
		}
		foreach ( $this->dimLinks as $dimLinks ) {
			if ( $dimLinks->hasDataForFuncAndParam( $func, $i ) ) {
				return true;
			}
		}
		if ( $this->unknownDimLinks && $this->unknownDimLinks->hasDataForFuncAndParam( $func, $i ) ) {
			return true;
		}
		if ( $this->keysLinks && $this->keysLinks->contains( $func ) && $this->keysLinks[$func]->hasParam( $i ) ) {
			return true;
		}
		return false;
	}

	public function withFuncAndParam(
		FunctionInterface $func,
		int $i,
		bool $isVariadic,
		int $initialFlags = SecurityCheckPlugin::ALL_TAINT
	): self {
		$ret = clone $this;

		if ( $isVariadic ) {
			$baseUnkLinks = $ret->unknownDimLinks ?? self::emptySingleton();
			$ret->unknownDimLinks = $baseUnkLinks->withFuncAndParam( $func, $i, false, $initialFlags );
			return $ret;
		}

		$ret->links = clone $ret->links;
		if ( $ret->links->contains( $func ) ) {
			$ret->links[$func]->addParam( $i, $initialFlags );
		} else {
			$ret->links[$func] = SingleMethodLinks::newWithParam( $i, $initialFlags );
		}
		return $ret;
	}

	/**
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
		if ( $this->keysLinks && $this->keysLinks->contains( $func ) ) {
			$keyInfo = $this->keysLinks[$func];
			if ( $keyInfo->hasParam( $param ) ) {
				$ret->setKeysOffsets( $keyInfo->getParamOffsets( $param ) );
			}
		}
		return $ret;
	}

	/**
	 * @param FunctionInterface $func
	 * @param int $param
	 * @return self
	 */
	public function asFilteredForFuncAndParam( FunctionInterface $func, int $param ): self {
		if ( $this === self::emptySingleton() ) {
			return $this;
		}
		$retLinks = new LinksSet();
		if ( $this->links->contains( $func ) ) {
			$retLinks->attach( $func, $this->links[$func] );
		}
		$ret = new self( $retLinks );
		foreach ( $this->dimLinks as $dim => $dimLinks ) {
			$ret = $ret->withLinksAtDim( $dim, $dimLinks->asFilteredForFuncAndParam( $func, $param ) );
		}
		if ( $this->unknownDimLinks ) {
			$ret = $ret->withLinksAtDim(
				null,
				$this->unknownDimLinks->asFilteredForFuncAndParam( $func, $param )
			);
		}
		if ( $this->keysLinks && $this->keysLinks->contains( $func ) ) {
			$ret->keysLinks = new LinksSet();
			$ret->keysLinks->attach( $func, $this->keysLinks[$func] );
		}
		return $ret;
	}

	/**
	 * @param string $indent
	 * @return string
	 */
	public function toString( string $indent = '' ): string {
		$elementsIndent = $indent . "\t";
		$ret = "{\n$elementsIndent" . 'OWN: ' . $this->links->__toString() . ',';
		if ( $this->keysLinks ) {
			$ret .= "\n{$elementsIndent}KEYS: " . $this->keysLinks->__toString() . ',';
		}
		if ( $this->dimLinks || $this->unknownDimLinks ) {
			$ret .= "\n{$elementsIndent}CHILDREN: {";
			$childrenIndent = $elementsIndent . "\t";
			foreach ( $this->dimLinks as $key => $links ) {
				$ret .= "\n$childrenIndent$key: " . $links->toString( $childrenIndent ) . ',';
			}
			if ( $this->unknownDimLinks ) {
				$ret .= "\n$childrenIndent(UNKNOWN): " . $this->unknownDimLinks->toString( $childrenIndent );
			}
			$ret .= "\n$elementsIndent}";
		}
		return $ret . "\n$indent}";
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->toString();
	}
}
