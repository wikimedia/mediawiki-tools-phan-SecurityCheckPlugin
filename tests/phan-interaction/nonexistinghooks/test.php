<?php

// Test attempts to register a hook handler that doesn't exist

class NonExistingHooks {
	public function register() {
		global $wgHooks;
		$wgHooks['Hook1'][] = $this; // $this->onHook1 doesn't exist
		$wgHooks['Hook2'][] = [ $this, 'handler2' ]; // $this->handler2 doesn't exist
		$thirdHandler = [ $this, 'handler3' ];
		$wgHooks['Hook3'][] = $thirdHandler;// $this->handler2 doesn't exist
		$wgHooks['Hook4'][] = 'globalHandler';// globalHandler doesn't exist
		$wgHooks['Hook5'][] = 'HookRegistration::staticHandler';// self::staticHandler doesn't exist
		$wgHooks['Hook6'][] = new self;// $this->onHook6 doesn't exist
		$wgHooks['Hook7'][] = new static;// $this->onHook7 doesn't exist
		$wgHooks['Hook8'][] = new NonExistingHooks;// $this->onHook8 doesn't exist
		$wgHooks['Hook9'][] = [ new NonExistingHooks, 'onHook9', $_GET['unsafe'] ];// $this->onHook9 doesn't exist
		$wgHooks['Hook10'][] = [ [ $this, 'myHook10Handler' ] ];// $this->myHook10Handler doesn't exist
		$wgHooks['Hook11'][] = [ [ $this, 'myHook11Handler' ], $_GET['unsafe'] ];// $this->myHook11Handler doesn't exist
		$wgHooks['Hook12'][] = [ 'HookRegistration::staticHandler2' ];// self::staticHandler2 doesn't exist
		$methodName = 'hook13Handler';
		$wgHooks['Hook13'][] = [ $this, $methodName ];// $this->hook13Handler doesn't exist
		$thisClass = __CLASS__;
		$wgHooks['Hook14'][] = new $thisClass;// $this->onHook14 doesn't exist
	}
}
