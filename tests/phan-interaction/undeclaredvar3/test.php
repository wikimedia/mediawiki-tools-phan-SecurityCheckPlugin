<?php

function getLintErrorDetails( array $lintError ) {
	[
		'type' => $type, // Avoid: PhanUndeclaredVariable Variable $type is undeclared
		'params' => $params
	] = $lintError;
}
