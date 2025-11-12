<?php
/*
 * For T249519
 */

namespace {
	class TextSlotDiffRenderer {
		protected function getTextDiffInternal() {
			$result = new \MediaWiki\Shell\Result;
			htmlspecialchars( $result->getStderr() );
		}
	}

}

namespace MediaWiki\Shell {

	class Result {
		private $stderr;

		public function getStderr(): string {
			return $this->stderr; // No "Variable $this is undeclared" here
		}
	}
}
