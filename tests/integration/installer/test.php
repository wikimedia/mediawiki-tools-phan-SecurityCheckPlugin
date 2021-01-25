<?php

use Wikimedia\Rdbms\Database;

class User {
	public $mEmail;

	public function setEmail( $str ) {
		$this->mEmail = $str;
	}

	public function createNew( $params = [] ) {// NOTE: The default value here is important!
		$fields = [
			'user_email' => $this->mEmail,
		];
		foreach ( $params as $name2 => $value ) {
			$fields["user_$name2"] = $value;// Because of https://github.com/phan/phan/issues/4344, 'mixed' is inferred for array keys
		}
		( new Database )->insert( 'user', $fields );// This is safe because $fields doesn't have NUMKEY
	}

}

class WebInstaller {
	protected $settings;

	public function setVar( $name, $value ) {
		$this->settings[$name] = $value;
	}

	public function getVar( $name ) {
		return $this->settings[$name];
	}

	protected function createSysop() {
		$user = new User;
		$user->setEmail( $this->getVar( '_AdminEmail' ) ); // NOT an SQLi
	}

	public function setupLanguage() {
		$this->setVar( '_UserLang', $_GET['accept-language-header'] ); // NOT an SQLi
	}
}
