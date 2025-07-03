<?php

class DifferentInstancePropExt {
	public function foo() {
		MainClass::fromId( $_GET['baz'] );
	}
}
