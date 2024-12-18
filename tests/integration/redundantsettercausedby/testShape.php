<?php

/*
 * Variant of text.php with a nontrivial taintedness shape.
 */

$triggerClassShape = new TriggerClassShape();
$triggerClassShape->triggerProp = $_GET['a'];
$triggerClassShape->callSink();

class TriggerClassShape {
	public $triggerProp = '';

	public function callSink() {
		new SinkClassShape( $this->triggerProp );
	}
}

class SinkClassShape {
	public $sinkProp;

	public function __construct( $val ) {
		$this->sinkProp['foo'] = $val; // Must be in the caused-by for doSinkFoo only
		$this->sinkProp['bar'] = $val; // Must be in the caused-by for doSinkBar only
	}

	public function doSinkFoo() {
		echo $this->sinkProp['foo']; // Must only be caused by the line that sets foo
	}

	public function doSinkBar() {
		echo $this->sinkProp['bar']; // Must only be caused by the line that sets bar
	}
}
