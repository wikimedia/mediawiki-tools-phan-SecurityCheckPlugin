<?php
// unsafe
echo @( $_GET['foo'] );
echo ~( $_GET['foo'] );
// safe
echo ( +$_GET['bar'] );
echo ( -$_GET['bar'] );
echo ( !$_GET['bar'] );
