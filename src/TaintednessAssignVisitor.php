<?php

namespace SecurityCheckPlugin;

use ast\Node;
use Phan\CodeBase;
use Phan\Exception\IssueException;
use Phan\Exception\NodeException;
use Phan\Exception\UnanalyzableException;
use Phan\Language\Context;
use Phan\Language\Element\GlobalVariable;
use Phan\Language\Element\Property;
use Phan\Language\Element\TypedElementInterface;
use Phan\PluginV3\PluginAwareBaseAnalysisVisitor;

/**
 * @see \Phan\Analysis\AssignmentVisitor
 */
class TaintednessAssignVisitor extends PluginAwareBaseAnalysisVisitor {
	use TaintednessBaseVisitor;

	/** @var Taintedness */
	private $rightTaint;

	/** @var Taintedness */
	private $errorTaint;

	/** @var CausedByLines */
	private $rightError;

	/** @var MethodLinks */
	private $rightLinks;

	/** @var bool */
	private $isAssignOp;

	/**
	 * List of resolved LHS offsets. NOTE: This list goes from outer to inner (i.e. with $x[1][2], the
	 * list would be [ 2, 1 ]).
	 * @var array
	 * @phan-var list<Node|mixed|null>
	 */
	private $resolvedOffsets;

	/**
	 * @inheritDoc
	 * @param Taintedness $rightTaint
	 * @param CausedByLines $rightLines
	 * @param MethodLinks $rightLinks
	 * @param Taintedness $errorTaint
	 * @param bool $isAssignOp Whether it's an assign op like .= (and not normal =)
	 * @param array $resolvedOffsets
	 * @phan-param list<Node|mixed|null> $resolvedOffsets
	 */
	public function __construct(
		CodeBase $code_base,
		Context $context,
		Taintedness $rightTaint,
		CausedByLines $rightLines,
		MethodLinks $rightLinks,
		Taintedness $errorTaint,
		bool $isAssignOp,
		array $resolvedOffsets = []
	) {
		parent::__construct( $code_base, $context );
		$this->rightTaint = $rightTaint;
		$this->rightError = $rightLines;
		$this->rightLinks = $rightLinks;
		$this->errorTaint = $errorTaint;
		$this->isAssignOp = $isAssignOp;
		$this->resolvedOffsets = $resolvedOffsets;
	}

	/**
	 * @param Node $node
	 */
	public function visitArray( Node $node ): void {
		$numKey = 0;
		foreach ( $node->children as $child ) {
			if ( $child === null ) {
				$numKey++;
				continue;
			}
			if ( !$child instanceof Node || $child->kind !== \ast\AST_ARRAY_ELEM ) {
				// Syntax error.
				return;
			}
			$key = $child->children['key'] !== null ? $this->resolveOffset( $child->children['key'] ) : $numKey++;
			$value = $child->children['value'];
			if ( !$value instanceof Node ) {
				// Syntax error, don't crash, and bail out immediately.
				return;
			}
			$childVisitor = new self(
				$this->code_base,
				$this->context,
				$this->rightTaint->getTaintednessForOffsetOrWhole( $key ),
				$this->rightError,
				$this->rightLinks,
				$this->errorTaint->getTaintednessForOffsetOrWhole( $key ),
				$this->isAssignOp,
				$this->resolvedOffsets
			);
			$childVisitor( $value );
		}
	}

	/**
	 * @param Node $node
	 */
	public function visitVar( Node $node ): void {
		try {
			$var = $this->getCtxN( $node )->getVariable();
		} catch ( NodeException | IssueException $_ ) {
			return;
		}
		$this->doAssignmentSingleElement( $var );
	}

	/**
	 * @param Node $node
	 */
	public function visitProp( Node $node ): void {
		try {
			$prop = $this->getCtxN( $node )->getProperty( false );
		} catch ( NodeException | IssueException | UnanalyzableException $_ ) {
			return;
		}
		$this->doAssignmentSingleElement( $prop );
	}

	/**
	 * @param Node $node
	 */
	public function visitStaticProp( Node $node ): void {
		try {
			$prop = $this->getCtxN( $node )->getProperty( true );
		} catch ( NodeException | IssueException | UnanalyzableException $_ ) {
			return;
		}
		$this->doAssignmentSingleElement( $prop );
	}

