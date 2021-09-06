<?php

/**
 * @param-taint none
 */
function missingParamName() {
}

/**
 * @param-taint $x
 */
function missingParamType( $x ) {
}

/**
 * @param-taint $x invalid
 */
function invalidParamType( $x ) {
}

/**
 * @return-taint invalid
 */
function invalidReturnType() {
}

/**
 * @return-taint exec_html
 */
function execInReturn() {
}

/** @return-taint tainted */
function testSingleLineValid() {
}

class TestTabbedValid {
	/** @return-taint tainted */
	function doTest() {
	}
}

/**
 * This test ensures that we get no issue if @param-taint or @return-taint doesn't appear at the start of the line
 */
function testNotStartValid() {
}
