<?php

$text = $_POST['foo'];
$html = Html::element( 'div', [ 'class' => 'foo' ], $text );

OutputPage::addHtml( $html );
