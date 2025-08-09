<?php

$text = $_POST['foo'];
$html = HardcodedSimpleTaint::yesArgReturnsEscaped( $text );

OutputClass::addHTML( $html );
