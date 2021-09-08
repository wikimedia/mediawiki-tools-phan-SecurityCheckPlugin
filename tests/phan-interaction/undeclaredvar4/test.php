<?php

function promoteToGlobal() {
	attach();
}

function attach() {
	if ( rand() ) {
		global $wgCentralAuthRC;
		var_dump( $wgCentralAuthRC );
	}
}


