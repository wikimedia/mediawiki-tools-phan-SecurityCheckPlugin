<?php

$out = new OutputPage;

$stuff = new OOUI\HorizontalLayout( "More stuff" );
$stuff2 = new OOUI\HorizontalLayout( $_GET['stuff'] );

$out->addHtml( "<span>" . $stuff . "</span>" );
$out->addHtml( "<span>" . $stuff2 . "</span>" );
