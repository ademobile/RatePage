<?php

namespace RatePage\Api;

use ApiBase;
use MediaWiki\MediaWikiServices;
use RatePage\Rating;
use Title;

/**
 * API for getting the page rating and voting for pages
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class RatePage extends ApiBase {
	use RatePageApiTrait;

	public function execute() {
		$params = $this->extractRequestParams();
		$this->requireOnlyOneParameter( $params,
			'pageid',
			'pagetitle' );

		if ( isset( $params['pageid'] ) ) {
			$title = Title::newFromID( $params['pageid'] );
		} else {
			$title = Title::newFromText( $params['pagetitle'] );
		}

		if ( is_null( $title ) || $title->getArticleID() < 1 ) {
			$this->dieWithError( 'Specified page does not exist' );
		}

		$this->getResult()->addValue( null,
			"pageId",
			$title->getArticleID() );

		$this->processParams( $params,
			$this->getContext(),
			$this );

		if ( !$this->contest && !Rating::canPageBeRated( $title ) ) {
			return;
		}

		$config = MediaWikiServices::getInstance()->getMainConfig();
		$ratingMin = $config->get( 'RPRatingMin' );
		$ratingMax = $config->get( 'RPRatingMax' );

		if ( isset( $params['answer'] ) ) {
			if ( !$this->permissions['vote'] ) {
				$this->dieWithError( 'You do not have permissions to vote in this contest' );
			}

			if ( $this->user->pingLimiter( 'ratepage' ) ) {
				$this->dieWithError( 'Rate limit for voting exceeded, please try again later' );
			}

			$answer = $params['answer'];
			if ( $answer < $ratingMin || $answer > $ratingMax ) {
				$this->dieWithError( 'Incorrect answer specified' );
			}
			Rating::voteOnPage( $title,
				$this->userName,
				$this->ip,
				$answer,
				$this->contest );
		}

		$this->addTitleToResults( $title,
			null,
			$this->getResult() );
	}

	/**
	 * Get the cache mode for the data generated by this module
	 *
	 * @param array $params Ignored parameters
	 *
	 * @return string Always returns "private"
	 */
	public function getCacheMode( $params ) {
		return 'private';
	}

	/**
	 * Return an array describing all possible parameters to this module
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return [ 'pageid' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'pagetitle' => [ ApiBase::PARAM_TYPE => 'string' ],
			'answer' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'contest' => [ ApiBase::PARAM_TYPE => 'string' ] ];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [ 'action=ratepage&pagetitle=Example' => 'apihelp-ratepage-example-1',
			'action=ratepage&pagetitle=Example&answer=2' => 'apihelp-ratepage-example-2' ];
	}

	public function isWriteMode() {
		return true;
	}
}