<?php

/*
 * Variant of test.php where we're checking that linked arguments are handled independently of each other.
 * The three sink classes are the same, but the calls are different (and we don't want their data to get mixed up).
 */

$triggerClassMulti = new TriggerClassMultipleArgs();
$triggerClassMulti->triggerProp = $_GET['a'];
$triggerClassMulti->callSink();

class TriggerClassMultipleArgs {
	public $triggerProp = '';

	public function callSink() {
		new SinkClassMultipleArgsFirst( $this->triggerProp, '' );
		new SinkClassMultipleArgsSecond( '', $this->triggerProp );
		new SinkClassMultipleArgsBoth( $this->triggerProp, $this->triggerProp );
	}
}

class SinkClassMultipleArgsFirst {
	public $sinkPropFirst;

	public function __construct( $val1, $val2 ) {
		$this->sinkPropFirst = $val1; // This must be in the caused-by lines
		$this->sinkPropFirst = $val2; // This must NOT be in the caused-by lines
	}

	public function doSink() {
		echo $this->sinkPropFirst; // Should only be caused by the line with $val1
	}
}

class SinkClassMultipleArgsSecond {
	public $sinkPropSecond;

	public function __construct( $val1, $val2 ) {
		$this->sinkPropSecond = $val1; // This must NOT be in the caused-by lines
		$this->sinkPropSecond = $val2; // This must be in the caused-by lines
	}

	public function doSink() {
		echo $this->sinkPropSecond; // Should only be caused by the line with $val2
	}
}

class SinkClassMultipleArgsBoth {
	public $sinkPropBoth;

	public function __construct( $val1, $val2 ) {
		$this->sinkPropBoth = $val1; // This must be in the caused-by lines
		$this->sinkPropBoth = $val2; // This must be in the caused-by lines
	}

	public function doSink() {
		echo $this->sinkPropBoth; // Should be caused by both lines in __construct
	}
}
