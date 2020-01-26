<?php

// We need to load this class now, so that when PHPUnit takes a snapshot of static attributes
// it also includes GlobalScope::global_variable_map. Failing to back up this property (or backing
// it up when it was already populated) will result in weird false positives.
require_once __DIR__ . '/../vendor/phan/phan/src/Phan/Language/Scope/GlobalScope.php';
// Same as above, for SecurityCheckPlugin::$docblockCache
require_once __DIR__ . '/../src/SecurityCheckPlugin.php';
