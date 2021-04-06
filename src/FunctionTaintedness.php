<?php declare( strict_types=1 );

namespace SecurityCheckPlugin;

use Closure;
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
	 * Set the taint for a given param
	 *
	 * @param int $param
	 * @param Taintedness $taint
	 */
	public function setParamTaint( int $param, Taintedness $taint ) : void {
		$this->paramTaints[$param] = $taint;
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
	 * Apply a callback to all taint values (in-place)
	 * @param Closure $fn
	 * @phan-param Closure( Taintedness ):void $fn
	 */
	public function map( Closure $fn ) : void {
		foreach ( $this->paramTaints as $taint ) {
			$fn( $taint );
		}
		$fn( $this->overall );
		if ( $this->variadicParamTaint ) {
			$fn( $this->variadicParamTaint );
		}
	}

	/**
	 * Sometimes we don't want NO_OVERRIDE. This is primarily used to ensure that NO_OVERRIDE
	 * doesn't propagate into other variables.
	 *
	 * Note that this always creates a clone of $this.
	 *
	 * @param bool $clear Whether to clear it or not
	 * @return $this
	 */
	public function withMaybeClearNoOverride( bool $clear ) : self {
		$ret = clone $this;
		if ( !$clear ) {
			return $ret;
		}
		$ret->overall->remove( SecurityCheckPlugin::NO_OVERRIDE );
		foreach ( $ret->paramTaints as $t ) {
			$t->remove( SecurityCheckPlugin::NO_OVERRIDE );
		}
		if ( $ret->variadicParamTaint ) {
			$ret->variadicParamTaint->remove( SecurityCheckPlugin::NO_OVERRIDE );
		}
		return $ret;
	}

	/**
	 * Check whether NO_OVERRIDE is set anywhere in this object.
	 *
	 * @return bool
	 */
	public function hasNoOverride() : bool {
		if ( $this->overall->has( SecurityCheckPlugin::NO_OVERRIDE ) ) {
			return true;
		}
		foreach ( $this->paramTaints as $t ) {
			if ( $t->has( SecurityCheckPlugin::NO_OVERRIDE ) ) {
				return true;
			}
		}
		if ( $this->variadicParamTaint && $this->variadicParamTaint->has( SecurityCheckPlugin::NO_OVERRIDE ) ) {
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
		$str = "[\n\toverall: " . $this->overall->toString( '    ' ) . ",\n";
		foreach ( $this->paramTaints as $par => $taint ) {
			$str .= "\t$par: " . $taint->toString() . ",\n";
		}
		if ( $this->variadicParamTaint ) {
			$str .= "\t...{$this->variadicParamIndex}: " . $this->variadicParamTaint->toString() . ",\n";
		}
		return "$str]";
	}

	/**
	 * @return string
	 */
	public function __toString() : string {
		return $this->toString();
	}
}
