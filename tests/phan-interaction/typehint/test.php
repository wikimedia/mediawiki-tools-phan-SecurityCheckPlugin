<?php

class FakeClass{ public $timestamp; }

function getHumanTimestampInternal( FakeClass $ts ) {
	if ( $ts->timestamp ) {// This would emit: PhanTypeExpectedObjectPropAccess Expected an object instance when accessing an instance property, but saw an expression $ts with type string
		$ts = 'x';
	}
}
