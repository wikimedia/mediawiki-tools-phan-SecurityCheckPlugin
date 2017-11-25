<?php

class Parser {

	const MARKER_SUFFIX = "-QINU`\"'\x7f";
	const MARKER_PREFIX = "\x7f'\"`UNIQ-";
	/** @var int */
	private $mMarkerIndex = 0;
	/** @var StripState */
	public $mStripState;
	public function setFunctionHook( $id, callable $cb, $opts = 0 ) {
	}

	public function __construct() {
		$this->mStripState = new StripState;
	}

	public function insertStripItem( $text ) {
		$marker = self::MARKER_PREFIX . "-item-{$this->mMarkerIndex}-" . self::MARKER_SUFFIX;
		$this->mMarkerIndex++;
		$this->mStripState->addGeneral( $marker, $text );
		return $marker;
	}

}

class StripState {
	protected $prefix;
	protected $data;
	protected $regex;

	protected $tempType, $tempMergePrefix;
	protected $circularRefGuard;
	protected $recursionLevel = 0;

	const UNSTRIP_RECURSION_LIMIT = 20;
	protected function addItem( $type, $marker, $value ) {
		if ( !preg_match( $this->regex, $marker, $m ) ) {
			throw new Exception( "Invalid marker: $marker" );
		}

		$this->data[$type][$m[1]] = $value;
	}

	/**
	 * Add a nowiki strip item
	 * @param string $marker
	 * @param string $value
	 */
	public function addNoWiki( $marker, $value ) {
		$this->addItem( 'nowiki', $marker, $value );
	}

	/**
	 * @param string $marker
	 * @param string $value
	 */
	public function addGeneral( $marker, $value ) {
		$this->addItem( 'general', $marker, $value );
	}
}
