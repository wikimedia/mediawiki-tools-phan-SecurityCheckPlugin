<?php
use Wikimedia\Rdbms\MysqlDatabase;

class MainClass {
	/**
	 * @var int
	 */
	protected $id = null;

	/**
	 * @var string
	 */
	protected $name = null;

	/**
	 * @param int $id
	 * @return self
	 */
	public static function fromId( $id ) {
		$obj = new self();
		$obj->id = $id;
		return $obj;
	}

	/**
	 * @param string $name
	 * @return self
	 */
	public static function fromName( $name ) {
		$obj = new self();
		$obj->name = $name;
		return $obj;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	public static function doQuery() {
		// $id will be null here, so it's safe
		$val = self::fromName( 'foo' )->getId();

		$dbr = new MysqlDatabase;

		$dbr->selectRow(
			"foo",
			'',
			[ "field = $val" ],
			__METHOD__
		);
	}
}
