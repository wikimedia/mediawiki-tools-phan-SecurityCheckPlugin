<?php
require_once "SecurityCheckPlugin.php";

/**
 * Copyright Brian Wolff 2017. Released under the GPL version 2 or later.
 *
 * This is a generic security check plugin.
 *
 * If your project has functions/methods whose output you
 * specificly need to mark tainted, then you probably
 * want to make your own subcalss of SecurityCheckPlugin
 * and override getCustomFuncTaint().
 *
 * See MediaWikiSecurityCheckPlugin for an example.
 */
class GenericSecurityCheckPlugin extends SecurityCheckPlugin {

	protected function getCustomFuncTaints() : array {
		return [];
	}
}

return new GenericSecurityCheckPlugin;
