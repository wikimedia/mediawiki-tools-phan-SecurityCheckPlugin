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
}
