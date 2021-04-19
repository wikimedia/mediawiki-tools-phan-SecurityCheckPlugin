<?php

namespace SecurityCheckPlugin;

use ast\Node;
use Phan\CodeBase;
use Phan\Exception\IssueException;
use Phan\Exception\NodeException;
use Phan\Language\Context;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\Property;
use Phan\Language\Element\TypedElementInterface;
use Phan\Language\Element\Variable;
use Phan\Language\FQSEN\FullyQualifiedFunctionLikeName;
use Phan\PluginV3\PluginAwareBaseAnalysisVisitor;

/**
 * This visitor takes a `return` statement and links what's being returned to the current function,
 * trying to determine which parameter the taintedness came from (if any).
 *
 * @note Callers should use $this->getNewFuncTaint() as the only entry point after __construct. Some class members
 * are set there, and asserting that they're non-null everywhere would be very expensive.
 *
 * @todo Perhaps this visitor is unnecessary. We might use whatever taintedness the returned expr has as a whole.
 * Especially if we stop setting the taintedness of each object in the FunctionTaintedness, see todo in
 * handleReturnedElement.
 */
class MatchReturnToParamVisitor extends PluginAwareBaseAnalysisVisitor {
	use TaintednessBaseVisitor;

	/** @var FullyQualifiedFunctionLikeName The FQSEN of the current function */
	private $curFuncFQSEN;

	/** @var Taintedness */
	private $retTaintedness;

	/** @var FunctionTaintedness The final object with taintedness attributed to each param */
	private $paramTaint;

	/**
	 * Offsets accumulated so far.
	 * @warning These are from right to left, i.e. for $x['a']['b'] it's [ 'b', 'a' ], which is the opposite
	 * of what we need to add.
	 * @var array<Node|int|string|null>
	 */
	private $currentOffsets = [];

	/**
	 * @inheritDoc
	 */
	public function __construct(
		CodeBase $code_base,
		Context $context,
		FunctionInterface $curFunc,
		Taintedness $retTaintedness
	) {
		parent::__construct( $code_base, $context );
		$this->curFuncFQSEN = $curFunc->getFQSEN();
		$this->retTaintedness = $retTaintedness;
	}

	/**
	 * @param Node $node
	 * @return FunctionTaintedness
	 */
	public function getNewFuncTaint( Node $node ) : FunctionTaintedness {
		assert( $node->kind === \ast\AST_RETURN );
		$retExpr = $node->children['expr'];
		if ( !$retExpr instanceof Node ) {
			assert( $this->retTaintedness->isSafe() );
			return new FunctionTaintedness( $this->retTaintedness );
		}

		$this->paramTaint = new FunctionTaintedness( Taintedness::newUnknown() );
		$this->currentOffsets = [];

		$this( $retExpr );

		$this->paramTaint->setOverall(
			$this->retTaintedness->without( SecurityCheckPlugin::PRESERVE_TAINT )
		);
		return $this->paramTaint;
	}

	/**
	 * @inheritDoc
	 */
	public function visitProp( Node $node ) : void {
		$this->handleReturnedElement( $this->getPropFromNode( $node ) );
	}

	/**
	 * @inheritDoc
	 */
	public function visitNullsafeProp( Node $node ) : void {
		$this->handleReturnedElement( $this->getPropFromNode( $node ) );
	}

	/**
	 * @inheritDoc
	 */
	public function visitStaticProp( Node $node ) : void {
		$this->handleReturnedElement( $this->getPropFromNode( $node ) );
	}