	/**
	 * @param Node $node
	 */
	public function visitDim( Node $node ): void {
		if ( !$node->children['expr'] instanceof Node ) {
			// Invalid syntax.
			return;
		}
		$dimNode = $node->children['dim'];
		if ( $dimNode === null ) {
			$curOff = null;
		} else {
			$curOff = $this->resolveOffset( $dimNode );
		}
		$this->resolvedOffsets[] = $curOff;
		$this( $node->children['expr'] );
	}

	/**
	 * @param TypedElementInterface $variableObj
	 */
	private function doAssignmentSingleElement(
		TypedElementInterface $variableObj
	): void {
		$lhsOffsets = array_reverse( $this->resolvedOffsets );
		$globalVarObj = $variableObj instanceof GlobalVariable ? $variableObj->getElement() : null;

		// Make sure assigning to $this->bar doesn't kill the whole prop taint.
		// Note: If there is a local variable that is a reference to another non-local variable, this will not
		// affect the non-local one (Pass by reference arguments are handled separately and work as expected).
		$override = !( $variableObj instanceof Property ) && !$globalVarObj;

		$offsetsTaint = $this->getKeysTaintednessList( $lhsOffsets );
		// In case of assign ops, add a caused-by line only with the taintedness actually being added.
		foreach ( $offsetsTaint as $keyTaint ) {
			$this->errorTaint->addKeysTaintedness( $keyTaint->get() );
		}

		$overrideTaint = $override;
		if ( $lhsOffsets ) {
			$curTaint = self::getTaintednessRaw( $variableObj );
			$newTaint = $curTaint ? clone $curTaint : Taintedness::newSafe();
			$newTaint->setTaintednessAtOffsetList( $lhsOffsets, $offsetsTaint, $this->rightTaint, $override );
			$overrideTaint = true;
		} else {
			$newTaint = $this->rightTaint;
		}
		$this->setTaintedness( $variableObj, $newTaint, $overrideTaint );

		if ( $globalVarObj ) {
			// Merge the taint on the "true" global object, too
			if ( $lhsOffsets ) {
				$curGlobalTaint = self::getTaintednessRaw( $globalVarObj );
				$newGlobalTaint = $curGlobalTaint ? clone $curGlobalTaint : Taintedness::newSafe();
				$newGlobalTaint->setTaintednessAtOffsetList( $lhsOffsets, $offsetsTaint, $this->rightTaint, false );
				$overrideGlobalTaint = true;
			} else {
				$newGlobalTaint = $this->rightTaint;
				$overrideGlobalTaint = false;
			}
			$this->setTaintedness( $globalVarObj, $newGlobalTaint, $overrideGlobalTaint );
		}

		if ( $lhsOffsets ) {
			$newLinks = self::getMethodLinksCloneOrEmpty( $variableObj );
			$newLinks->setLinksAtOffsetList( $lhsOffsets, $this->rightLinks, $override );
			$overrideLinks = true;
		} else {
			$newLinks = $this->rightLinks;
			$overrideLinks = $override && !$this->isAssignOp;
		}
		$this->mergeTaintDependencies( $variableObj, $newLinks, $overrideLinks );
		if ( $globalVarObj ) {
			// Merge dependencies on the global copy as well
			if ( $lhsOffsets ) {
				$newGlobalLinks = self::getMethodLinksCloneOrEmpty( $globalVarObj );
				$newGlobalLinks->setLinksAtOffsetList( $lhsOffsets, $this->rightLinks, false );
				$overrideGlobalLinks = true;
			} else {
				$newGlobalLinks = $this->rightLinks;
				$overrideGlobalLinks = false;
			}
			$this->mergeTaintDependencies( $globalVarObj, $newGlobalLinks, $overrideGlobalLinks );
		}

		$overrideError = $override && !$this->isAssignOp && !$lhsOffsets;

		if ( $overrideError ) {
			self::clearTaintError( $variableObj );
		}
		$this->addTaintError( $this->errorTaint, $variableObj );
		$this->mergeTaintError( $variableObj, $this->rightError );
		if ( $globalVarObj ) {
			$this->addTaintError( $this->errorTaint, $globalVarObj );
			$this->mergeTaintError( $globalVarObj, $this->rightError );
		}
	}
}
