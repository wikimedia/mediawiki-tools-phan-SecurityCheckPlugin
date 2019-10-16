<?php

for ( $i = $_GET['foo']; strlen( $i ) > 0; $i = substr( $i, 0, -1 ) ) {
	echo $i;
}

for ( $j = 'a'; $j < 'z'; $j++ ) {
	echo $j;
}

for ( $k = $_GET['baz']; ; ) {
	echo $k;
	break;
}
