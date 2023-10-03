<?php

// Helpers
/**
 * @return-taint html
 */
function getHTML() {
	return 'x';
}

/**
 * @return-taint shell
 */
function getShell() {
	return 'x';
}



function ignoreArg( $x ) {
	return rand();
}
function testNoPassThroughIgnored( $arg ) {
	$y = ignoreArg( $arg );
	echo $y;
}
testNoPassThroughIgnored( $_GET['a'] ); // Safe

function preservePartialMovedAtOffset( $par ) {
	return [ 'x' => $par['y'], 'z' => 'safe' ];
}
function echoPreservePartialMovedAtOffsetAll( $arg ) {
	$y = preservePartialMovedAtOffset( $arg );
	echo $y;
}
function echoPreservePartialMovedAtOffsetX( $arg ) {
	$y = preservePartialMovedAtOffset( $arg );
	echo $y['x'];
}
function echoPreservePartialMovedAtOffsetZ( $arg ) {
	$y = preservePartialMovedAtOffset( $arg );
	echo $y['z'];
}
echoPreservePartialMovedAtOffsetAll( $_GET['a'] ); // TODO Unsafe
echoPreservePartialMovedAtOffsetX( $_GET['a'] ); // TODO Unsafe
echoPreservePartialMovedAtOffsetZ( $_GET['a'] ); // Safe
echoPreservePartialMovedAtOffsetAll( [ 'y' => 'safe', 'z' => $_GET['a'] ] ); // Safe
echoPreservePartialMovedAtOffsetX( [ 'y' => 'safe', 'z' => $_GET['a'] ] ); // Safe
echoPreservePartialMovedAtOffsetZ( [ 'y' => 'safe', 'z' => $_GET['a'] ] ); // Safe
echoPreservePartialMovedAtOffsetAll( [ 'z' => 'safe', 'y' => $_GET['a'] ] ); // TODO Unsafe
echoPreservePartialMovedAtOffsetX( [ 'z' => 'safe', 'y' => $_GET['a'] ] ); // TODO Unsafe
echoPreservePartialMovedAtOffsetZ( [ 'z' => 'safe', 'y' => $_GET['a'] ] ); // Safe


function preservePartial( $x ) {
	return $x['y'];
}
function echoPartialAll( $par ) {
	$v = preservePartial( $par );
	echo $v;
}
function echoPartialY( $par ) {
	$v = preservePartial( $par )['y'];
	echo $v;
}
function echoPartialX( $par ) {
	$v = preservePartial( $par )['x'];
	echo $v;
}
echoPartialAll( $_GET['a'] ); // TODO Unsafe
echoPartialAll( getHTML() ); // TODO Unsafe
echoPartialAll( getShell() ); // Safe
echoPartialAll( [ 'y' => getHTML(), 'z' => 'safe' ] ); // TODO Unsafe
echoPartialAll( [ 'z' => getHTML(), 'y' => 'safe' ] ); // Safe
echoPartialAll( [ 'y' => getShell(), 'z' => 'safe' ] ); // Safe
echoPartialY( [ 'y' => [ 'y' => getHTML(), 'x' => 'safe' ], 'z' => 'safe' ] ); // TODO Unsafe
echoPartialY( [ 'y' => [ 'y' => 'safe', 'x' => getHTML() ], 'z' => 'safe' ] ); // Safe
echoPartialX( [ 'y' => [ 'y' => getHTML(), 'x' => 'safe' ], 'z' => 'safe' ] ); // Safe
echoPartialX( [ 'y' => [ 'y' => 'safe', 'x' => getHTML() ], 'z' => 'safe' ] ); // TODO Unsafe


