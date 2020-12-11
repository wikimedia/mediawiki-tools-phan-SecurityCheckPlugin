<?php
namespace MyNS;

use \Parser;
use Wikimedia\Rdbms\MysqlDatabase;

class SomeClass {
	public function register() {
		$parser = new Parser;

		$indirectClosure = function ( Parser $parser, $arg ) {
			return [ $arg, 'isHTML' => true ];
		};

		$parser->setFunctionHook( 'something', [ __CLASS__, 'bar' ] );
		$parser->setFunctionHook( 'something', [ __CLASS__, 'baz' ] );

		$parser->setFunctionHook( 'afunc', 'wfSomeFunc' );
		$parser->setFunctionHook( 'two', function ( Parser $parser, $arg1 ) {
				return [ $_GET['d'], 'isHTML' => true ];
		} );
		$parser->setFunctionHook( 'safe', function ( Parser $parser, $arg1 ) {
			return [ 'SAFE', 'isHTML' => true ];
		} );

		$parser->setFunctionHook( 'three', $indirectClosure );
	}

	public function bar( Parser $parser, $arg1, $arg2 ) {
		$out = '';
		$db = new MysqlDatabase;
		$res = $db->query( "Select foo from someTable WHERE field = '$arg2' LIMIT 1" );
		foreach ( $res as $row ) {
			$out .= $row->foo;
		}
		$out .= $arg1;

		return $out;
	}

	public function baz( Parser $parser, $arg1 ) {
		return [ $arg1, 'isHTML' => true ];
	}
}

function wfSomeFunc() {
		// This should not trigger because its in wrong NS
		return [ $_GET['d'], 'isHTML' => true ];
}
