<?php

/**
 * API for getting the page rating
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class ApiPageRating extends ApiBase {
	public function execute() {
		$params = $this->extractRequestParams();
		$this->requireOnlyOneParameter( $params, 'pageid', 'pagetitle' );

        if (isset($params['pageid']))
            $title = Title::newFromID($params['pageid']);
		else $title = Title::newFromText($params['pagetitle']);
		
		$user = RequestContext::getMain()->getUser();
		if ( $user->getName() == '' ) {
			$userName = RequestContext::getMain()->getRequest()->getIP();
		} else {
			$userName = $user->getName();
		}

		$pageViews = RatePage::getPageViews($title);
		$pageRating = RatePage::getPageRating($title);
		$userVoted = RatePage::hasUserVoted($title, $userName);

		$this->getResult()->addValue( null, "pageViews", [ "viewCount" => $pageViews ] );
		$this->getResult()->addValue( null, "pageRating", $pageRating );
		$this->getResult()->addValue( null, "userVoted", ($userVoted) ? "true" : "false" );
	}

	/**
	 * Get the cache mode for the data generated by this module
	 *
	 * @param array $params Ignored parameters
	 * @return string Always returns "public"
	 */
	public function getCacheMode( $params ) {
		return 'public';
	}

	/**
	 * Return an array describing all possible parameters to this module
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'pageid' => [
				ApiBase::PARAM_TYPE => 'integer'
            ],
            'pagetitle' => [
                ApiBase::PARAM_TYPE => 'string'
            ]
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			
		];
	}
}