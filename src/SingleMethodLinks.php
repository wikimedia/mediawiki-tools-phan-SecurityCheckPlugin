<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

/**
 * Links for a single method
 */
class SingleMethodLinks {
	/** @var bool[] */
	private $params = [];

	/**
	 * @param int $i
	 * @return self
	 */
	public static function newWithParam( int $i ) : self {
		$ret = new self;
		$ret->addParam( $i );
		return $ret;
	}

	/**
	 * @param int $i
	 */
	public function addParam( int $i ) : void {
		$this->params[$i] = true;
	}

	/**
	 * @param self $other
	 */
	public function mergeWith( self $other ) : void {
		foreach ( $other->params as $i => $_ ) {
			$this->params[$i] = true;
		}
	}

	/**
	 * @return int[]
	 */
	public function getParams() : array {
		return array_keys( $this->params );
	}
}
