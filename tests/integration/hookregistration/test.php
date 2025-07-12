<?php

// Test for all kinds of hook registration

namespace HookRegistration;

class HookRegistration {
	public function register() {
		global $wgHooks;

		$wgHooks['ThisInstance'][] = $this;
		$wgHooks['DirectArrayCallable'][] = [ $this, 'handlerForDirectArrayCallable' ];
		$arrayCallableVar = [ $this, 'handlerForArrayCallableWithVar' ];
		$wgHooks['ArrayCallableWithVar'][] = $arrayCallableVar;
		$clos = function ( $x ) { // Unsafe handler
			echo $x;
		};
		$wgHooks['ClosureWithVal'][] = $clos;
		$wgHooks['ClosureDirect'][] = function ( $x ) { echo $x; }; // Unsafe handler
		$wgHooks['GlobalFunctionString'][] = '\HookRegistration\globalFunctionStringHandler';
		$wgHooks['StaticMethodString'][] = '\HookRegistration\HookRegistration::handlerForStaticMethodString';
		$wgHooks['NewSelf'][] = new self;
		$wgHooks['NewStatic'][] = new static;
		$wgHooks['NewClassName'][] = new HookRegistration;
		$methodName = 'handlerForArrayCallableWithVarMethodName';
		$wgHooks['ArrayCallableWithVarMethodName'][] = [ $this, $methodName ];
		$thisClass = __CLASS__;
		$wgHooks['NewClassVar'][] = new $thisClass;
		$wgHooks['ArrayCallableWithLateStaticBinding'][] = [ self::class, 'handlerForArrayCallableWithLateStaticBinding' ];
		$wgHooks['ArrayCallableWithClassName'][] = [ '\HookRegistration\HookRegistration', 'handlerForArrayCallableWithClassName' ];
		$wgHooks['FirstClassGlobalFunction'][] = firstClassGlobalFunctionHandler( ... );
		$wgHooks['FirstClassNonstaticMethod'][] = $this->handlerForFirstClassNonstaticMethod( ... );
		$wgHooks['FirstClassStaticMethod'][] = self::handlerForFirstClassStaticMethod( ... );
		$wgHooks['Noop'][] = '*no-op*'; // Hardcoded value of `HookContainer::NOOP`.
	}

	public function onThisInstance( $x ) {
		echo $x;
	}
	public function handlerForDirectArrayCallable( $x ) {
		echo $x;
	}
	public function handlerForArrayCallableWithVar( $x ) {
		echo $x;
	}
	public static function handlerForStaticMethodString( $x ) {
		echo $x;
	}
	public function onNewSelf( $x ) {
		echo $x;
	}
	public function onNewStatic( $x ) {
		echo $x;
	}
	public function onNewClassName( $x ) {
		echo $x;
	}
	public function handlerForArrayCallableWithVarMethodName( $x ) {
		echo $x;
	}
	public function onNewClassVar( $x ) {
		echo $x;
	}
	public static function handlerForArrayCallableWithLateStaticBinding( $x ) {
		echo $x;
	}
	public function handlerForArrayCallableWithClassName( $x ) {
		echo $x;
	}
	public function handlerForFirstClassNonstaticMethod( $x ) {
		echo $x;
	}
	public static function handlerForFirstClassStaticMethod( $x ) {
		echo $x;
	}
}

function globalFunctionStringHandler( $x ) {
	echo $x;
}
function firstClassGlobalFunctionHandler( $x ) {
	echo $x;
}

// These are all unsafe.
( new HookRunner )->onThisInstance( $_GET['foo'] );
( new HookRunner )->onDirectArrayCallable( $_GET['foo'] );
( new HookRunner )->onArrayCallableWithVar( $_GET['foo'] );
( new HookRunner )->onClosureWithVal( $_GET['foo'] );
( new HookRunner )->onClosureDirect( $_GET['foo'] );
( new HookRunner )->onGlobalFunctionString( $_GET['foo'] );
( new HookRunner )->onStaticMethodString( $_GET['foo'] );
( new HookRunner )->onNewSelf( $_GET['foo'] );
( new HookRunner )->onNewStatic( $_GET['foo'] );
( new HookRunner )->onNewClassName( $_GET['foo'] );
( new HookRunner )->onArrayCallableWithVarMethodName( $_GET['foo'] );
( new HookRunner )->onNewClassVar( $_GET['foo'] );
( new HookRunner )->onArrayCallableWithLateStaticBinding( $_GET['foo'] );
( new HookRunner )->onArrayCallableWithClassName( $_GET['foo'] );
( new HookRunner )->onFirstClassGlobalFunction( $_GET['foo'] );
( new HookRunner )->onFirstClassNonstaticMethod( $_GET['foo'] );
( new HookRunner )->onFirstClassStaticMethod( $_GET['foo'] );

// This one is safe
( new HookRunner )->onNoop( $_GET['foo'] );
