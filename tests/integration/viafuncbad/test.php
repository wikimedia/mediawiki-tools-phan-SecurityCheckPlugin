<?php

$text = $_POST['foo'];
$html = Html::rawElement( 'div', [ 'class' => 'foo' ], $text );

OutputPage::addHTML( $html );
