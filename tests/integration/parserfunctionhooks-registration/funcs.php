<?php

namespace ParserFunctionHooksRegistration {
	function parserFunctionHookGlobalFunctionNamespaced() {
		return [ $_GET['d'], 'isHTML' => true ]; // Unsafe
	}

	function parserFunctionHookSafeIfNamespacedUnsafeIfGlobal() {
		return [ 'safe', 'isHTML' => true ]; // Safe
	}
}

namespace {
	function parserFunctionHookGlobalFunctionGlobalNamespace() {
		return [ $_GET['d'], 'isHTML' => true ]; // Unsafe
	}

	// This method must remain unused, it's here to test that we can correctly resolve the namespace and use
	// the namespaced version.
	function parserFunctionHookSafeIfNamespacedUnsafeIfGlobal() {
		return [ $_GET['d'], 'isHTML' => true ]; // Would be unsafe, but this is NOT used as a parser hook, so safe.
	}
}
