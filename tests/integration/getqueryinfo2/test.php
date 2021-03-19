<?php

function reallyGetQueryInfo( $namespace ) {
	$retval = [
		'tables' => [],
		'fields' => [],
		'conds' => []
	];
	$retval['conds']['namespace'] = $namespace;
	'@phan-debug-var-taintedness $retval';
	return $retval;
}

function main() {
	$qi = reallyGetQueryInfo( $_GET['a'] );
	'@phan-debug-var-taintedness $qi';
	echo $qi['tables']; // Safe
	echo $qi['fields']; // Safe
	echo $qi['conds']; // XSS
	echo $qi; // XSS
}