function preserveMovedAtOffset( $par ) {
	return [ 'x' => $par, 'z' => 'safe' ];
}
function echoPreserveMovedAtOffsetAll( $par ) {
	$v = preserveMovedAtOffset( $par );
	echo $v;
}
function echoPreserveMovedAtOffsetX( $par ) {
	$v = preserveMovedAtOffset( $par )['x'];
	echo $v;
}
function echoPreserveMovedAtOffsetZ( $par ) {
	$v = preserveMovedAtOffset( $par )['z'];
	echo $v;
}
echoPreserveMovedAtOffsetAll( $_GET['a'] ); // TODO Unsafe
echoPreserveMovedAtOffsetX( $_GET['a'] ); // TODO Unsafe
echoPreserveMovedAtOffsetZ( $_GET['a'] ); // Safe
echoPreserveMovedAtOffsetAll( [ 'x' => 'safe', 'z' => $_GET['a'] ] ); // TODO Unsafe
echoPreserveMovedAtOffsetX( [ 'x' => 'safe', 'z' => $_GET['a'] ] ); // TODO Unsafe
echoPreserveMovedAtOffsetZ( [ 'x' => 'safe', 'z' => $_GET['a'] ] ); // Safe


function removeBitsFromDifferentOffsets( $par ) {
	return htmlspecialchars( $par['x'] ) . escapeshellcmd( $par['y'] );
}
function echoRemoveBitsFromDifferentOffsets( $par ) {
	$v = removeBitsFromDifferentOffsets( $par );
	echo $v;
}
function execRemoveBitsFromDifferentOffsets( $par ) {
	$v = removeBitsFromDifferentOffsets( $par );
	shell_exec( $v );
}
echoRemoveBitsFromDifferentOffsets( [ 'x' => $_GET['t'], 'y' => $_GET['t'] ] ); // TODO Unsafe
echoRemoveBitsFromDifferentOffsets( [ 'x' => getHTML(), 'y' => getHTML() ] ); // TODO Unsafe
echoRemoveBitsFromDifferentOffsets( [ 'x' => getHTML(), 'y' => getShell() ] ); // Safe
echoRemoveBitsFromDifferentOffsets( [ 'x' => getShell(), 'y' => getHTML() ] ); // TODO Unsafe
echoRemoveBitsFromDifferentOffsets( [ 'x' => getShell(), 'y' => getShell() ] ); // Safe
execRemoveBitsFromDifferentOffsets( [ 'x' => $_GET['t'], 'y' => $_GET['t'] ] ); // TODO Unsafe
execRemoveBitsFromDifferentOffsets( [ 'x' => getHTML(), 'y' => getHTML() ] ); // Safe
execRemoveBitsFromDifferentOffsets( [ 'x' => getHTML(), 'y' => getShell() ] ); // Safe
execRemoveBitsFromDifferentOffsets( [ 'x' => getShell(), 'y' => getHTML() ] ); // TODO Unsafe
execRemoveBitsFromDifferentOffsets( [ 'x' => getShell(), 'y' => getShell() ] ); // TODO Unsafe


function escapeHtmlFirstShellSecond( $x, $y ) {
	$x = htmlspecialchars( $x );
	$y = escapeshellcmd( $y );
	return $x . $y;
}
function echoEscapeHtmlFirstShellSecond( $x, $y ) {
	$v = escapeHtmlFirstShellSecond( $x, $y );
	echo $v;
}
function execEscapeHtmlFirstShellSecond( $x, $y ) {
	$v = escapeHtmlFirstShellSecond( $x, $y );
	shell_exec( $v );
}
echoEscapeHtmlFirstShellSecond( 'a', 'b' ); // Safe
echoEscapeHtmlFirstShellSecond( $_GET['a'], $_GET['b'] ); // TODO Unsafe
echoEscapeHtmlFirstShellSecond( $_GET['a'], 'b' ); // Safe
echoEscapeHtmlFirstShellSecond( 'a', $_GET['b'] ); // TODO Unsafe
echoEscapeHtmlFirstShellSecond( getHTML(), getHTML() ); // TODO Unsafe
echoEscapeHtmlFirstShellSecond( getHTML(), getShell() ); // Safe
echoEscapeHtmlFirstShellSecond( getShell(), getHTML() ); // TODO Unsafe
echoEscapeHtmlFirstShellSecond( getShell(), getShell() ); // Safe
execEscapeHtmlFirstShellSecond( 'a', 'b' ); // Safe
execEscapeHtmlFirstShellSecond( $_GET['a'], $_GET['b'] ); // TODO Unsafe
execEscapeHtmlFirstShellSecond( $_GET['a'], 'b' ); // TODO Unsafe
execEscapeHtmlFirstShellSecond( 'a', $_GET['b'] ); // Safe
execEscapeHtmlFirstShellSecond( getHTML(), getHTML() ); // Safe
execEscapeHtmlFirstShellSecond( getHTML(), getShell() ); // Safe
execEscapeHtmlFirstShellSecond( getShell(), getHTML() ); // TODO Unsafe
execEscapeHtmlFirstShellSecond( getShell(), getShell() ); // TODO Unsafe



