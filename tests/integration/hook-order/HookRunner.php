<?php

namespace HookOrder;

interface SomethingHook {
	public function onSomething( &$arg1, &$arg2 );
}

class HookRunner implements SomethingHook {
	public function onSomething( &$arg1, &$arg2 ) {
	}
}
