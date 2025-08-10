<?php

$text = $_POST['foo'];

$safe = HardcodedSimpleTaint::escapesArgReturnsEscaped( $text );
TestSinkShape::sinkAll( $safe ); // Safe

$unsafe = HardcodedSimpleTaint::yesArgReturnsEscaped( $text );
TestSinkShape::sinkAll( $unsafe ); // XSS
