<?php declare(strict_types=1);

require_once( 'TaintednessBaseVisitor.php' );
require_once( 'PreTaintednessVisitor.php' );
require_once( 'TaintednessVisitor.php' );

use Phan\AST\AnalysisVisitor;
use Phan\AST\ContextNode;
use Phan\CodeBase;
use Phan\Language\Context;
use Phan\Language\Element\Clazz;
use Phan\Language\Element\Func;
use Phan\Language\Element\FunctionInterface;
use Phan\Language\Element\Method;
use Phan\Language\Element\Variable;
use Phan\Language\UnionType;
use Phan\Language\FQSEN\FullyQualifiedFunctionLikeName;
use Phan\Plugin;
use Phan\Plugin\PluginImplementation;
use ast\Node;
use ast\Node\Decl;
use Phan\Debug;
use Phan\Language\Scope\FunctionLikeScope;
use Phan\Language\Scope\BranchScope;

abstract class SecurityCheckPlugin extends PluginImplementation {


	// Various taint flags. The _EXEC_ varieties mean
	// that it is unsafe to assign that type of taint
	// to the variable in question.

	const NO_TAINT = 0;
	// For decleration type things. Given a special value for
	// debugging purposes, but inapplicable taint should not
	// actually show up anywhere.
	const INAPLICABLE_TAINT = 1;
	// Flag to denote that we don't know
	const UNKNOWN_TAINT = 2;
	// Flag for function parameters and the like, where it
	// preserves whatever taint the function is given.
	const PRESERVE_TAINT = 4;

	// In future might separate out different types of html quoting.
	// e.g. "<div data-foo='" . htmlspecialchars( $bar ) . "'>";
	// is unsafe.
	const HTML_TAINT = 8;
	const HTML_EXEC_TAINT = 16;
	const SQL_TAINT = 32;
	const SQL_EXEC_TAINT = 64;
	const SHELL_TAINT = 128;
	const SHELL_EXEC_TAINT = 256;
	const SERIALIZE_TAINT = 512;
	const SERIALIZE_EXEC_TAINT = 1024;

	const YES_TAINT = 680;
	const EXEC_TAINT = 1360;
	const YES_EXEC_TAINT = 2040;

	/**
	 * @param CodeBase $code_base
	 * The code base in which the node exists
	 *
	 * @param Context $context
	 * The context in which the node exits. This is
	 * the context inside the given node rather than
	 * the context outside of the given node
	 *
	 * @param Node $node
	 * The php-ast Node being analyzed.
	 *
	 * @param Node $node
	 * The parent node of the given node (if one exists).
	 *
	 * @return void
	 */
	public function analyzeNode(
		CodeBase $code_base,
		Context $context,
		Node $node,
		Node $parent_node = null
	) {
	//echo __METHOD__ . ' ' .\ast\get_kind_name($node->kind) . " (Parent: " . ($parent_node ? \ast\get_kind_name($parent_node->kind) : "N/A") . ")\n";
		$oldMem = memory_get_peak_usage();
		(new TaintednessVisitor($code_base, $context, $this))(
			$node
		);
		$newMem = memory_get_peak_usage();
		$diff = floor(($newMem - $oldMem )/(1024*1024));
		if ( $diff > 10 ) {
			echo "Memory Spike! " . $context . " " .\ast\get_kind_name($node->kind) .
			" diff=$diff MB; cur=" . floor((memory_get_usage()/(1024*1024))) . " MB\n";
		}
	}

	public function preAnalyzeNode( CodeBase $code_base, Context $context, Node $node ) {
		(new PreTaintednessVisitor( $code_base, $context, $this ))( $node );
	}


	/**
	 * Get the taintedness of a function
	 *
	 * This allows overriding the default taint of a function
	 *
	 * If you want to provide custom taint hints for your application,
	 * normally you would override the getCustomFuncTaints() method, not this one.
	 *
	 * @param FullyQualifiedFunctionLikeName $fqsen The function/method in question
	 * @return array|null Null to autodetect taintedness. Otherwise an array
	 *   Numeric keys reflect how the taintedness the parameter reflect the
	 *   return value, or whether the parameter is directly executed.
	 *   The special key overall controls the taint of the function
	 *   irrespective of its parameters. The overall keys must always be specified.
	 *   As a special case, if the overall key has SecurityCheckPlugin::PRESERVE_TAINT
	 *   then any unspecified keys behave like they are self::YES_TAINT.
	 *
	 *   For example: [ self::YES_TAINT, 'overall' => self::NO_TAINT ]
	 *   means that the taint of the return value is the same as the taint
	 *   of the the first arg, and all other args are ignored.
	 *   [ self::HTML_EXEC_TAINT, 'overall' => self::NO_TAINT ]
	 *   Means that the first arg is output in an html context (e.g. like echo)
	 *   [ self::YES_TAINT & ~self::HTML_TAINT, 'overall' => self::NO_TAINT ]
	 *   Means that the function removes html taint (escapes) e.g. htmlspecialchars
	 *   [ 'overall' => self::YES_TAINT ]
	 *   Means that it returns a tainted value (e.g. return $_POST['foo']; )
	 */
	public function getBuiltinFuncTaint( FullyQualifiedFunctionLikeName $fqsen ) {
		$funcTaints = $this->getCustomFuncTaints() + $this->getPHPFuncTaints();
		$name = (string)$fqsen;

		return $funcTaints[$name] ?? null;
	}

	/**
	 * Get an array of function taints custom for the application
	 *
	 * @return array Array of function taints (See getBuiltinFuncTaint())
	 */
	abstract protected function getCustomFuncTaints() : array;

	/**
	 * Tains for builtin php functions
	 *
	 * @return array List of func taints (See getBuiltinFuncTaint())
	 */
	protected function getPHPFuncTaints() : array {
		return [
			'\htmlspecialchars' => [
				~SecurityCheckPlugin::HTML_TAINT & SecurityCheckPlugin::YES_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT
			],
			'\escapeshellarg' => [
				~SecurityCheckPlugin::SHELL_TAINT & SecurityCheckPlugin::YES_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT
			],
			// Or any time the serialized data comes from a trusted source.
			'\serialize' => [
				'overall'=> self::YES_TAINT & ~self::SERIALIZE_TAINT,
			],
			'\unserialize' => [
				SecurityCheckPlugin::SERIALIZE_EXEC_TAINT,
				'overall' => SecurityCheckPlugin::NO_TAINT,
			],
		];
	}
}

