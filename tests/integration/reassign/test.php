<?php

############################################ Global Scope ############################################
$evil1 = $_GET['y'];
$evil1 = htmlspecialchars( $evil1 );
echo $evil1; // Safe
$evil1 .= $_GET['baz'];
echo $evil1; // Unsafe
$evil1 = empty( $evil1 );
echo $evil1; // Safe again

############################################ FunctionLike Scope ############################################
function reassignInFunctionScope() {
	$evil2 = $_GET['y'];
	$evil2 = htmlspecialchars( $evil2 );
	echo $evil2; // Safe
	$evil2 .= $_GET['baz'];
	echo $evil2; // Unsafe
	$evil2 = empty( $evil2 );
	echo $evil2; // Safe again
}

############################################ BranchScope inside GlobalScope, var exists outside ############################################
$evil3 = $_GET['barbaz'];
if ( rand() ) {
	// In branch scope for existing
	$evil3 = $_GET['y'];
	$evil3 = htmlspecialchars( $evil3 );
	echo $evil3; // Safe
	$evil3 .= $_GET['baz'];
	echo $evil3; // Unsafe
	$evil3 = empty( $evil3 );
	echo $evil3; // Safe again
}
echo $evil3; // Unsafe because no guarantee the if gets executed

############################################ BranchScope inside GlobalScope, var DOES NOT exist outside ############################################
if ( rand() ) {
	// In branch scope for nonexisting
	$evil4 = $_GET['y'];
	$evil4 = htmlspecialchars( $evil4 );
	echo $evil4; // Safe
	$evil4 .= $_GET['baz'];
	echo $evil4; // Unsafe
	$evil4 = empty( $evil4 );
	echo $evil4; // Safe again
}

############################################ BranchScope inside FunctionLikeScope, var exists outside ############################################
function functionScope() {
	$evil5 = $_GET['barbaz'];
	if ( rand() ) {
		// Branch scope inside FunctionLike scope
		$evil5 = $_GET['y'];
		$evil5 = htmlspecialchars( $evil5 );
		echo $evil5; // Safe
		$evil5 .= $_GET['baz'];
		echo $evil5; // Unsafe
		$evil5 = empty( $evil5 );
		echo $evil5; // Safe again
	}
	echo $evil5; // Unsafe because no guarantee the if gets executed
}

############################################ BranchScope inside FunctionLikeScope, var DOES NOT exist outside ############################################
function functionScope2() {
	if ( rand() ) {
		// Branch scope inside FunctionLike scope
		$evil6 = $_GET['y'];
		$evil6 = htmlspecialchars( $evil6 );
		echo $evil6; // Safe
		$evil6 .= $_GET['baz'];
		echo $evil6; // Unsafe
		$evil6 = empty( $evil6 );
		echo $evil6; // Safe again
	}
}
