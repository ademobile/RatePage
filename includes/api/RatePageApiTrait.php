<?php

/**
 * Trait RatePageApiTrait
 * Common code for both API endpoints.
 */
trait RatePageApiTrait {
	/**
	 * @var string
	 */
	protected $contest;

	/**
	 * @var array
	 */
	protected $permissions;

	/**
	 * @var string
	 */
	protected $userName;

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * @var string
	 */
	protected $ip;

	/**
	 * Processes the contest parameter and checks user permissions.
	 * @param array $params
	 * @param IContextSource $context
	 * @param ApiBase $parent
	 * @throws ApiUsageException
	 * @throws MWException
	 */
	protected function processParams( array &$params, IContextSource &$context, ApiBase &$parent ) : void {
		$this->user = $context->getUser();
		$this->ip = $context->getRequest()->getIP();
		if ( $this->user->getName() == '' ) {
			$this->userName = $this->ip;
		} else {
			$this->userName = $this->user->getName();
		}

		$this->contest = '';
		$this->permissions = [
			'vote' => true,
			'see' => true
		];

		if ( isset( $params['contest'] ) ) {
			$this->contest = trim( $params['contest'] );
			if ( strlen( $this->contest ) > 255 ) {
				$parent->dieWithError( 'Contest ID length exceeds the limit (255 characters)' );
			}
			if ( strlen( $this->contest ) > 0 ) {
				if ( !ctype_alnum( $this->contest ) ) {
					$parent->dieWithError( 'Contest ID must be alphanumeric, no other characters are allowed' );
				}

				$this->permissions = RatePageRights::checkUserPermissionsOnContest( $this->contest, $this->user );
			}
		}
	}

	/**
	 * Adds a title to API results given a path.
	 * @param Title $title
	 * @param array|null $path
	 * @param ApiResult $result
	 */
	protected function addTitleToResults( Title $title, ?array $path, ApiResult &$result ) {
		$userVote = RatePageRating::getUserVote( $title, $this->userName, $this->contest );

		if ( $this->permissions['see'] ) {
			$pageRating = RatePageRating::getPageRating( $title, $this->contest );
			$result->addValue( $path, "pageRating", $pageRating );
		}

		$result->addValue( $path, "userVote", $userVote );
		$result->addValue( $path, 'canVote', (int) $this->permissions['vote'] );
		$result->addValue( $path, 'canSee', (int) $this->permissions['see'] );
	}
}
