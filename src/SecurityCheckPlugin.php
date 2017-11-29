<?php declare( strict_types=1 );

require_once __DIR__ . '/TaintednessBaseVisitor.php';
require_once __DIR__ . '/PreTaintednessVisitor.php';
require_once __DIR__ . '/TaintednessVisitor.php';

use Phan\CodeBase;
use Phan\Language\Context;
use Phan\Language\FQSEN\FullyQualifiedFunctionLikeName;
use Phan\Plugin\PluginImplementation;
use ast\Node;

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
	// To allow people to add other application specific
	// taints.
	const CUSTOM1_TAINT = 2048;
	const CUSTOM1_EXEC_TAINT = 4096;
	const CUSTOM2_TAINT = 8192;
	const CUSTOM2_EXEC_TAINT = 16384;
	// For stuff that doesn't fit another
	// category (For the moment, this is stuff like `require $foo`)
	const MISC_TAINT = 32768;
	const MISC_EXEC_TAINT = 65536;

	const YES_TAINT = 43688;
	const EXEC_TAINT = 87376;
	const YES_EXEC_TAINT = 131064;

	/**
	 * Called on every node in the AST in post-order
	 *
	 * @param CodeBase $code_base The code base in which the node exists
	 * @param Context $context The context in which the node exits.
	 * @param Node $node The php-ast Node being analyzed.
	 * @param Node $parent_node The parent node of the given node (if one exists).
	 * @return void
	 */
	public function analyzeNode(
		CodeBase $code_base,
		Context $context,
		Node $node,
		Node $parent_node = null
	) {
		$oldMem = memory_get_peak_usage();
		// This would also return the taint of the current node,
		// but we don't need that here so we discard the return value.
		$visitor = new TaintednessVisitor( $code_base, $context, $this );
		$visitor( $node );
		$newMem = memory_get_peak_usage();
		$diff = floor( ( $newMem - $oldMem ) / ( 1024 * 1024 ) );
		if ( $diff > 10 ) {
			$cur = floor( ( memory_get_usage() / ( 1024 * 1024 ) ) );
			$visitor->debug( __METHOD__, "Memory Spike! " . \ast\get_kind_name( $node->kind ) .
				" diff=$diff MB; cur=$cur MB\n"
			);
		}
	}

	/**
	 * Called on every node in the ast, but in pre-order
	 *
	 * We only need this for a couple things, namely
	 * structural elements that cause a new variable to be
	 * declared (e.g. method declarations, foreach loops)
	 *
	 * @param CodeBase $code_base
	 * @param Context $context
	 * @param Node $node
	 */
	public function preAnalyzeNode( CodeBase $code_base, Context $context, Node $node ) {
		( new PreTaintednessVisitor( $code_base, $context, $this ) )( $node );
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
	 * Can be used to force specific issues to be marked false positives
	 *
	 * For example, a specific application might be able to recognize
	 * that we are in a CLI context, and thus the XSS is really a false positive.
	 *
	 * @note The $lhsTaint parameter uses the self::*_TAINT constants,
	 *   NOT the *_EXEC_TAINT constants.
	 * @param int $lhsTaint The dangerous taints to be output (e.g. LHS of assignment)
	 * @param int $rhsTaint The taint of the expression
	 * @param string &$msg Issue description (so plugin can modify to state why false)
	 * @param Context $context
	 * @param CodeBase $code_base
	 * @return bool Is this a false positive?
	 */
	public function isFalsePositive(
		int $lhsTaint,
		int $rhsTaint,
		string &$msg,
		Context $context,
		CodeBase $code_base
	) : bool {
		return false;
	}

	/**
	 * Tains for builtin php functions
	 *
	 * @return array List of func taints (See getBuiltinFuncTaint())
	 */
	protected function getPHPFuncTaints() : array {
		return [
			'\htmlspecialchars' => [
				~self::HTML_TAINT & self::YES_TAINT,
				'overall' => self::NO_TAINT
			],
			'\escapeshellarg' => [
				~self::SHELL_TAINT & self::YES_TAINT,
				'overall' => self::NO_TAINT
			],
			// Or any time the serialized data comes from a trusted source.
			'\serialize' => [
				'overall' => self::YES_TAINT & ~self::SERIALIZE_TAINT,
			],
			'\unserialize' => [
				self::SERIALIZE_EXEC_TAINT,
				'overall' => self::NO_TAINT,
			],
			'\mysql_query' => [
				self::SQL_EXEC_TAINT,
				'overall' => self::UNKNOWN_TAINT
			],
			'\mysqli_query' => [
				self::NO_TAINT,
				self::SQL_EXEC_TAINT,
				'overall' => self::UNKNOWN_TAINT
			],
			'\mysqli::query' => [
				self::SQL_EXEC_TAINT,
				'overall' => self::UNKNOWN_TAINT
			],
			'\mysqli_real_query' => [
				self::NO_TAINT,
				self::SQL_EXEC_TAINT,
				'overall' => self::UNKNOWN_TAINT
			],
			'\mysqli::real_query' => [
				self::SQL_EXEC_TAINT,
				'overall' => self::UNKNOWN_TAINT
			],
			'\mysqli_escape_string' => [
				self::NO_TAINT,
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\mysqli_real_escape_string' => [
				self::NO_TAINT,
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\mysqli::escape_string' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
			'\mysqli::real_escape_string' => [
				self::YES_TAINT & ~self::SQL_TAINT,
				'overall' => self::NO_TAINT
			],
		];
	}
}
