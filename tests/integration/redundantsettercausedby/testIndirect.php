<?php

/*
 * Same as test.php, but here the properties are assigned to a local variable first.
 */

$triggerClassIndirect = new TriggerClassIndirect();
$triggerClassIndirect->triggerProp = $_GET['a'];
$triggerClassIndirect->callSink();

class TriggerClassIndirect {
	public $triggerProp = '';

	public function callSink() {
		new SinkClassIndirect( $this->triggerProp );
	}
}

class SinkClassIndirect {
	public $sinkProp;

	public function __construct( $val ) {
		$indirectVal = $val; // This must be in the caused-by lines
		$this->sinkProp = $indirectVal; // This must be in the caused-by lines
	}

	public function setProp( $val ) {
		$indirectVal = $val; // This method is never called, and this line must NOT appear in the caused-by lines
		$this->sinkProp = $indirectVal; // This method is never called, and this line must NOT appear in the caused-by lines
	}

	public function doSink() {
		echo $this->sinkProp; // Should be caused by __construct (lines 23 and 24), but NOT setProp (lines 28 and 29)
	}
}
