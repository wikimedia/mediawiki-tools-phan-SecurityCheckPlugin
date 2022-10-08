<?php
interface IFoo {
	/**
	 * @param-taint $t escapes_html
	 */
	public function escapeHTML( $t );

	/**
	 * @return-taint html
	 */
	public function getUnsafeHTML();

	/**
	 * @return-taint Tainted
	 */
	public function getUserInput();

	/**
	 * @param-taint $query exec_SQL
	 * @return bool
	 */
	public function doQuery( $query );

	/**
	 * @param-taint $line exec_shell, array_ok
	 */
	public function wfShellExec2( $line );

	/**
	 * @return-taint onlysafefor_sql
	 */
	public function getSomeSQL();

	/**
	 * @param-taint $foo none
	 */
	public function safeOutput( $foo );

	/**
	 * @return-taint none
	 */
	public function getSafeString();

	/**
	 * @return-taint fdasdfa_html
	 */
	public function invalidTaint();

	/**
	 * @param-taint $t exec_sql,exec_shell,exec_custom1,exec_htmlnoent
	 */
	public function multiTaint( $t );

	/**
	 * @param-taint $foo none
	 * @param-taint &$bar exec_html
	 */
	public function passbyRef( $foo, &$bar );
}
