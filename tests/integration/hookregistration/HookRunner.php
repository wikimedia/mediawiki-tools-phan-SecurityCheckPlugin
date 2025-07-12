<?php

namespace HookRegistration;

interface ThisInstanceHook {
	public function onThisInstance( $arg );
}
interface DirectArrayCallableHook {
	public function onDirectArrayCallable( $arg );
}
interface ArrayCallableWithVarHook {
	public function onArrayCallableWithVar( $arg );
}
interface ClosureWithValHook {
	public function onClosureWithVal( $arg );
}
interface ClosureDirectHook {
	public function onClosureDirect( $arg );
}
interface GlobalFunctionStringHook {
	public function onGlobalFunctionString( $arg );
}
interface StaticMethodStringHook {
	public function onStaticMethodString( $arg );
}
interface NewSelfHook {
	public function onNewSelf( $arg );
}
interface NewStaticHook {
	public function onNewStatic( $arg );
}
interface NewClassNameHook {
	public function onNewClassName( $arg );
}
interface ArrayCallableWithVarMethodNameHook {
	public function onArrayCallableWithVarMethodName( $arg );
}
interface NewClassVarHook {
	public function onNewClassVar( $arg );
}
interface ArrayCallableWithLateStaticBindingHook {
	public function onArrayCallableWithLateStaticBinding( $arg );
}
interface ArrayCallableWithClassNameHook {
	public function onArrayCallableWithClassName( $arg );
}
interface FirstClassGlobalFunctionHook {
	public function onFirstClassGlobalFunction( $arg );
}
interface FirstClassNonstaticMethodHook {
	public function onFirstClassNonstaticMethod( $arg );
}
interface FirstClassStaticMethodHook {
	public function onFirstClassStaticMethod( $arg );
}
interface NoopHook {
	public function onNoop( $arg );
}

class HookRunner implements
	ThisInstanceHook, DirectArrayCallableHook, ArrayCallableWithVarHook, ClosureWithValHook,
	ClosureDirectHook, GlobalFunctionStringHook, StaticMethodStringHook, NewSelfHook, NewStaticHook, NewClassNameHook,
	ArrayCallableWithVarMethodNameHook, NewClassVarHook, ArrayCallableWithLateStaticBindingHook,
	ArrayCallableWithClassNameHook, FirstClassGlobalFunctionHook, FirstClassNonstaticMethodHook,
	FirstClassStaticMethodHook, NoopHook
{
	public function onThisInstance( $arg ) {
	}

	public function onDirectArrayCallable( $arg ) {
	}

	public function onArrayCallableWithVar( $arg ) {
	}

	public function onClosureWithVal( $arg ) {
	}

	public function onClosureDirect( $arg ) {
	}

	public function onGlobalFunctionString( $arg ) {
	}

	public function onStaticMethodString( $arg ) {
	}

	public function onNewSelf( $arg ) {
	}

	public function onNewStatic( $arg ) {
	}

	public function onNewClassName( $arg ) {
	}

	public function onArrayCallableWithVarMethodName( $arg ) {
	}

	public function onNewClassVar( $arg ) {
	}

	public function onArrayCallableWithLateStaticBinding( $arg ) {
	}

	public function onArrayCallableWithClassName( $arg ) {
	}

	public function onFirstClassGlobalFunction( $arg ) {
	}

	public function onFirstClassNonstaticMethod( $arg ) {
	}

	public function onFirstClassStaticMethod( $arg ) {
	}

	public function onNoop( $arg ) {
	}
}
