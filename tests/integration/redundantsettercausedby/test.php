<?php

/*
 * Test to verify that setter methods are not included in the caused-by lines if they're not responsible
 * for the relevant taintedness. Note that the ordering is important here, and TriggerClass should be defined
 * and used before the definition of SinkClass.
 */

$triggerClass = new TriggerClass();
$triggerClass->triggerProp = $_GET['a'];
$triggerClass->callSink();

class TriggerClass {
	public $triggerProp = '';

	public function callSink() {
		new SinkClass( $this->triggerProp );
	}
}

class SinkClass {
	public $sinkProp;

	public function __construct( $val ) {
		$this->sinkProp = $val; // This must be in the caused-by lines
	}

	public function setProp( $val ) {
		$this->sinkProp = $val; // This method is never called, and this line must NOT appear in the caused-by lines
	}

	public function doSink() {
		echo $this->sinkProp; // Should be caused by __construct (line 25), but NOT setProp (line 29)
	}
}
