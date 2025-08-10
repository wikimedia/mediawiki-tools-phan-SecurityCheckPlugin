<?php

// NOTE: This file should be analyzed BEFORE second.php, so watch out if renaming it

namespace CausedbyRedundant;

class TriggerSinkAnalysis {
	public static function trigger1( GenericHTMLSink $sink ) {
		SafeMethodClass::safeMethod( $sink );
	}

	public static function trigger2() {
		GenericSinks::returnArg( getEscaped() );
	}
}

class TriggerHolderAnalysis {
	public function trigger() {
		$holder = new UnsafeArrayHolder();
		$holder->setField( $_GET['a'], $_GET['a'], $_GET['a'] );
	}
}

class TriggerMoreSinkAnalysis {
	private function trigger1() {
		GenericSinks::returnArg(
			GenericSinks::escapeArgHTML( getUnsafe() )
		);
	}

	private function trigger2( GenericHTMLSink $sink ) {
		$sink->htmlSink( GenericSinks::returnArg( getEscaped() ) );
	}
}

class TriggerIssue {
	public function execute( $ignored ) {
		$this->method1();
		$this->method2();
	}

	private function method1() {
		$holder = new UnsafeArrayHolder();
		echo GenericSinks::returnArg( $holder->get( $_GET['a'] ) );// Must have second.php line 13 in its caused-by
	}

	private function method2() {
		$text = '';
		SafeMethodClass::safeMethod( $text );
	}
}
