<?php

// NOTE: This file should be analyzed BEFORE Core.php, so don't rename it

class CentralAuthHooks {
	public static function onSpecialContributionsBeforeMainOutput( OutputPage $out ) {
		LogEventsList::showLogExtract( $out );
	}

	public static function getBlockLogLink() {
		Html::rawElement( 'span', [], getEscaped() );
	}

}

class GlobalUserMergeLogger {
	public function log() {
		$cache = new MapCacheLRU();
		$cache->setField( $_GET['a'], $_GET['a'], $_GET['a'] );
	}
}

class SpecialMergeAccount {
	private function doDryRunMerge() {
		Html::rawElement( 'p', [], Html::element( 'i', getUnsafe() ) );
	}

	private function doAttachMerge( OutputPage $out ) {
		$out->addHTML( Html::rawElement( 'div', [], getEscaped() ) );
	}
}

class SpecialMultiLock {
	public function execute( $subpage ) {
		$this->showUserTable();
		$this->showLogExtract();
	}

	private function showUserTable() {
		$cache = new MapCacheLRU();
		echo Html::rawElement( 'td', [], $cache->get( $_GET['a'] ) );// Must have Core line 11 in its caused-by
	}

	private function showLogExtract() {
		$text = '';
		LogEventsList::showLogExtract( $text );
	}

}
