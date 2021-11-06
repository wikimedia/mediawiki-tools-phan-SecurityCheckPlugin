<?php

echo $_GET;// Unsafe
echo $_GET['foooo'];// Unsafe
function iterateGet() {
	foreach ( $_GET as $k => $v ) {
		echo $k;// Unsafe
		echo $v;// Unsafe
	}
}
function iterateGetElement() {
	foreach ( $_GET['a'] as $k => $v ) {
		echo $k;// Unsafe
		echo $v;// Unsafe
	}
}

echo $_POST;// Unsafe
echo $_POST['foooo'];// Unsafe
function iteratePost() {
	foreach ( $_POST as $k => $v ) {
		echo $k;// Unsafe
		echo $v;// Unsafe
	}
}
function iteratePostElement() {
	foreach ( $_POST['a'] as $k => $v ) {
		echo $k;// Unsafe
		echo $v;// Unsafe
	}
}

echo $_SERVER;// Unsafe
echo $_SERVER['REMOTE_HOST'];// Unsafe
echo $_SERVER['argv'];// Unsafe
function iterateServer() {
	foreach ( $_SERVER as $k => $v ) {
		echo $k;// Unsafe
		echo $v;// Unsafe
	}
}
function iterateServerElement() {
	foreach ( $_SERVER['argc'] as $k => $v ) {
		echo $k;// Unsafe
		echo $v;// Unsafe
	}
}

echo $_FILES; // Unsafe
foreach ( $_FILES as $k => $v ) {
	echo $k;// Unsafe
	echo $v;// Unsafe
}
echo $_FILES['foo']['name']; // Unsafe
echo $_FILES['foo']['type']; // Unsafe
echo $_FILES['foo']['tmp_name']; // Safe
echo $_FILES['foo']['error']; // Safe
echo $_FILES['foo']['size']; // Safe

echo $GLOBALS;// Unsafe
echo $GLOBALS['foo'];// Safe
echo $GLOBALS['_GET']; // Unsafe
echo $GLOBALS['_GET']['foo']; // Unsafe
echo $GLOBALS['GLOBALS']; // Unsafe
echo $GLOBALS['GLOBALS']['_GET']; // Unsafe
echo $GLOBALS['GLOBALS']['foo']; // Ideally safe


// Simpler tests, these should be all tainted
echo $_COOKIE;// Unsafe
echo $_COOKIE['a'];// Unsafe
echo $_SESSION;// Unsafe
echo $_SESSION['a'];// Unsafe
echo $_REQUEST;// Unsafe
echo $_REQUEST['a'];// Unsafe
echo $_ENV;// Unsafe
echo $_ENV['a'];// Unsafe
echo $http_response_header;// Unsafe
