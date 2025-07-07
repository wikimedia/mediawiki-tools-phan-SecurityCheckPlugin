<?php
namespace MyNS;

use \Parser;
use Wikimedia\Rdbms\MysqlDatabase;

class SomeClass {
	private $ownInstance;

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
		$parser->setFunctionHook( 'unsafe', [ SomeClass::class, 'unsafeHook' ] );
		$parser->setFunctionHook( 'unsafe2', [ self::class, 'unsafeHook2' ] );
		$parser->setFunctionHook( 'unsafe3', [ $this, 'unsafeHook3' ] );
		$this->ownInstance = $this;
		$parser->setFunctionHook( 'unsafe4', [ $this->ownInstance, 'unsafeHook4' ] );

		$parser->setFunctionHook( 'nocrash1', [ $this, 'testNoCrash1' ] );
		$parser->setFunctionHook( 'nocrash2', [ $this, 'testNoCrash2' ] );
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

	public function unsafeHook( Parser $parser, $arg ) {
		return [ $arg, 'isHTML' => true ];
	}

	public function unsafeHook2( Parser $parser, $arg ) {
		return [ $arg, 'isHTML' => true ];
	}

	public function unsafeHook3( Parser $parser, $arg ) {
		return [ $arg, 'isHTML' => true ];
	}

	public function unsafeHook4( Parser $parser, $arg ) {
		return [ $arg, 'isHTML' => true ];
	}

	public function testNoCrash1() {
		// Make sure this doesn't crash due to the unpacking.
		return [ ...$GLOBALS['return_data'], 'isHTML' => true ];
	}
	public function testNoCrash2() {
		// Make sure this doesn't crash due to the unpacking.
		return [ 'isHTML' => true, ...$GLOBALS['return_data'] ];
	}
}

function wfSomeFunc() {
		// This should not trigger because its in wrong NS
		return [ $_GET['d'], 'isHTML' => true ];
}
