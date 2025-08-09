<?php

class CausedByRedundant2 {
	public static function concatFirstAndStringifiedSecond( $first, $second ) {
		return "($first)" . self::stringify( $second );
	}

	public static function stringify( array $arr ) {
		$ret = '';
		foreach ( $arr as $key => $value ) {
			if ( rand() ) {
				$arrayValue = [];
				foreach ( $value as $k => $v ) {
					if ( is_string( $v ) ) {
						foreach ( explode( ' ', $v ) as $part ) {
							$arrayValue[] = $part;
						}
					} elseif ( $v ) {
						$arrayValue[] = $k;
					}
				}
				$value = implode( ' ', $arrayValue );
			}

			$encValue = htmlspecialchars( $value );
			$ret .= " $key=\"$encValue\"";
		}
		return $ret;
	}
}
