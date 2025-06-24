<?php

use Wikimedia\Rdbms\SelectQueryBuilder;

$sqb = new SelectQueryBuilder();

$sqb->options(); // Invalid, but don't crash
$sqb->options( [] ); // Safe
$sqb->options( [ 'GROUP BY' => $_GET['a'] ] ); // SQLi
$sqb->options( [ 'ORDER BY' => $_GET['a'] ] ); // SQLi
$sqb->options( [ 'HAVING' => $_GET['a'] ] ); // SQLi
$sqb->options( [ 'HAVING' => [ $_GET['a'] ] ] ); // SQLi
$sqb->options( [ 'HAVING' => [ 'safe' => $_GET['a'] ] ] ); // Safe
$sqb->options( [ 'USE INDEX' => $_GET['a'] ] ); // SQLi
$sqb->options( [ 'IGNORE INDEX' => $_GET['a'] ] ); // SQLi
$sqb->options( [ 'SOME OTHER OPTION' => $_GET['a'] ] ); // Safe
$sqb->options( [ $GLOBALS['UNRESOLVABLE_OPTION'] => $_GET['a'] ] ); // Safe

$sqb->option( 'GROUP BY', $_GET['a'] ); // SQLi
$sqb->option( 'ORDER BY', $_GET['a'] ); // SQLi
$sqb->option( 'HAVING', $_GET['a'] ); // SQLi
$sqb->option( 'HAVING', [ $_GET['a'] ] ); // SQLi
$sqb->option( 'HAVING', [ 'safe' => $_GET['a'] ] ); // Safe
$sqb->option( 'USE INDEX', $_GET['a'] ); // SQLi
$sqb->option( 'IGNORE INDEX', $_GET['a'] ); // SQLi
$sqb->option( 'SOME OTHER OPTION', $_GET['a'] ); // Safe

$sqb->joinConds( [ 'sometable' => [ $_GET['join_type'], '1=1' ] ] ); // SQLi
$sqb->joinConds( [ 'sometable' => [ 'LEFT JOIN', $_GET['a'] ] ] ); // SQLi
$sqb->joinConds( [ 'sometable' => [ 'LEFT JOIN', [ $_GET['a'] ] ] ] ); // SQLi
$sqb->joinConds( [ 'sometable' => [ 'LEFT JOIN', [ 'escaped' => $_GET['a'] ] ] ] ); // Safe
