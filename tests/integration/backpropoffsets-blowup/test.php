<?php

namespace BackpropOffsetsBlowup;

// In these tests, the offset from the RHS of the assignments should only be added once. Failing to do so
// would be wrong, and would probably lead to memory exhaustion on large codebases.
// Unfortunately we need to use a hacky annotation to ensure that this bug doesn't happen: if we set HTML_EXEC
// on both ['x'] and ['x']['x'], the latter is ignored due to the former, so there's no difference in terms of
// issues being emitted.
// For recursive variants, basically any result is correct (since the recursion is potentially infinite, and even if it
// wasn't, we couldn't tell), as long as:
// - The outer taintedness has no OWN flags
// - It has *HTML on 'x'
// - It should have *HTML on 'x'->'x'
// Then the actual depth (beyond 'x'->'x') is irrelevant, and just depends on how thoroughly we/phan decide to analyze
// the method. However, it's also important that we don't recurse too much, or the memory usage might explode. We call
// the recursive variants more than once in order to assert this.
// All XSSs are suppressed so it's easier to focus on the important annotations.

class TestProp {
	public $prop;
	function echoUnsafe( $arg ) {
		$this->prop = $arg['x'];
		echo $this->prop;
	}
}
( new TestProp() )->echoUnsafe( [ 'x' => 'a' ] );
'@taint-check-debug-method-first-arg TestProp::echoUnsafe';

class TestPropRecursive {
	public $prop;
	function echoRecursive( $arg ) {
		$this->prop = $arg['x'];
		if ( rand() ) {
			$this->echoRecursive( $this->prop );//@phan-suppress-current-line SecurityCheck-XSS
		}
		echo $this->prop;//@phan-suppress-current-line SecurityCheck-XSS
	}
}
( new TestPropRecursive() )->echoRecursive( [ 'x' => 'a' ] );
( new TestPropRecursive() )->echoRecursive( [ 'x' => [ 'x' => 's', 'y' => $_GET['a'] ] ] );//@phan-suppress-current-line SecurityCheck-XSS
( new TestPropRecursive() )->echoRecursive( [ 'x' => [ 'x' => [ 'x' => [ 'x' => 's', 'y' => $_GET['a'] ] ] ] ] );//@phan-suppress-current-line SecurityCheck-XSS
'@taint-check-debug-method-first-arg TestPropRecursive::echoRecursive';

class TestGlobal {
	function echoUnsafe( $arg ) {
		global $foo;
		$foo = $arg['x'];
		echo $foo;
	}
}
( new TestGlobal() )->echoUnsafe( [ 'x' => 'a' ] );
'@taint-check-debug-method-first-arg TestGlobal::echoUnsafe';

class TestGlobalRecursive {
	function echoRecursive( $arg ) {
		global $foo;
		$foo = $arg['x'];
		if ( rand() ) {
			$this->echoRecursive( $foo );//@phan-suppress-current-line SecurityCheck-XSS
		}
		echo $foo;//@phan-suppress-current-line SecurityCheck-XSS
	}
}
( new TestGlobalRecursive() )->echoRecursive( [ 'x' => 'a' ] );
( new TestGlobalRecursive() )->echoRecursive( [ 'x' => [ 'x' => 's', 'y' => $_GET['a'] ] ] );//@phan-suppress-current-line SecurityCheck-XSS
( new TestGlobalRecursive() )->echoRecursive( [ 'x' => [ 'x' => [ 'x' => [ 'x' => 's', 'y' => $_GET['a'] ] ] ] ] );//@phan-suppress-current-line SecurityCheck-XSS
'@taint-check-debug-method-first-arg TestGlobalRecursive::echoRecursive';

class TestVar {
	function echoUnsafe( $arg ) {
		$local = $arg['x'];
		echo $local;
	}
}
( new TestVar() )->echoUnsafe( [ 'x' => 'a' ] );
'@taint-check-debug-method-first-arg TestVar::echoUnsafe';

class TestVarRecursive {
	function echoRecursive( $arg ) {
		$local = $arg['x'];
		if ( rand() ) {
			$this->echoRecursive( $local );
		}
		echo $local;
	}
}
( new TestVarRecursive() )->echoRecursive( [ 'x' => 'a' ] );
( new TestVarRecursive() )->echoRecursive( [ 'x' => [ 'x' => 's', 'y' => $_GET['a'] ] ] );//@phan-suppress-current-line SecurityCheck-XSS
( new TestVarRecursive() )->echoRecursive( [ 'x' => [ 'x' => [ 'x' => [ 'x' => 's', 'y' => $_GET['a'] ] ] ] ] );//@phan-suppress-current-line SecurityCheck-XSS
'@taint-check-debug-method-first-arg TestVarRecursive::echoRecursive';


