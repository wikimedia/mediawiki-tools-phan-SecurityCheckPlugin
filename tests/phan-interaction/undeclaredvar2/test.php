<?php

function article() {
	if ( rand() ) {
		$article = $GLOBALS['foo'];
	}
	if ( isset( $article ) && $article->getPage() ) {
		return 42;
	} else {
		return 43;
	}
}
