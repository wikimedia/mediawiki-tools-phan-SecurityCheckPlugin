<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use Phan\Library\Set;

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

	/** @var Set */
	private $methodLinks;

	/**
	 * @param Taintedness $taintedness
	 * @param array $error
	 * @param Set $methodLinks
	 * @phan-param list<array{0:Taintedness,1:string}> $error
	 */
	public function __construct( Taintedness $taintedness, array $error, Set $methodLinks ) {
		$this->taintedness = $taintedness;
		$this->error = $error;
		$this->methodLinks = $methodLinks;
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

	/**
	 * @return Set
	 */
	public function getMethodLinks() : Set {
		return $this->methodLinks;
	}
}
