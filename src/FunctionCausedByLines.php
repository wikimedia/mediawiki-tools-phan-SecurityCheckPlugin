<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

class FunctionCausedByLines {
	/** @var CausedByLines */
	private $genericLines;
	/** @var CausedByLines[] */
	private $paramLines = [];
	/** @var int|null Index of a variadic parameter, if any */
	private $variadicParamIndex;
	/** @var CausedByLines|null */
	private $variadicParamLines;

	public function __construct() {
		$this->genericLines = new CausedByLines();
	}

	/**
	 * @return CausedByLines
	 */
	public function getGenericLines(): CausedByLines {
		return $this->genericLines;
	}

	/**
	 * @param string $line
	 * @param Taintedness $taint
	 */
	public function addGenericLine( string $line, Taintedness $taint ): void {
		$this->genericLines->addLine( $taint, $line );
	}

	/**
	 * @param int $param
	 * @param string $line
	 * @param Taintedness $taint
	 */
	public function addParamLine( int $param, string $line, Taintedness $taint ): void {
		assert( $param !== $this->variadicParamIndex );
		if ( !isset( $this->paramLines[$param] ) ) {
			$this->paramLines[$param] = new CausedByLines();
		}
		$this->paramLines[$param]->addLine( $taint, $line );
	}

	/**
	 * @param int $param
	 * @param string $line
	 * @param Taintedness $taint
	 */
	public function addVariadicParamLine( int $param, string $line, Taintedness $taint ): void {
		assert( !isset( $this->paramLines[$param] ) );
		$this->variadicParamIndex = $param;
		if ( !$this->variadicParamLines ) {
			$this->variadicParamLines = new CausedByLines();
		}
		$this->variadicParamLines->addLine( $taint, $line );
	}

	/**
	 * @param int $param
	 * @return CausedByLines
	 */
	public function getParamLines( int $param ): CausedByLines {
		if ( isset( $this->paramLines[$param] ) ) {
			return $this->paramLines[$param];
		}
		if (
			$this->variadicParamIndex !== null && $param >= $this->variadicParamIndex &&
			$this->variadicParamLines
		) {
			return $this->variadicParamLines;
		}
		return new CausedByLines();
	}

	/**
	 * @return CausedByLines
	 */
	public function getAllLinesMerged(): CausedByLines {
		$ret = clone $this->genericLines;
		foreach ( $this->paramLines as $line ) {
			$ret->mergeWith( $line );
		}
		if ( $this->variadicParamLines ) {
			$ret->mergeWith( $this->variadicParamLines );
		}
		return $ret;
	}

	/**
	 * @param CausedByLines $generic
	 * @return self
	 */
	public function withGenericError( CausedByLines $generic ): self {
		$ret = clone $this;
		$ret->genericLines = $generic;
		return $ret;
	}

	public function __clone() {
		$this->genericLines = clone $this->genericLines;
		foreach ( $this->paramLines as $k => $pLines ) {
			$this->paramLines[$k] = clone $pLines;
		}
		if ( $this->variadicParamLines ) {
			$this->variadicParamLines = clone $this->variadicParamLines;
		}
	}

	/**
	 * @return string
	 */
	public function toString(): string {
		$str = "{\nGeneric: " . $this->genericLines->toDebugString() . ",\n";
		foreach ( $this->paramLines as $par => $lines ) {
			$str .= "$par: " . $lines->toDebugString() . ",\n";
		}
		if ( $this->variadicParamIndex !== null ) {
			$str .= "...{$this->variadicParamIndex}: " . $this->variadicParamLines->toDebugString() . "\n";
		}
		return "$str}";
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->toString();
	}
}
