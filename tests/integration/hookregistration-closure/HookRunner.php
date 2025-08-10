<?php

namespace HookRegistrationClosure;

interface ReplaceArgWithUnsafeHook {
	public function onReplaceArgWithUnsafe( &$arg );
}
interface ReplaceArgWithUsedLocalUnsafeHook {
	public function onReplaceArgWithUsedLocalUnsafe( &$arg );
}
interface ReplaceArgWithUsedGlobalUnsafeHook {
	public function onReplaceArgWithUsedGlobalUnsafe( &$arg );
}

class HookRunner implements ReplaceArgWithUnsafeHook, ReplaceArgWithUsedLocalUnsafeHook, ReplaceArgWithUsedGlobalUnsafeHook {
	public function onReplaceArgWithUnsafe( &$arg ) {
	}

	public function onReplaceArgWithUsedLocalUnsafe( &$arg ) {
	}

	public function onReplaceArgWithUsedGlobalUnsafe( &$arg ) {
	}
}
