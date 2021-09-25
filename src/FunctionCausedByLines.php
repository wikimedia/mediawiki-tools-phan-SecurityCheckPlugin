<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

class FunctionCausedByLines {
	/** @var CausedByLines */
	private $genericLines;
	/** @var CausedByLines[] */
	private $paramSinkLines = [];
	/** @var CausedByLines[] */
	private $paramPreservedLines = [];
	/** @var int|null Index of a variadic parameter, if any */
	private $variadicParamIndex;
	/** @var CausedByLines|null */
	private $variadicParamSinkLines;
	/** @var CausedByLines|null */
	private $variadicParamPreservedLines;

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
	 * @param MethodLinks|null $links
	 */
	public function addGenericLines( array $lines, Taintedness $taint, MethodLinks $links = null ): void {
		foreach ( $lines as $line ) {
			$this->genericLines->addLine( clone $taint, $line, $links ? clone $links : null );
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
	public function addParamSinkLines( int $param, array $lines, Taintedness $taint ): void {
		assert( $param !== $this->variadicParamIndex );
		if ( !isset( $this->paramSinkLines[$param] ) ) {
			$this->paramSinkLines[$param] = new CausedByLines();
		}
		foreach ( $lines as $line ) {
			$this->paramSinkLines[$param]->addLine( clone $taint, $line );
		}
	}

	/**
	 * @param int $param
	 * @param string[] $lines
	 * @param Taintedness $taint
	 * @param MethodLinks|null $links
	 */
	public function addParamPreservedLines(
		int $param,
		array $lines,
		Taintedness $taint,
		MethodLinks $links = null
	): void {
		assert( $param !== $this->variadicParamIndex );
		if ( !isset( $this->paramPreservedLines[$param] ) ) {
			$this->paramPreservedLines[$param] = new CausedByLines();
		}
		foreach ( $lines as $line ) {
			$this->paramPreservedLines[$param]->addLine( clone $taint, $line, $links ? clone $links : null );
		}
	}

	/**
	 * @param int $param
	 * @param CausedByLines $lines
	 */
	public function setParamSinkLines( int $param, CausedByLines $lines ): void {
		$this->paramSinkLines[$param] = $lines;
	}

	/**
	 * @param int $param
	 * @param CausedByLines $lines
	 */
	public function setParamPreservedLines( int $param, CausedByLines $lines ): void {
		$this->paramPreservedLines[$param] = $lines;
	}

	/**
	 * @param int $param
	 * @param CausedByLines $lines
	 */
	public function setVariadicParamSinkLines( int $param, CausedByLines $lines ): void {
		$this->variadicParamIndex = $param;
		$this->variadicParamSinkLines = $lines;
	}

	/**
	 * @param int $param
	 * @param CausedByLines $lines
	 */
	public function setVariadicParamPreservedLines( int $param, CausedByLines $lines ): void {
		$this->variadicParamIndex = $param;
		$this->variadicParamPreservedLines = $lines;
	}

	/**
	 * @param int $param
	 * @param string[] $lines
	 * @param Taintedness $taint
	 */
	public function addVariadicParamSinkLines(
		int $param,
		array $lines,
		Taintedness $taint
	): void {
		assert( !isset( $this->paramSinkLines[$param] ) && !isset( $this->paramPreservedLines[$param] ) );
		$this->variadicParamIndex = $param;
		if ( !$this->variadicParamSinkLines ) {
			$this->variadicParamSinkLines = new CausedByLines();
		}
		foreach ( $lines as $line ) {
			$this->variadicParamSinkLines->addLine( clone $taint, $line );
		}
	}

	/**
	 * @param int $param
	 * @param string[] $lines
	 * @param Taintedness $taint
	 * @param MethodLinks|null $links
	 */
	public function addVariadicParamPreservedLines(
		int $param,
		array $lines,
		Taintedness $taint,
		MethodLinks $links = null
	): void {
		assert( !isset( $this->paramSinkLines[$param] ) && !isset( $this->paramPreservedLines[$param] ) );
		$this->variadicParamIndex = $param;
		if ( !$this->variadicParamPreservedLines ) {
			$this->variadicParamPreservedLines = new CausedByLines();
		}
		foreach ( $lines as $line ) {
			$this->variadicParamPreservedLines->addLine( clone $taint, $line, $links ? clone $links : null );
		}
	}

	/**
	 * @param int $param
	 * @return CausedByLines
	 */
	public function getParamSinkLines( int $param ): CausedByLines {
		if ( isset( $this->paramSinkLines[$param] ) ) {
			return $this->paramSinkLines[$param];
		}
		if (
			$this->variadicParamIndex !== null && $param >= $this->variadicParamIndex &&
			$this->variadicParamSinkLines
		) {
			return $this->variadicParamSinkLines;
		}
		return new CausedByLines();
	}

	/**
	 * @param int $param
	 * @return CausedByLines
	 */
	public function getParamPreservedLines( int $param ): CausedByLines {
		if ( isset( $this->paramPreservedLines[$param] ) ) {
			return $this->paramPreservedLines[$param];
		}
		if (
			$this->variadicParamIndex !== null && $param >= $this->variadicParamIndex &&
			$this->variadicParamPreservedLines
		) {
			return $this->variadicParamPreservedLines;
		}
		return new CausedByLines();
	}

	/**
	 * @param FunctionCausedByLines $other
	 * @param FunctionTaintedness $funcTaint To check NO_OVERRIDE
	 */
	public function mergeWith( self $other, FunctionTaintedness $funcTaint ): void {
		if ( $funcTaint->canOverrideOverall() ) {
			$this->genericLines = $this->genericLines->asMergedWith( $other->genericLines );
		}
		foreach ( $other->paramSinkLines as $param => $lines ) {
			if ( $funcTaint->canOverrideNonVariadicParam( $param ) ) {
				$this->paramSinkLines[$param] = isset( $this->paramSinkLines[$param] )
					? $this->paramSinkLines[$param]->asMergedWith( $lines )
					: $lines;
			}
		}
		foreach ( $other->paramPreservedLines as $param => $lines ) {
			if ( $funcTaint->canOverrideNonVariadicParam( $param ) ) {
				$this->paramPreservedLines[$param] = isset( $this->paramPreservedLines[$param] )
					? $this->paramPreservedLines[$param]->asMergedWith( $lines )
					: $lines;
			}
		}
		$variadicIndex = $other->variadicParamIndex;
		if ( $variadicIndex !== null && $funcTaint->canOverrideVariadicParam() ) {
			$this->variadicParamIndex = $variadicIndex;
			$sinkVariadic = $other->variadicParamSinkLines;
			if ( $sinkVariadic ) {
				$this->variadicParamSinkLines = $this->variadicParamSinkLines
					? $this->variadicParamSinkLines->asMergedWith( $sinkVariadic )
					: $sinkVariadic;
			}
			$preserveVariadic = $other->variadicParamPreservedLines;
			if ( $preserveVariadic ) {
				$this->variadicParamPreservedLines = $this->variadicParamPreservedLines
					? $this->variadicParamPreservedLines->asMergedWith( $preserveVariadic )
					: $preserveVariadic;
			}
		}
	}

	public function __clone() {
		$this->genericLines = clone $this->genericLines;
		foreach ( $this->paramSinkLines as $k => $pLines ) {
			$this->paramSinkLines[$k] = clone $pLines;
		}
		if ( $this->variadicParamSinkLines ) {
			$this->variadicParamSinkLines = clone $this->variadicParamSinkLines;
		}
		if ( $this->variadicParamPreservedLines ) {
			$this->variadicParamPreservedLines = clone $this->variadicParamPreservedLines;
		}
	}

	/**
	 * @return string
	 */
	public function toString(): string {
		$str = "{\nGeneric: " . $this->genericLines->toDebugString() . ",\n";
		foreach ( $this->paramSinkLines as $par => $lines ) {
			$str .= "$par (sink): " . $lines->toDebugString() . ",\n";
		}
		foreach ( $this->paramPreservedLines as $par => $lines ) {
			$str .= "$par (preserved): " . $lines->toDebugString() . ",\n";
		}
		if ( $this->variadicParamSinkLines ) {
			$str .= "...{$this->variadicParamIndex} (sink): " . $this->variadicParamSinkLines->toDebugString() . "\n";
		}
		if ( $this->variadicParamPreservedLines ) {
			$str .= "...{$this->variadicParamIndex} (preserved): " .
				$this->variadicParamPreservedLines->toDebugString() . "\n";
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
