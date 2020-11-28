<?php

// We shouldn't get redundant/impossible conditions warnings here due to the assignments in the other file.

function wfDebug() {
	global $wgDebugRawPage;
	if ( !$wgDebugRawPage ) {
	}
}

function wfReportTime() {
	global $wgShowHostnames;
	if ( $wgShowHostnames ) {
	}
}

