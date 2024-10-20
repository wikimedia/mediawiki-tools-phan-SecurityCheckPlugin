<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use ast\Node;

/**
 * Links for a single method
 */
class SingleMethodLinks {
	/**
	 * @var ParamLinksOffsets[]
	 */
	private $params = [];

	public static function newWithParam( int $i, int $initialFlags ): self {
		$ret = new self;
		$ret->addParam( $i, $initialFlags );
		return $ret;
	}

	public function addParam( int $i, int $flags ): void {
		$this->params[$i] = new ParamLinksOffsets( $flags );
	}

	/**
	 * @param self $other
	 */
	public function mergeWith( self $other ): void {
		foreach ( $other->params as $i => $otherPar ) {
			if ( isset( $this->params[$i] ) ) {
				$this->params[$i]->mergeWith( $otherPar );
			} else {
				$this->params[$i] = $otherPar;
			}
		}
	}

	/**
	 * @param Node|string|int|null $offset
	 */
	public function pushOffsetToAll( $offset ): void {
		foreach ( $this->params as $i => $_ ) {
			$this->params[$i]->pushOffset( $offset );
		}
	}

	/**
	 * @return self
	 */
	public function asAllParamsMovedToKeys(): self {
		$ret = new self;
		foreach ( $this->params as $i => $offsets ) {
			$ret->params[$i] = $offsets->asMovedToKeys();
		}
		return $ret;
	}

	/**
	 * @todo Try to avoid this method
	 * @return ParamLinksOffsets[]
	 */
	public function getParams(): array {
		return $this->params;
	}

	/**
	 * @param int $x
	 * @return bool
	 */
	public function hasParam( int $x ): bool {
		return isset( $this->params[$x] );
	}

	/**
	 * @note This will fail hard if unset.
	 * @param int $x
	 * @return ParamLinksOffsets
	 */
	public function getParamOffsets( int $x ): ParamLinksOffsets {
		return $this->params[$x];
	}

	/**
	 * @param int[] $params
	 */
	public function keepOnlyParams( array $params ): void {
		$this->params = array_intersect_key( $this->params, array_fill_keys( $params, 1 ) );
	}

	public function __clone() {
		foreach ( $this->params as $k => $val ) {
			$this->params[$k] = clone $val;
		}
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function __toString(): string {
		$paramBits = [];
		foreach ( $this->params as $k => $paramOffsets ) {
			$paramBits[] = "$k: { " . $paramOffsets->__toString() . ' }';
		}
		return '[ ' . implode( ', ', $paramBits ) . ' ]';
	}
}
