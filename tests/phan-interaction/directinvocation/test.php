<?php

throw new InvalidArgumentException();
// Seen here:
//    PhanParamTooManyInternal Call with 1 arg(s) to \RuntimeException::__construct() which only takes 0 arg(s).
// when using $is_direct_invocation=false in getPossibleFuncDefinitions()
throw new RuntimeException( 'foo' );
