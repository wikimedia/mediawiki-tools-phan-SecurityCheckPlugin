<?php

class DifferentInstancePropExt {
	public function triggerDifferentInstanceProp() {
		MainClass::fromId( $_GET['baz'] );
	}
}
