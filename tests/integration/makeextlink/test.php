<?php

namespace MediaWiki\Linker;

class Linker {
	public static function makeExternalLink( $url, $text, $escape = true,
		$linktype = '', $attribs = [], $title = null
	) {
		return 'f';
	}
}

// safe
echo Linker::makeExternalLink(
	'http://example.com',
	$_GET['evil']
);

// safe
echo Linker::makeExternalLink(
	'http://example.com',
	$_GET['evil'],
	true
);

// unsafe
echo Linker::makeExternalLink(
	'http://example.com',
	$_GET['evil'],
	false
);

// unsafe
echo Linker::makeExternalLink(
	'http://example.com',
	$_GET['evil'],
	0
);

// unsafe
$evil = $_GET['evil'];
echo Linker::makeExternalLink(
	'http://example.com',
	$evil,
	false
);
