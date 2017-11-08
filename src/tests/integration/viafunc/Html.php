<?php

// Stub html class.

class Html {


	public static function element( $element, $attribs = [], $contents = '' ) {
		return self::rawElement( $element, $attribs, strtr( $contents, [
			// There's no point in escaping quotes, >, etc. in the contents of
			// elements.
			'&' => '&amp;',
			'<' => '&lt;'
		] ) );
	}

	public static function rawElement( $element, $attribs = [], $content = '' ) {
		return 'placeholder';
	}

 	public static function hidden( $name, $value, array $attribs = [] ) {
		return self::input( $name, $value, 'hidden', $attribs );
 	}

	/**
	 * Convenience function to produce an "<input>" element.  This supports the
	 * new HTML5 input types and attributes.
	 *
	 * @param string $name Name attribute
	 * @param string $value Value attribute
	 * @param string $type Type attribute
	 * @param array $attribs Associative array of miscellaneous extra
	 *   attributes, passed to Html::element()
	 * @return string Raw HTML 
	 */   
	public static function input( $name, $value = '', $type = 'text', array $attribs = [] ) {
		$attribs['type'] = $type;
		$attribs['value'] = $value;
		$attribs['name'] = $name;
		return self::element( 'input', $attribs );
	}
}
