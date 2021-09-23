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
	 * @suppress PhanUnreferencedPublicMethod
	 */
	public function getGenericLines(): CausedByLines {
		return $this->genericLines;
	}

	/**
	 * @param string[] $lines
	 * @param Taintedness $taint
	 */
	public function addGenericLines( array $lines, Taintedness $taint ): void {
		foreach ( $lines as $line ) {
			$this->genericLines->addLine( clone $taint, $line );
		}
	}

	/**
	 * @param CausedByLines $lines
	 */
	public function setGenericLines( CausedByLines $lines ): void {
		$this->genericLines = $lines;
	}

	/**
	 * @param int $param
	 * @param string[] $lines
	 * @param Taintedness $taint
	 */
	public function addParamLines( int $param, array $lines, Taintedness $taint ): void {
		assert( $param !== $this->variadicParamIndex );
		if ( !isset( $this->paramLines[$param] ) ) {
			$this->paramLines[$param] = new CausedByLines();
		}
		foreach ( $lines as $line ) {
			$this->paramLines[$param]->addLine( clone $taint, $line );
		}
	}

	/**
	 * @param int $param
	 * @param CausedByLines $lines
	 */
	public function setParamLines( int $param, CausedByLines $lines ): void {
		$this->paramLines[$param] = $lines;
	}

	/**
	 * @param int $param
	 * @param CausedByLines $lines
	 */
	public function setVariadicParamLines( int $param, CausedByLines $lines ): void {
		$this->variadicParamIndex = $param;
		$this->variadicParamLines = $lines;
	}

	/**
	 * @param int $param
	 * @param string[] $lines
	 * @param Taintedness $taint
	 */
	public function addVariadicParamLines( int $param, array $lines, Taintedness $taint ): void {
		assert( !isset( $this->paramLines[$param] ) );
		$this->variadicParamIndex = $param;
		if ( !$this->variadicParamLines ) {
			$this->variadicParamLines = new CausedByLines();
		}
		foreach ( $lines as $line ) {
			$this->variadicParamLines->addLine( clone $taint, $line );
		}
	}

	/**
	 * @param int $param
	 * @return CausedByLines
	 * @todo Param lines should be split into preserved vs sink, like in FunctionTaintedness
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
	 * @param FunctionCausedByLines $other
	 */
	public function mergeWith( self $other ): void {
		$this->genericLines = $this->genericLines->asMergedWith( $other->genericLines );
		foreach ( $other->paramLines as $param => $lines ) {
			$this->paramLines[$param] = isset( $this->paramLines[$param] )
				? $this->paramLines[$param]->asMergedWith( $lines )
				: $lines;
		}
		if ( $other->variadicParamIndex !== null ) {
			$this->variadicParamLines = $this->variadicParamLines
				? $this->variadicParamLines->asMergedWith( $other->variadicParamLines )
				: $other->variadicParamLines;
		}
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
