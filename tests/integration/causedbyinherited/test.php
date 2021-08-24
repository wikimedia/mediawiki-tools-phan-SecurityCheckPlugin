<?php

class ParentClass {
	function getEvil() {
		return $_GET['x'];
	}
}

class ChildClass extends ParentClass {

}

$child = new ChildClass();
echo $child->getEvil(); // Should have line 5 in its caused-by