function removeBitsFromDifferentOffsetsAndAll( $par ) {
	return mysqli_real_escape_string( new mysqli, $par ) . htmlspecialchars( $par['x'] ) . escapeshellcmd( $par['y'] );
}
function echoRemoveBitsFromDifferentOffsetsAndAll( $par ) {
	$v = removeBitsFromDifferentOffsetsAndAll( $par );
	echo $v;
}
function execRemoveBitsFromDifferentOffsetsAndAll( $par ) {
	$v = removeBitsFromDifferentOffsetsAndAll( $par );
	shell_exec( $v );
}
function queryRemoveBitsFromDifferentOffsetsAndAll( $par ) {
	$v = removeBitsFromDifferentOffsetsAndAll( $par );
	mysqli_query( new mysqli, $v );
}
echoRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => $_GET['t'], 'y' => $_GET['t'] ] ); // TODO Unsafe
echoRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getHTML(), 'y' => getHTML() ] ); // TODO Unsafe
echoRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getHTML(), 'y' => getShell() ] ); // TODO Unsafe
echoRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getShell(), 'y' => getHTML() ] ); // TODO Unsafe
echoRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getShell(), 'y' => getShell() ] ); // Safe
execRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => $_GET['t'], 'y' => $_GET['t'] ] ); // TODO Unsafe
execRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getHTML(), 'y' => getHTML() ] ); // Safe
execRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getHTML(), 'y' => getShell() ] ); // TODO Unsafe
execRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getShell(), 'y' => getHTML() ] ); // TODO Unsafe
execRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getShell(), 'y' => getShell() ] ); // TODO Unsafe
queryRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => $_GET['t'], 'y' => $_GET['t'] ] ); // TODO Unsafe
queryRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getHTML(), 'y' => getHTML() ] ); // Safe
queryRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getHTML(), 'y' => getShell() ] ); // Safe
queryRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getShell(), 'y' => getHTML() ] ); // Safe
queryRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getShell(), 'y' => getShell() ] ); // Safe
queryRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => $_GET['t'], 'y' => getShell() ] ); // TODO Unsafe
queryRemoveBitsFromDifferentOffsetsAndAll( [ 'x' => getShell(), 'y' => $_GET['t'] ] ); // TODO Unsafe



function removeHtmlFromAllShellFromOffset( $par ) {
	return htmlspecialchars( $par ) . escapeshellcmd( $par['x'] );
}
function echoRemoveHtmlFromAllShellFromOffset( $par ) {
	$v = removeHtmlFromAllShellFromOffset( $par );
	echo $v;
}
function execRemoveHtmlFromAllShellFromOffset( $par ) {
	$v = removeHtmlFromAllShellFromOffset( $par );
	shell_exec( $v );
}
echoRemoveHtmlFromAllShellFromOffset( [ 'x' => $_GET['t'] ] ); // TODO Unsafe
echoRemoveHtmlFromAllShellFromOffset( [ 'x' => getHTML() ] ); // TODO Unsafe
echoRemoveHtmlFromAllShellFromOffset( [ 'x' => getShell() ] ); // Safe
execRemoveHtmlFromAllShellFromOffset( [ 'x' => $_GET['t'] ] ); // TODO Unsafe
execRemoveHtmlFromAllShellFromOffset( [ 'x' => getHTML() ] ); // Safe
execRemoveHtmlFromAllShellFromOffset( [ 'x' => getShell() ] ); // TODO Unsafe

function echoOffsetOfEscaped( $par ) {
	$v = htmlspecialchars( $par );
	$y = $v['x'];// $v is a string but we don't care. The important thing is that ~HTML isn't lost
	echo $y;
}
echoOffsetOfEscaped( [ 'x' => $_GET['t'] ] ); // Safe
