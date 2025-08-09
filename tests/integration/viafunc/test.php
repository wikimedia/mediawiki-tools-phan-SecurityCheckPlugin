<?php

$text = $_POST['foo'];
$html = HardcodedSimpleTaint::escapesArgReturnsEscaped( $text );

OutputPage::addHtml( $html );
