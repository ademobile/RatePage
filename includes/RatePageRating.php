<?php

/**
 * RatePage page rating code
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class RatePageRating {

	/**
	 * @param Title $title
	 * @return bool
	 */
	public static function canPageBeRated( Title $title ) {
		global $wgRPRatingAllowedNamespaces, $wgRPRatingPageBlacklist;

		if ( $title->getArticleID() < 1 ) {
			return false;
		}   //no such page

		if ( $title->isRedirect() ) {
			return false;
		}

		if ( !is_null( $wgRPRatingAllowedNamespaces ) && !in_array( $title->getNamespace(), $wgRPRatingAllowedNamespaces ) ) {
			return false;
		}

		if ( !is_null( $wgRPRatingPageBlacklist ) && ( in_array( $title->getFullText(), $wgRPRatingPageBlacklist ) || in_array( str_replace( " ", "_", $title->getFullText() ), $wgRPRatingPageBlacklist ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param Title $title
	 * @param string|null $contest
	 * @return array
	 */
	public static function getPageRating( Title $title, string $contest = '' ) {
		global $wgRPRatingMin, $wgRPRatingMax;

		if ( $title->getArticleID() < 0 ) {
			return [];
		}   //no such page

		$where = [ 'rv_page_id' => $title->getArticleID(), 'rv_contest' => $contest ];

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'ratepage_vote',
			[
				'rv_answer as answer',
				"count(rv_page_id) as 'count'"
			],
			$where,
			__METHOD__,
			[
				'GROUP BY' => 'rv_answer',
				'ORDER BT' => 'rv_answer'
			]
		);

		$pageRating = [];
		for ( $i = $wgRPRatingMin; $i <= $wgRPRatingMax; $i++ ) {
			$pageRating[$i] = 0;
		}

		foreach ( $res as $row ) {
			$pageRating[$row->answer] = (int) $row->count;
		}

		return $pageRating;
	}

	/**
	 * @param Title $title
	 * @param string $user
	 * @param string|null $contest
	 * @return bool|int
	 */
	public static function getUserVote( Title $title, string $user, string $contest = '' ) {
		if ( $title->getArticleID() < 0 ) {
			return false;
		}   //no such page

		$where = [ 'rv_page_id' => $title->getArticleID(), 'rv_user' => $user, 'rv_contest' => $contest ];

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->selectField(
			'ratepage_vote',
			'rv_answer',
			$where,
			__METHOD__
		);

		if ( $res != false && !is_null( $res ) ) {
			return (int) $res;
		}

		return -1;
	}

	/**
	 * Vote on a page. Returns bool indicating whether the vote was successful.
	 * @param Title $title
	 * @param string $user
	 * @param string $ip
	 * @param int $answer
	 * @param string|null $contest
	 * @return bool
	 */
	public static function voteOnPage( Title $title, string $user, string $ip, int $answer, string $contest = '' ) {
		if ( $title->getArticleID() < 0 ) {
			return false;
		}   //no such page

		$where = [ 'rv_page_id' => $title->getArticleID(), 'rv_user' => $user, 'rv_contest' => $contest ];

		//check whether the user has voted during a transaction to avoid a duplicate vote
		$dbw = wfGetDB( DB_MASTER );
		$dbw->startAtomic( __METHOD__ );
		$res = $dbw->selectField(
			'ratepage_vote',
			'count(rv_user)',
			$where,
			__METHOD__
		);

		if ( $res > 0 ) {
			//the user has already voted, change the vote
			$dbw->update(
				'ratepage_vote',
				[
					'rv_answer' => $answer,
					'rv_date' => date( 'Y-m-d H:i:s' )
				],
				$where,
				__METHOD__
			);

			$dbw->endAtomic( __METHOD__ );
			return true;
		}

		//insert the vote
		$dbw->insert(
			'ratepage_vote',
			[
				'rv_page_id' => $title->getArticleID(),
				'rv_user' => $user,
				'rv_ip' => $ip,
				'rv_answer' => $answer,
				'rv_date' => date( 'Y-m-d H:i:s' ),
				'rv_contest' => $contest
			],
			__METHOD__
		);

		$dbw->endAtomic( __METHOD__ );
		return true;
	}
}