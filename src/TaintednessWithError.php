<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

/**
 * Object that encapsulates a Taintedness and a list of lines that caused it
 */
class TaintednessWithError {
	/** @var Taintedness */
	private $taintedness;
	/**
	 * @var CausedByLines
	 */
	private $error;

	/** @var MethodLinks */
	private $methodLinks;

	/**
	 * @param Taintedness $taintedness
	 * @param CausedByLines $error
	 * @param MethodLinks $methodLinks
	 */
	public function __construct( Taintedness $taintedness, CausedByLines $error, MethodLinks $methodLinks ) {
		$this->taintedness = $taintedness;
		$this->error = $error;
		$this->methodLinks = $methodLinks;
	}

	public static function emptySingleton(): self {
		static $singleton;
		if ( !$singleton ) {
			$singleton = new self(
				Taintedness::safeSingleton(),
				CausedByLines::emptySingleton(),
				MethodLinks::emptySingleton()
			);
		}
		return $singleton;
	}

	public static function unknownSingleton(): self {
		static $singleton;
		if ( !$singleton ) {
			$singleton = new self(
				Taintedness::unknownSingleton(),
				CausedByLines::emptySingleton(),
				MethodLinks::emptySingleton()
			);
		}
		return $singleton;
	}

	/**
	 * @return Taintedness
	 */
	public function getTaintedness(): Taintedness {
		return $this->taintedness;
	}

	/**
	 * @return CausedByLines
	 */
	public function getError(): CausedByLines {
		return $this->error;
	}

	/**
	 * @return MethodLinks
	 */
	public function getMethodLinks(): MethodLinks {
		return $this->methodLinks;
	}

	/**
	 * @param self $other
	 * @return self
	 */
	public function asMergedWith( self $other ): self {
		$ret = clone $this;
		$ret->taintedness = $ret->taintedness->asMergedWith( $other->taintedness );
		$ret->error = $ret->error->asMergedWith( $other->error );
		$ret->methodLinks = $ret->methodLinks->asMergedWith( $other->methodLinks );
		return $ret;
	}

	public function __clone() {
		$this->taintedness = clone $this->taintedness;
		$this->error = clone $this->error;
		$this->methodLinks = clone $this->methodLinks;
	}
}
