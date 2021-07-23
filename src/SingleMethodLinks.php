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

	/**
	 * @param int $i
	 * @return self
	 */
	public static function newWithParam( int $i ): self {
		$ret = new self;
		$ret->addParam( $i );
		return $ret;
	}

	/**
	 * @param int $i
	 */
	public function addParam( int $i ): void {
		$this->params[$i] = new ParamLinksOffsets();
	}

	/**
	 * @param self $other
	 */
	public function mergeWith( self $other ): void {
		foreach ( $other->params as $i => $_ ) {
			if ( isset( $this->params[$i] ) ) {
				$this->params[$i]->mergeWith( $other->params[$i] );
			} else {
				$this->params[$i] = $other->params[$i];
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
		$this->params = array_diff_key( $this->params, array_fill_keys( $params, 1 ) );
	}

	public function __clone() {
		foreach ( $this->params as $k => $val ) {
			$this->params[$k] = clone $val;
		}
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		$paramBits = [];
		foreach ( $this->params as $k => $paramOffsets ) {
			$paramBits[] = "$k: { " . $paramOffsets->__toString() . ' }';
		}
		return '[ ' . implode( ', ', $paramBits ) . ' ]';
	}
}
