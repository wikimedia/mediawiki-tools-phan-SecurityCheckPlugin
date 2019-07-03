<?php
class Html {
	/**
	 * @param string $element The element's name, e.g., 'a'
	 * @param array $attribs Associative array of attributes, e.g., [
	 *   'href' => 'https://www.mediawiki.org/' ]. See expandAttributes() for
	 *   further documentation.
	 * @param string $contents The raw HTML contents of the element: *not*
	 *   escaped!
	 * @return string Raw HTML
	 */
	public static function rawElement( $element, $attribs = [], $contents = '' ) {
		return "<$element>$contents</$element>";
	}

	/**
	 * Identical to rawElement(), but HTML-escapes $contents (like
	 * Xml::element()).
	 *
	 * @param string $element Name of the element, e.g., 'a'
	 * @param array $attribs Associative array of attributes, e.g., [
	 *   'href' => 'https://www.mediawiki.org/' ]. See expandAttributes() for
	 *   further documentation.
	 * @param string $contents
	 *
	 * @return string
	 */
	public static function element( $element, $attribs = [], $contents = '' ) {
		return self::rawElement( $element, $attribs, strtr( $contents, [
			// There's no point in escaping quotes, >, etc. in the contents of
			// elements.
			'&' => '&amp;',
			'<' => '&lt;'
		] ) );
	}
}