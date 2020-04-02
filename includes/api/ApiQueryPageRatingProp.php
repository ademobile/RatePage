<?php

use MediaWiki\MediaWikiServices;

class ApiQueryPageRatingProp extends ApiQueryBase {
	use RatePageApiTrait;

	/**
	 * @inheritDoc
	 */
	public function __construct( ApiQuery $queryModule, $moduleName, $paramPrefix = 'pr' ) {
		parent::__construct( $queryModule, $moduleName, $paramPrefix );
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$titles = $this->getPageSet()->getGoodTitles();
		$this->processParams( $params, $this->getContext(), $this );
		$result = $this->getResult();

		foreach ( $titles as $title ) {
			if ( !RatePageRating::canPageBeRated( $title ) ) continue;

			$path = [ 'query', 'pages', $title->getArticleID(), $this->getModuleName() ];
			$this->addTitleToResults( $title, $path, $result );
		}
	}

	/**
	 * Get the cache mode for the data generated by this module
	 *
	 * @param array $params Ignored parameters
	 * @return string Always returns "private"
	 */
	public function getCacheMode( $params ) {
		return 'private';
	}

	/**
	 * Return an array describing all possible parameters to this module
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'contest' => [ ApiBase::PARAM_TYPE => 'string' ]
		];
	}
}
