<?php

function echoAll( $p1, ...$p ) {
	echo $p1;
	echo $p;
}

echoAll( $_GET['x'], '' );
echoAll( 1, 2, 3, 4, $_GET['a'] );

/**
 * This is wrong because "..." is missing from the annotation
 * @param-taint $p exec_html
 */
function annotatedWrong( $p1, ...$p ) {
}
annotatedWrong( $_GET['x'], '' );
annotatedWrong( 1, 2, 3, 4, $_GET['a'] );

/**
 * @param-taint ...$p exec_html
 */
function annotatedCorrect( $p1, ...$p ) {
}

annotatedCorrect( $_GET['x'], '' );
annotatedCorrect( 1, 2, 3, 4, $_GET['a'] );
