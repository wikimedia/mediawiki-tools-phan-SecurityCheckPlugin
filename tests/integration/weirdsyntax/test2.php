<?php

function inPlaceSpread() {
	$array = [ 1, 2, 3 ];
	$array = [ ...$array ]; // Ensure no crash
}
