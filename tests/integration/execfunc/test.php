<?php
// Regression test for returning an EXEC_* tainted
// variable causing the method to have an overall EXEC_* taint.
$out = new OutputBufferingClass;

$out->addHTML( $_GET['foo'] );

$oldOut = $out->getHTML();
