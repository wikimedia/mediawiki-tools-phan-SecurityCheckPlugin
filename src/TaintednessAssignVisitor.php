<?php

namespace SecurityCheckPlugin;

use ast\Node;
use Phan\CodeBase;
use Phan\Exception\IssueException;
use Phan\Exception\NodeException;
use Phan\Exception\UnanalyzableException;
use Phan\Language\Context;
use Phan\Language\Element\TypedElementInterface;
use Phan\PluginV3\PluginAwareBaseAnalysisVisitor;

/**
 * @see \Phan\Analysis\AssignmentVisitor
 */
class TaintednessAssignVisitor extends PluginAwareBaseAnalysisVisitor {
	use TaintednessBaseVisitor;

	/** @var Taintedness */
	private $allRightTaint;

	/** @var TaintednessWithError */
	private $rightTaint;

	/** @var bool */
	private $allowClearLHSData;

	/**
	 * List of resolved LHS offsets. NOTE: This list goes from outer to inner (i.e. with $x[1][2], the
	 * list would be [ 2, 1 ]).
	 * @var array
	 * @phan-var list<Node|mixed|null>
	 */
	private $resolvedOffsets;

	/**
	 * @inheritDoc
	 * @param Taintedness $allRightTaint
	 * @param TaintednessWithError $rightTaint
	 * @param bool $allowClearLHSData
	 * @param array $resolvedOffsets
	 * @phan-param list<Node|mixed|null> $resolvedOffsets
	 */
	public function __construct(
		CodeBase $code_base,
		Context $context,
		Taintedness $allRightTaint,
		TaintednessWithError $rightTaint,
		bool $allowClearLHSData,
		array $resolvedOffsets = []
	) {
		parent::__construct( $code_base, $context );
		$this->allRightTaint = $allRightTaint;
		$this->rightTaint = $rightTaint;
		$this->allowClearLHSData = $allowClearLHSData;
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
				$this->allRightTaint->getTaintednessForOffsetOrWhole( $key ),
				new TaintednessWithError(
					$this->rightTaint->getTaintedness()->getTaintednessForOffsetOrWhole( $key ),
					$this->rightTaint->getError(),
					$this->rightTaint->getMethodLinks()
				),
				$this->allowClearLHSData,
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
		$this->handlePhanObject( $var );
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
		$this->handlePhanObject( $prop );
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
		$this->handlePhanObject( $prop );
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
	 * @param TypedElementInterface $obj
	 */
	private function handlePhanObject( TypedElementInterface $obj ): void {
		$offsets = array_reverse( $this->resolvedOffsets );
		$this->doAssignmentSingleElement(
			$obj,
			$this->allRightTaint,
			$this->rightTaint->getTaintedness(),
			$offsets,
			$this->allowClearLHSData
		);
		$this->setTaintDependenciesInAssignment( $this->rightTaint, $obj, $offsets );
	}
}
