<?php
namespace MediaWiki\Auth;

abstract class AuthenticationRequest {
	abstract public function getFieldInfo();
}

class TestAuth extends AuthenticationRequest {

	public function getFieldInfo() {
		return [
			'campaign' => [
				'type' => 'hidden',
				'value' => 'foo',
				'label' => htmlspecialchars( $_GET['evil'] ),
				'help' => htmlspecialchars( $_GET['evil'] ),
				'optional' => true,
			],
 ];
	}
}
