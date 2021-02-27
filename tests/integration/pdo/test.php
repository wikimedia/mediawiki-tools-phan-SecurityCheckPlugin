<?php

$pdo = new PDO();

$taintedVar = $_GET['goat'];
$pdo->query( "SELECT * FROM user WHERE user_name = $taintedVar" ); // SQLi
$pdo->prepare( 'SELECT ' . $taintedVar ); // SQLi
$pdo->exec( "UPDATE $taintedVar" ); // SQLi

$prep = $pdo->prepare( 'SELECT * FROM user WHERE user_name = :name OR user_id = ?' );
$prep->bindParam( ':name', $_GET['name'] );
$prep->bindParam( 1, $_GET['id'], PDO::PARAM_INT );
$prep->execute();// Safe

$prep2 = $pdo->prepare( 'SELECT * FROM user WHERE user_name = :name OR user_id = ' . $_GET['id'] ); // SQLi
$prep->bindParam( ':name', $_GET['name'] );
$prep->execute();// Considered safe, since the SQLi is reported above
