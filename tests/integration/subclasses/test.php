<?php

use Wikimedia\Rdbms\MysqlDatabase;

$db = new MysqlDatabase;

$name = $_GET['name'];
$myQuery = "Select * from foo where name = '$name' LIMIT 3;";

$db->query( $myQuery, "query" );