// This is adapted from an actual example taken from PHPUnit's TestRunner (when it handles config options).
// This would cause a very long list of dependencies to be merged together, a total of 2^(assignments-1) offsets.
// That would make the plugin hang, or exhaust the memory of your machine for a large exponent.
class PHPUnitExample {
	function exhaustionTest( $arguments ) {
		$arguments['a0'] = $arguments['a0'] ?? true;
		$arguments['a1'] = $arguments['a1'] ?? true;
		$arguments['a2'] = $arguments['a2'] ?? true;
		$arguments['a3'] = $arguments['a3'] ?? true;
		$arguments['a4'] = $arguments['a4'] ?? true;
		$arguments['a5'] = $arguments['a5'] ?? true;
		$arguments['a6'] = $arguments['a6'] ?? true;
		$arguments['a7'] = $arguments['a7'] ?? true;
		$arguments['a8'] = $arguments['a8'] ?? true;
		$arguments['a9'] = $arguments['a9'] ?? true;
		$arguments['b0'] = $arguments['b0'] ?? true;
		$arguments['b1'] = $arguments['b1'] ?? true;
		$arguments['b2'] = $arguments['b2'] ?? true;
		$arguments['b3'] = $arguments['b3'] ?? true;
		$arguments['b4'] = $arguments['b4'] ?? true;
		$arguments['b5'] = $arguments['b5'] ?? true;
		$arguments['b6'] = $arguments['b6'] ?? true;
		$arguments['b7'] = $arguments['b7'] ?? true;
		$arguments['b8'] = $arguments['b8'] ?? true;
		$arguments['b9'] = $arguments['b9'] ?? true;
		$arguments['c0'] = $arguments['c0'] ?? true;
		$arguments['c1'] = $arguments['c1'] ?? true;
		$arguments['c2'] = $arguments['c2'] ?? true;
		$arguments['c3'] = $arguments['c3'] ?? true;
		$arguments['c4'] = $arguments['c4'] ?? true;
		$arguments['c5'] = $arguments['c5'] ?? true;
		$arguments['c6'] = $arguments['c6'] ?? true;
		$arguments['c7'] = $arguments['c7'] ?? true;
		$arguments['c8'] = $arguments['c8'] ?? true;
		$arguments['c9'] = $arguments['c9'] ?? true;
		$arguments['d0'] = $arguments['d0'] ?? true;
		$arguments['d1'] = $arguments['d1'] ?? true;
		$arguments['d2'] = $arguments['d2'] ?? true;
		$arguments['d3'] = $arguments['d3'] ?? true;
		$arguments['d4'] = $arguments['d4'] ?? true;
		$arguments['d5'] = $arguments['d5'] ?? true;
		$arguments['d6'] = $arguments['d6'] ?? true;
		$arguments['d7'] = $arguments['d7'] ?? true;
		$arguments['d8'] = $arguments['d8'] ?? true;
		$arguments['d9'] = $arguments['d9'] ?? true;
		$arguments['e0'] = $arguments['e0'] ?? true;
		$arguments['e1'] = $arguments['e1'] ?? true;
		$arguments['e2'] = $arguments['e2'] ?? true;
		$arguments['e3'] = $arguments['e3'] ?? true;
		$arguments['e4'] = $arguments['e4'] ?? true;
		$arguments['e5'] = $arguments['e5'] ?? true;
		$arguments['e6'] = $arguments['e6'] ?? true;
		$arguments['e7'] = $arguments['e7'] ?? true;
		$arguments['e8'] = $arguments['e8'] ?? true;
		$arguments['e9'] = $arguments['e9'] ?? true;
		$arguments['f0'] = $arguments['f0'] ?? true;
		$arguments['f1'] = $arguments['f1'] ?? true;
		$arguments['f2'] = $arguments['f2'] ?? true;
		$arguments['f3'] = $arguments['f3'] ?? true;
		$arguments['f4'] = $arguments['f4'] ?? true;
		$arguments['f5'] = $arguments['f5'] ?? true;
		$arguments['f6'] = $arguments['f6'] ?? true;
		$arguments['f7'] = $arguments['f7'] ?? true;
		$arguments['f8'] = $arguments['f8'] ?? true;
		$arguments['f9'] = $arguments['f9'] ?? true;
		$arguments['g0'] = $arguments['g0'] ?? true;
		$arguments['g1'] = $arguments['g1'] ?? true;
		$arguments['g2'] = $arguments['g2'] ?? true;
		$arguments['g3'] = $arguments['g3'] ?? true;
		$arguments['g4'] = $arguments['g4'] ?? true;
		$arguments['g5'] = $arguments['g5'] ?? true;
		$arguments['g6'] = $arguments['g6'] ?? true;
		$arguments['g7'] = $arguments['g7'] ?? true;
		$arguments['g8'] = $arguments['g8'] ?? true;
		$arguments['g9'] = $arguments['g9'] ?? true;
		$arguments['h0'] = $arguments['h0'] ?? true;
		$arguments['h1'] = $arguments['h1'] ?? true;
		$arguments['h2'] = $arguments['h2'] ?? true;
		$arguments['h3'] = $arguments['h3'] ?? true;
		$arguments['h4'] = $arguments['h4'] ?? true;
		$arguments['h5'] = $arguments['h5'] ?? true;
		$arguments['h6'] = $arguments['h6'] ?? true;
		$arguments['h7'] = $arguments['h7'] ?? true;
		$arguments['h8'] = $arguments['h8'] ?? true;
		$arguments['h9'] = $arguments['h9'] ?? true;
		$arguments['i0'] = $arguments['i0'] ?? true;
		$arguments['i1'] = $arguments['i1'] ?? true;
		$arguments['i2'] = $arguments['i2'] ?? true;
		$arguments['i3'] = $arguments['i3'] ?? true;
		$arguments['i4'] = $arguments['i4'] ?? true;
		$arguments['i5'] = $arguments['i5'] ?? true;
		$arguments['i6'] = $arguments['i6'] ?? true;
		$arguments['i7'] = $arguments['i7'] ?? true;
		$arguments['i8'] = $arguments['i8'] ?? true;
		$arguments['i9'] = $arguments['i9'] ?? true;
		$arguments['j0'] = $arguments['j0'] ?? true;
		$arguments['j1'] = $arguments['j1'] ?? true;
		$arguments['j2'] = $arguments['j2'] ?? true;
		$arguments['j3'] = $arguments['j3'] ?? true;
		$arguments['j4'] = $arguments['j4'] ?? true;
		$arguments['j5'] = $arguments['j5'] ?? true;
		$arguments['j6'] = $arguments['j6'] ?? true;
		$arguments['j7'] = $arguments['j7'] ?? true;
		$arguments['j8'] = $arguments['j8'] ?? true;
		$arguments['j9'] = $arguments['j9'] ?? true;
		$arguments['k0'] = $arguments['k0'] ?? true;
		$arguments['k1'] = $arguments['k1'] ?? true;
		$arguments['k2'] = $arguments['k2'] ?? true;
		$arguments['k3'] = $arguments['k3'] ?? true;
		$arguments['k4'] = $arguments['k4'] ?? true;
		$arguments['k5'] = $arguments['k5'] ?? true;
		$arguments['k6'] = $arguments['k6'] ?? true;
		$arguments['k7'] = $arguments['k7'] ?? true;
		$arguments['k8'] = $arguments['k8'] ?? true;
		$arguments['k9'] = $arguments['k9'] ?? true;
		$arguments['l0'] = $arguments['l0'] ?? true;
		$arguments['l1'] = $arguments['l1'] ?? true;
		$arguments['l2'] = $arguments['l2'] ?? true;
		$arguments['l3'] = $arguments['l3'] ?? true;
		$arguments['l4'] = $arguments['l4'] ?? true;
		$arguments['l5'] = $arguments['l5'] ?? true;
		$arguments['l6'] = $arguments['l6'] ?? true;
		$arguments['l7'] = $arguments['l7'] ?? true;
		$arguments['l8'] = $arguments['l8'] ?? true;
		$arguments['l9'] = $arguments['l9'] ?? true;
		$arguments['m0'] = $arguments['m0'] ?? true;
		$arguments['m1'] = $arguments['m1'] ?? true;
		$arguments['m2'] = $arguments['m2'] ?? true;
		$arguments['m3'] = $arguments['m3'] ?? true;
		$arguments['m4'] = $arguments['m4'] ?? true;
		$arguments['m5'] = $arguments['m5'] ?? true;
		$arguments['m6'] = $arguments['m6'] ?? true;
		$arguments['m7'] = $arguments['m7'] ?? true;
		$arguments['m8'] = $arguments['m8'] ?? true;
		$arguments['m9'] = $arguments['m9'] ?? true;
		$arguments['n0'] = $arguments['n0'] ?? true;
		$arguments['n1'] = $arguments['n1'] ?? true;
		$arguments['n2'] = $arguments['n2'] ?? true;
		$arguments['n3'] = $arguments['n3'] ?? true;
		$arguments['n4'] = $arguments['n4'] ?? true;
		$arguments['n5'] = $arguments['n5'] ?? true;
		$arguments['n6'] = $arguments['n6'] ?? true;
		$arguments['n7'] = $arguments['n7'] ?? true;
		$arguments['n8'] = $arguments['n8'] ?? true;
		$arguments['n9'] = $arguments['n9'] ?? true;
		$arguments['o0'] = $arguments['o0'] ?? true;
		$arguments['o1'] = $arguments['o1'] ?? true;
		$arguments['o2'] = $arguments['o2'] ?? true;
		$arguments['o3'] = $arguments['o3'] ?? true;
		$arguments['o4'] = $arguments['o4'] ?? true;
		$arguments['o5'] = $arguments['o5'] ?? true;
		$arguments['o6'] = $arguments['o6'] ?? true;
		$arguments['o7'] = $arguments['o7'] ?? true;
		$arguments['o8'] = $arguments['o8'] ?? true;
		$arguments['o9'] = $arguments['o9'] ?? true;
		$arguments['p0'] = $arguments['p0'] ?? true;
		$arguments['p1'] = $arguments['p1'] ?? true;
		$arguments['p2'] = $arguments['p2'] ?? true;
		$arguments['p3'] = $arguments['p3'] ?? true;
		$arguments['p4'] = $arguments['p4'] ?? true;
		$arguments['p5'] = $arguments['p5'] ?? true;
		$arguments['p6'] = $arguments['p6'] ?? true;
		$arguments['p7'] = $arguments['p7'] ?? true;
		$arguments['p8'] = $arguments['p8'] ?? true;
		$arguments['p9'] = $arguments['p9'] ?? true;
		$arguments['q0'] = $arguments['q0'] ?? true;
		$arguments['q1'] = $arguments['q1'] ?? true;
		$arguments['q2'] = $arguments['q2'] ?? true;
		$arguments['q3'] = $arguments['q3'] ?? true;
		$arguments['q4'] = $arguments['q4'] ?? true;
		$arguments['q5'] = $arguments['q5'] ?? true;
		$arguments['q6'] = $arguments['q6'] ?? true;
		$arguments['q7'] = $arguments['q7'] ?? true;
		$arguments['q8'] = $arguments['q8'] ?? true;
		$arguments['q9'] = $arguments['q9'] ?? true;
		$arguments['r0'] = $arguments['r0'] ?? true;
		$arguments['r1'] = $arguments['r1'] ?? true;
		$arguments['r2'] = $arguments['r2'] ?? true;
		$arguments['r3'] = $arguments['r3'] ?? true;
		$arguments['r4'] = $arguments['r4'] ?? true;
		$arguments['r5'] = $arguments['r5'] ?? true;
		$arguments['r6'] = $arguments['r6'] ?? true;
		$arguments['r7'] = $arguments['r7'] ?? true;
		$arguments['r8'] = $arguments['r8'] ?? true;
		$arguments['r9'] = $arguments['r9'] ?? true;
		$arguments['s0'] = $arguments['s0'] ?? true;
		$arguments['s1'] = $arguments['s1'] ?? true;
		$arguments['s2'] = $arguments['s2'] ?? true;
		$arguments['s3'] = $arguments['s3'] ?? true;
		$arguments['s4'] = $arguments['s4'] ?? true;
		$arguments['s5'] = $arguments['s5'] ?? true;
		$arguments['s6'] = $arguments['s6'] ?? true;
		$arguments['s7'] = $arguments['s7'] ?? true;
		$arguments['s8'] = $arguments['s8'] ?? true;
		$arguments['s9'] = $arguments['s9'] ?? true;
		$arguments['t0'] = $arguments['t0'] ?? true;
		$arguments['t1'] = $arguments['t1'] ?? true;
		$arguments['t2'] = $arguments['t2'] ?? true;
		$arguments['t3'] = $arguments['t3'] ?? true;
		$arguments['t4'] = $arguments['t4'] ?? true;
		$arguments['t5'] = $arguments['t5'] ?? true;
		$arguments['t6'] = $arguments['t6'] ?? true;
		$arguments['t7'] = $arguments['t7'] ?? true;
		$arguments['t8'] = $arguments['t8'] ?? true;
		$arguments['t9'] = $arguments['t9'] ?? true;
		$arguments['u0'] = $arguments['u0'] ?? true;
		$arguments['u1'] = $arguments['u1'] ?? true;
		$arguments['u2'] = $arguments['u2'] ?? true;
		$arguments['u3'] = $arguments['u3'] ?? true;
		$arguments['u4'] = $arguments['u4'] ?? true;
		$arguments['u5'] = $arguments['u5'] ?? true;
		$arguments['u6'] = $arguments['u6'] ?? true;
		$arguments['u7'] = $arguments['u7'] ?? true;
		$arguments['u8'] = $arguments['u8'] ?? true;
		$arguments['u9'] = $arguments['u9'] ?? true;
		$arguments['v0'] = $arguments['v0'] ?? true;
		$arguments['v1'] = $arguments['v1'] ?? true;
		$arguments['v2'] = $arguments['v2'] ?? true;
		$arguments['v3'] = $arguments['v3'] ?? true;
		$arguments['v4'] = $arguments['v4'] ?? true;
		$arguments['v5'] = $arguments['v5'] ?? true;
		$arguments['v6'] = $arguments['v6'] ?? true;
		$arguments['v7'] = $arguments['v7'] ?? true;
		$arguments['v8'] = $arguments['v8'] ?? true;
		$arguments['v9'] = $arguments['v9'] ?? true;
		$arguments['w0'] = $arguments['w0'] ?? true;
		$arguments['w1'] = $arguments['w1'] ?? true;
		$arguments['w2'] = $arguments['w2'] ?? true;
		$arguments['w3'] = $arguments['w3'] ?? true;
		$arguments['w4'] = $arguments['w4'] ?? true;
		$arguments['w5'] = $arguments['w5'] ?? true;
		$arguments['w6'] = $arguments['w6'] ?? true;
		$arguments['w7'] = $arguments['w7'] ?? true;
		$arguments['w8'] = $arguments['w8'] ?? true;
		$arguments['w9'] = $arguments['w9'] ?? true;
		$arguments['x0'] = $arguments['x0'] ?? true;
		$arguments['x1'] = $arguments['x1'] ?? true;
		$arguments['x2'] = $arguments['x2'] ?? true;
		$arguments['x3'] = $arguments['x3'] ?? true;
		$arguments['x4'] = $arguments['x4'] ?? true;
		$arguments['x5'] = $arguments['x5'] ?? true;
		$arguments['x6'] = $arguments['x6'] ?? true;
		$arguments['x7'] = $arguments['x7'] ?? true;
		$arguments['x8'] = $arguments['x8'] ?? true;
		$arguments['x9'] = $arguments['x9'] ?? true;
		$arguments['y0'] = $arguments['y0'] ?? true;
		$arguments['y1'] = $arguments['y1'] ?? true;
		$arguments['y2'] = $arguments['y2'] ?? true;
		$arguments['y3'] = $arguments['y3'] ?? true;
		$arguments['y4'] = $arguments['y4'] ?? true;
		$arguments['y5'] = $arguments['y5'] ?? true;
		$arguments['y6'] = $arguments['y6'] ?? true;
		$arguments['y7'] = $arguments['y7'] ?? true;
		$arguments['y8'] = $arguments['y8'] ?? true;
		$arguments['y9'] = $arguments['y9'] ?? true;
		$arguments['z0'] = $arguments['z0'] ?? true;
		$arguments['z1'] = $arguments['z1'] ?? true;
		$arguments['z2'] = $arguments['z2'] ?? true;
		$arguments['z3'] = $arguments['z3'] ?? true;
		$arguments['z4'] = $arguments['z4'] ?? true;
		$arguments['z5'] = $arguments['z5'] ?? true;
		$arguments['z6'] = $arguments['z6'] ?? true;
		$arguments['z7'] = $arguments['z7'] ?? true;
		$arguments['z8'] = $arguments['z8'] ?? true;
		$arguments['z9'] = $arguments['z9'] ?? true;
		echo $arguments;
	}
}
'@taint-check-debug-method-first-arg PHPUnitExample::exhaustionTest';


// Similar to the PHPUnit example above, but with a different variable at the LHS
class SameOffsetDifferentVar {
	function testStuff( $argument ) {
		$x = [];
		$x['a'] = $argument['a'];
		$x['b'] = $argument['b'];
		$x['c'] = $argument['c'];
		echo $x;
	}
}
'@taint-check-debug-method-first-arg SameOffsetDifferentVar::testStuff';
