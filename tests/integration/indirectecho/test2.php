<?php
$a = new IndirectEcho;

$a->appendHold( "42" );
$a->appendHold( $_POST['foo'] );
$a->appendHold( "42" );

$a->echoHold();
