<?php

function foo( $param ) {
	$a = OutputPage::addHtml( $param );
	$a = $_POST['evil'];
}
