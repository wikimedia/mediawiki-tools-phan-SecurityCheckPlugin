<?php

use MediaWiki\Parser\Parser;

$parser = new Parser;
$parser->setHook( 'string1', 'testInferParserTagHookParamTypes' );

function testInferParserTagHookParamTypes( $content, $attribs, $parser, $ppframe ) {
	// Verify that we're telling phan what's the type of these two parameters, even without a doc comment or type declarations
	'@phan-debug-var $parser,$ppframe';
}
