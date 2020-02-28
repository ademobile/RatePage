<?php

class RatePageContestLogFormatter extends LogFormatter {

	/**
	 * @return string
	 */
	protected function getMessageKey() {
		$subtype = $this->entry->getSubtype();
		return "logentry-ratepage-contest-$subtype";
	}

	/**
	 * @return array
	 */
	protected function extractParameters() {
		$parameters = $this->entry->getParameters();
		if ( $this->entry->isLegacy() ) {
			list( $contestId ) = $parameters;
		} else {
			$contestId = $parameters['id'];
		}

		$params = [];
		$params[3] = Message::rawParam(
			$this->makePageLink(
				$this->entry->getTarget(),
				[],
				$this->msg( 'ratePage-log-contest-formatter' )
					->numParams( $contestId )->escaped()
			)
		);

		return $params;
	}
}