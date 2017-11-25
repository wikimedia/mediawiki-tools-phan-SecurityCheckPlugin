<?php
function wfSomeFunc() {
		$someEvil = $_GET['d'];
		$someEvil .= "More text";
				return [ $someEvil, 'isHTML' => true ];
}
