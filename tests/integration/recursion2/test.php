<?php

function main() {
	safeTainted();
	taintedSafe();
}

function safeTainted() {
	if ( rand() ) {
		return 'safe';
	}
	if ( rand() ) {
		echo safeTainted(); // XSS
		return 'safe';
	}
	echo safeTainted();
	return $_GET['x'];
}

function taintedSafe() {
	if ( rand() ) {
		echo taintedSafe(); // LikelyFalsePositive, because it's still unknown
	}
	if ( rand() ) {
		return $_GET['baz'];
	}
	echo taintedSafe();
	return 'safe';
}
