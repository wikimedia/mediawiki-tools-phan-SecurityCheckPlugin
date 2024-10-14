<?php

// Regression test for caused-by lines when the same line is backpropagated over and over again. The sink here has a
// complex shape due to optionally-variadic parameters and recursion; and backpropagation happens several times. Trying
// to push parameter offsets each time would make the ParamLinksOffsets objects grow exponentially, to the point of
// going OOM and timing out.
// This test is based on certain interactions in MW core between Title and Message.
// Note that this class needs to be analyzed before the other one.

class ParamCollector {
	protected array $parameters = [];

	public function params( ...$args ): void {
		if ( count( $args ) === 1 && isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		$this->parameters = $args;
		foreach ( $this->parameters as $param ) {
			$this->execParam( $param );
		}
	}

	protected function execParam( $param ) {
		echo $param['k1'];
		echo $param['k2'];
		echo $param['k3'];
		echo $param['k4'];
		$this->recursiveExecParam( $param['k5'] );
	}

	protected function recursiveExecParam( array $params ) {
		foreach ( $params as $p ) {
			$this->execParam( $p );
		}
	}
}

