<?php

// Test using `HookContainer::NOOP` in hook registration.

use MediaWiki\HookContainer\HookContainer;

$hookContainer = new HookContainer();

$hookContainer->register( 'TestNoop', HookContainer::NOOP );
$hookContainer->register( 'TestNoop', '*no-op*' );
