<?php

namespace HookOrder;

interface DoesNotClearPreviousTaintHook {
	public function onDoesNotClearPreviousTaint( &$arg1, &$arg2 );
}

interface AllTaintTypesAreMergedHook {
	public function onAllTaintTypesAreMerged( &$arg1, &$arg2 );
}

class HookRunner implements DoesNotClearPreviousTaintHook, AllTaintTypesAreMergedHook {
	public function onDoesNotClearPreviousTaint( &$arg1, &$arg2 ) {
	}
	public function onAllTaintTypesAreMerged( &$arg1, &$arg2 ) {
	}
}