	/**
	 * @inheritDoc
	 */
	public function visitVar( Node $node ) : void {
		$cn = $this->getCtxN( $node );
		if ( Variable::isHardcodedGlobalVariableWithName( $cn->getVariableName() ) ) {
			return;
		}
		try {
			$this->handleReturnedElement( $cn->getVariable() );
		} catch ( NodeException | IssueException $e ) {
			$this->debug( __METHOD__, "variable not in scope?? " . $this->getDebugInfo( $e ) );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitEncapsList( Node $node ) : void {
		foreach ( $node->children as $child ) {
			if ( !is_object( $child ) ) {
				continue;
			}
			$this->recurse( $child );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitArray( Node $node ) : void {
		foreach ( $node->children as $child ) {
			if ( !is_object( $child ) ) {
				continue;
			}
			$this->recurse( $child );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitArrayElem( Node $node ) : void {
		if ( is_object( $node->children['key'] ) ) {
			$this->recurse( $node->children['key'] );
		}
		if ( is_object( $node->children['value'] ) ) {
			$this->recurse( $node->children['value'] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitCast( Node $node ) : void {
		// Future todo might be to ignore casts to ints, since
		// such things should be safe. Unclear if that makes
		// sense in all circumstances.
		if ( $node->children['expr'] instanceof Node ) {
			$this->recurse( $node->children['expr'] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitDim( Node $node ) : void {
		if ( $node->children['expr'] instanceof Node ) {
			// For now just consider the outermost array.
			// FIXME. doesn't handle tainted array keys!
			$offs = $node->children['dim'];
			$realOffs = $offs !== null ? $this->resolveOffset( $offs ) : null;
			$this->recurse( $node->children['expr'], [ $realOffs ] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitUnaryOp( Node $node ) : void {
		if ( $node->children['expr'] instanceof Node ) {
			$this->recurse( $node->children['expr'] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitBinaryOp( Node $node ) : void {
		if ( $node->children['left'] instanceof Node ) {
			$this->recurse( $node->children['left'] );
		}
		if ( $node->children['right'] instanceof Node ) {
			$this->recurse( $node->children['right'] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitConditional( Node $node ) : void {
		if ( $node->children['true'] instanceof Node ) {
			$this->recurse( $node->children['true'] );
		}
		if ( $node->children['false'] instanceof Node ) {
			$this->recurse( $node->children['false'] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function visitPreDec( Node $node ) : void {
		$this->handleIncOrDec( $node );
	}

	/**
	 * @inheritDoc
	 */
	public function visitPreInc( Node $node ) : void {
		$this->handleIncOrDec( $node );
	}

	/**
	 * @inheritDoc
	 */
	public function visitPostDec( Node $node ) : void {
		$this->handleIncOrDec( $node );
	}

	/**
	 * @inheritDoc
	 */
	public function visitPostInc( Node $node ) : void {
		$this->handleIncOrDec( $node );
	}

	/**
	 * @param Node $node
	 */
	private function handleIncOrDec( Node $node ) : void {
		$children = $node->children;
		assert( count( $children ) === 1 );
		$this->recurse( reset( $children ) );
	}

	/**
	 * Wrapper for __invoke. Allows changing the current offsets, and restoring later.
	 *
	 * @param Node $node
	 * @param array<Node|int|string|null> $addedOffsets
	 */
	private function recurse( Node $node, array $addedOffsets = [] ) : void {
		if ( !$addedOffsets ) {
			$this( $node );
			return;
		}
		$oldOffs = $this->currentOffsets;
		$this->currentOffsets = array_merge( $this->currentOffsets, $addedOffsets );
		try {
			$this( $node );
		} finally {
			$this->currentOffsets = $oldOffs;
		}
	}

	/**
	 * @param TypedElementInterface|null $element
	 */
	private function handleReturnedElement( ?TypedElementInterface $element ) : void {
		if ( !$element ) {
			return;
		}
		$pobjTaintContribution = $this->getTaintednessPhanObj( $element )
			->withOnly( SecurityCheckPlugin::PRESERVE_TAINT );
		// $this->debug( __METHOD__, "taint for $pobj is $pobjTaintContribution" );
		$links = self::getMethodLinks( $element );
		if ( !$links ) {
			// No method links.
			// $this->debug( __METHOD__, "no method links for $pobj in " . $curFunc->getFQSEN() );
			// If its a non-private property, try getting parent class
			if ( $element instanceof Property && !$element->isPrivate() ) {
				$this->debug( __METHOD__, "FIXME should check parent class of $element" );
			}
			return;
		}
		// Note that we're reversing the array, see doc of the class prop.
		for ( $i = count( $this->currentOffsets ) - 1; $i >= 0; $i-- ) {
			$links = $links->getForDim( $this->currentOffsets[$i] );
		}
		$links = $links->getLinks();

		foreach ( $links as $func ) {
			if ( $func->getFQSEN() === $this->curFuncFQSEN ) {
				$paramInfo = $links[$func];
				// Note, not forCaller, as that doesn't see variadic parameters
				$calleeParamList = $func->getParameterList();
				foreach ( $paramInfo->getParams() as $i => $offsets ) {
					$pTaint = $pobjTaintContribution->asMovedAtRelevantOffsets( $offsets )->asPreservedTaintedness();
					// TODO: Is there any point in setting $pTaint here? Should we just set PRESERVE instead?
					// But then, can we track what taint is being removed before the argument is returned?
					// And can we track caused-by lines for that?
					if ( isset( $calleeParamList[$i] ) && $calleeParamList[$i]->isVariadic() ) {
						$this->paramTaint->setVariadicParamPreservedTaint( $i, $pTaint );
					} else {
						$this->paramTaint->setParamPreservedTaint( $i, $pTaint );
					}
				}
			}
		}
	}
}
