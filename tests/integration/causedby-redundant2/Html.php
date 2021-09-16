<?php

class Html {
	// Rename in case we'll hardcode taintedness for openElement
	public static function openElementXXX( $element, $attribs = [] ) {
		return "<$element" . self::expandAttributesXXX( $attribs );
	}

	// Rename in case we'll hardcode taintedness for expandAttributes
	public static function expandAttributesXXX( array $attribs ) {
		$ret = '';
		foreach ( $attribs as $key => $value ) {
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
