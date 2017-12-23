<?php

$someHtml = '<h1>Hello there!</h1>';

$escaped = htmlspecialchars( $someHtml );
$doubleEscaped = htmlspecialchars( $escaped );

// Oh no! It's double escaped!
echo $doubleEscaped;
