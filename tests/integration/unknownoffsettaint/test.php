<?php

abstract class Job {
	public $params;

	public function __construct( array $params = null ) {
		$this->params = $params + [ 'requestId' => $_GET['foo'] ];
	}
}

class BacklinkJobUtils {
	public function partitionBacklinkJob( Job $job ) {
		$params = $job->params;
		echo $params['table']; // Safe
	}
}

