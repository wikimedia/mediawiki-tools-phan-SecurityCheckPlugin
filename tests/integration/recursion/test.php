<?php

function main() {
	echo taintedRecSafe(); // XSS
	echo recTaintedSafe(); // XSS
	echo safeRecTainted(); // XSS
}

function taintedRecSafe() {
	if ( rand() ) {
		return $_GET['baz'];
	}
	if ( rand() ) {
		return taintedRecSafe();
	}
	return 'safe';
}

function recTaintedSafe() {
	if ( rand() ) {
		return recTaintedSafe();
	}
	if ( rand() ) {
		return $_GET['baz'];
	}
	return 'safe';
}

function safeRecTainted() {
	if ( rand() ) {
		return 'safe';
	}
	if ( rand() ) {
		return safeRecTainted();
	}
	return $_GET['baz'];
}
