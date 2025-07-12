<?php

// Test attempts to register a hook handler that doesn't exist. Adapted from the "hookregistration" test.

class NonExistingHooks {
	public function register() {
		global $wgHooks;

		$wgHooks['ThisInstance'][] = $this; // $this->onThisInstance doesn't exist
		$wgHooks['DirectArrayCallable'][] = [ $this, 'doesNotExist' ];
		$arrayCallableVar = [ $this, 'doesNotExist' ];
		$wgHooks['ArrayCallableWithVar'][] = $arrayCallableVar;
		$wgHooks['GlobalFunctionString'][] = 'globalFunctionThatDoesNotExist';
		$wgHooks['StaticMethodString'][] = '\NonExistingHooks::classThatDoesNotExist';
		$wgHooks['NewSelf'][] = new self; // $this->onNewSelf doesn't exist
		$wgHooks['NewStatic'][] = new static; // $this->onNewStatic doesn't exist
		$wgHooks['NewClassName'][] = new NonExistingHooks(); // $this->onNewClassName doesn't exist
		$methodName = 'doesNotExist';
		$wgHooks['ArrayCallableWithVarMethodName'][] = [ $this, $methodName ];
		$thisClass = __CLASS__;
		$wgHooks['NewClassVar'][] = new $thisClass; // $this->onNewClassVar doesn't exist
		$wgHooks['ArrayCallableWithLateStaticBinding'][] = [ self::class, 'doesNotExist' ];
		$wgHooks['ArrayCallableWithClassName'][] = [ '\NonExistingHooks', 'doesNotExist' ];
		$wgHooks['FirstClassGlobalFunction'][] = globalFunctionThatDoesNotExist( ... );
		$wgHooks['FirstClassNonstaticMethod'][] = $this->doesNotExist( ... );
		$wgHooks['FirstClassStaticMethod'][] = self::doesNotExist( ... );
		$wgHooks['InvalidFQSEN'][] = '--**/\\**--';
		$wgHooks['InvalidType'][] = false;
		$wgHooks['InvalidArray'][] = [];
	}
}
