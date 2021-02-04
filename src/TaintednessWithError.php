<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

/**
 * Object that encapsulates a Taintedness and a list of lines that caused it
 */
class TaintednessWithError {
	/** @var Taintedness */
	private $taintedness;
	/**
	 * @var array
	 * @phan-var list<array{0:Taintedness,1:string}>
	 */
	private $error;

	/**
	 * @param Taintedness $taintedness
	 * @param array $error
	 * @phan-param list<array{0:Taintedness,1:string}> $error
	 */
	public function __construct( Taintedness $taintedness, array $error ) {
		$this->taintedness = $taintedness;
		$this->error = $error;
	}

	/**
	 * @return Taintedness
	 */
	public function getTaintedness() : Taintedness {
		return $this->taintedness;
	}

	/**
	 * @return array
	 * @phan-return list<array{0:Taintedness,1:string}>
	 */
	public function getError() : array {
		return $this->error;
	}
}
