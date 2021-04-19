<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use LogicException;

/**
 * Value object used to store taintedness of functions.
 * The $overall prop specifies what taint the function returns
 *   irrespective of its arguments.
 * The numeric keys in $paramTaints are how each individual argument affects taint.
 *
 *   For 'overall': the EXEC flags mean a call does evil regardless of args
 *                  the TAINT flags are what taint the output has
 *   For numeric keys: EXEC flags for what taints are unsafe here
 *                     TAINT flags for what taint gets passed through func.
 * As a special case, if the overall key has self::PRESERVE_TAINT
 * then any unspecified keys behave like they are self::YES_TAINT
 *
 * If func has an arg that is missing from $paramTaints, then it should be
 * treated as NO_TAINT if its a number or bool, and YES_TAINT otherwise.
 * The $overall taintedness must always be set.
 */
class FunctionTaintedness {
	/** @var Taintedness Overall taintedness of the func */
	private $overall;
	/** @var Taintedness[] Taintedness for each param */
	public $paramTaints = [];
	/** @var int|null Index of a variadic parameter, if any */
	private $variadicParamIndex;
	/** @var Taintedness|null Taintedness for a variadic parameter, if any */
	private $variadicParamTaint;
	/** @var int Special overall flags */
	private $overallFlags = 0;
	/** @var int[] Special flags for parameters */
	private $paramFlags = [];
	/** @var int */
	private $variadicParamFlags = 0;

	/**
	 * @param Taintedness $overall
	 */
	public function __construct( Taintedness $overall ) {
		$this->overall = $overall;
	}

	/**
	 * @param Taintedness $val
	 */
	public function setOverall( Taintedness $val ) : void {
		$this->overall = $val;
	}

	/**
	 * @param int $flags
	 */
	public function addOverallFlags( int $flags ) : void {
		$this->overallFlags |= $flags;
	}

	/**
	 * Get a copy of the overall taint
	 *
	 * @return Taintedness
	 */
	public function getOverall() : Taintedness {
		if ( $this->overall === null ) {
			throw new LogicException( 'Found null overall' );
		}
		return clone $this->overall;
	}

	/**
	 * @return int
	 */
	public function getOverallFlags() : int {
		return $this->overallFlags;
	}

	/**
	 * Set the taint for a given param
	 *
	 * @param int $param
	 * @param Taintedness $taint
	 */
	public function setParamTaint( int $param, Taintedness $taint ) : void {
		$this->paramTaints[$param] = $taint;
	}

	/**
	 * @param int $param
	 * @param int $flags
	 */
	public function addParamFlags( int $param, int $flags ) : void {
		$this->paramFlags[$param] = ( $this->paramFlags[$param] ?? 0 ) | $flags;
	}

	/**
	 * @param int $index
	 * @param Taintedness $taint
	 */
	public function setVariadicParamTaint( int $index, Taintedness $taint ) : void {
		$this->variadicParamIndex = $index;
		$this->variadicParamTaint = $taint;
	}

	/**
	 * @param int $flags
	 */
	public function addVariadicParamFlags( int $flags ) : void {
		$this->variadicParamFlags |= $flags;
	}

	/**
	 * Get a clone of the taintedness of the given param, and NO_TAINT if not set.
	 *
	 * @param int $param
	 * @return Taintedness
	 */
	public function getParamTaint( int $param ) : Taintedness {
		if ( isset( $this->paramTaints[$param] ) ) {
			return clone $this->paramTaints[$param];
		}
		if ( $this->variadicParamIndex !== null && $param >= $this->variadicParamIndex ) {
			return clone $this->variadicParamTaint;
		}
		return Taintedness::newSafe();
	}

	/**
	 * @param int $param
	 * @return int
	 */
	public function getParamFlags( int $param ) : int {
		if ( isset( $this->paramFlags[$param] ) ) {
			return $this->paramFlags[$param];
		}
		if ( $this->variadicParamIndex !== null && $param >= $this->variadicParamIndex ) {
			return $this->variadicParamFlags;
		}
		return 0;
	}

	/**
	 * @return Taintedness|null
	 */
	public function getVariadicParamTaint() : ?Taintedness {
		return $this->variadicParamTaint;
	}

	/**
	 * @return int|null
	 */
	public function getVariadicParamIndex() : ?int {
		return $this->variadicParamIndex;
	}

	/**
	 * @return int
	 */
	public function getVariadicParamFlags() : int {
		return $this->variadicParamFlags;
	}

	/**
	 * Get the *keys* of the params for which we have data, excluding variadic parameters
	 *
	 * @return int[]
	 */
	public function getParamKeysNoVariadic() : array {
		return array_keys( $this->paramTaints );
	}

	/**
	 * Check whether we have taint data for the given param
	 *
	 * @param int $param
	 * @return bool
	 */
	public function hasParam( int $param ) : bool {
		if ( isset( $this->paramTaints[$param] ) ) {
			return true;
		}
		if ( $this->variadicParamIndex !== null && $param >= $this->variadicParamIndex ) {
			return true;
		}
		return false;
	}

	/**
	 * Make sure to clone properties when cloning the instance
	 */
	public function __clone() {
		$this->overall = clone $this->overall;
		foreach ( $this->paramTaints as $k => $e ) {
			$this->paramTaints[$k] = clone $e;
		}
		if ( $this->variadicParamTaint ) {
			$this->variadicParamTaint = clone $this->variadicParamTaint;
		}
	}

	/**
	 * @return string
	 */
	public function toString() : string {
		$str = "[\n\toverall: " . $this->overall->toString( '    ' ) .
			self::flagsToString( $this->overallFlags ) . ",\n";
		foreach ( $this->paramTaints as $par => $taint ) {
			$str .= "\t$par: " . $taint->toString() . self::flagsToString( $this->paramFlags[$par] ?? 0 ) . ",\n";
		}
		if ( $this->variadicParamTaint ) {
			$str .= "\t...{$this->variadicParamIndex}: " . $this->variadicParamTaint->toString() .
				self::flagsToString( $this->variadicParamFlags ) . ",\n";
		}
		return "$str]";
	}

	/**
	 * @param int $flags
	 * @return string
	 */
	private static function flagsToString( int $flags ) : string {
		$bits = [];
		if ( $flags & SecurityCheckPlugin::NO_OVERRIDE ) {
			$bits[] = 'no override';
		}
		if ( $flags & SecurityCheckPlugin::RAW_PARAM ) {
			$bits[] = 'raw param';
		}
		return $bits ? ' (' . implode( ', ', $bits ) . ')' : '';
	}

	/**
	 * @return string
	 */
	public function __toString() : string {
		return $this->toString();
	}
}
