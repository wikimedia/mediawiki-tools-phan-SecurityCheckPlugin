<?php
$a = new Foo;

$a->appendHold( "42" );
$a->appendHold( $_POST['foo'] );
$a->appendHold( "42" );

$a->echoHold();
