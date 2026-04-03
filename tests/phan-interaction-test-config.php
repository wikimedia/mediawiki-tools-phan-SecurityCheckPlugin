<?php
declare( strict_types = 1 );

$cfg = require __DIR__ . '/integration-test-config.php';
$cfg['whitelist_issue_types'] = [];

return $cfg;
