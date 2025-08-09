<?php

/** @return-taint tainted */
function getUnsafeStdClass(): stdClass {
	return (object)$_GET['a'];
}

/**
 * @return-taint none
 */
function getFieldName(): string {
	return $_GET['unknown'];
}