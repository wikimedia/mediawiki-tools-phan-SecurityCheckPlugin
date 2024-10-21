<?php

function arrayPlusAndExecUnsafeWithAssignOp( $data ) {
	$defaultData = [
		'safe' => '',
		'unsafe' => '',
	];

	$data += $defaultData;
	echo $data['unsafe'];
}

arrayPlusAndExecUnsafeWithAssignOp( [ 'unsafe' => $_GET['x'] ] ); // XSS caused by 10 (not 9)
arrayPlusAndExecUnsafeWithAssignOp( [ 'safe' => $_GET['x'] ] ); // Safe
arrayPlusAndExecUnsafeWithAssignOp( [ 'somethingelse' => $_GET['x'] ] ); // Safe

function arrayPlusAndExecUnsafeWithFullAssignment( $data ) {
	$defaultData = [
		'safe' => '',
		'unsafe' => '',
	];

	$local = $data + $defaultData;
	echo $local['unsafe'];
}

arrayPlusAndExecUnsafeWithFullAssignment( [ 'unsafe' => $_GET['x'] ] ); // XSS caused by 23, 24
arrayPlusAndExecUnsafeWithFullAssignment( [ 'safe' => $_GET['x'] ] ); // Safe
arrayPlusAndExecUnsafeWithFullAssignment( [ 'somethingelse' => $_GET['x'] ] ); // Safe
