<?php

class RatePageContestDB {

	public static function loadContest( $contest ) {
		$dbr = wfGetDB( DB_REPLICA );
		$contest = $dbr->selectRow(
			[
				'ratepage_contest',
				'ratepage_vote'
			],
			'*',
			[
				'rpc_id' => $contest,
			],
			__METHOD__
		);

		return $contest;
	}

	public static function loadVotes( $contest ) {
		$votes = [];
		$dbr = wfGetDB( DB_REPLICA );

		$votesRes = $dbr->select(
			[
				'ratepage_vote'
			],
			[
				'rv_page_id',
				'rv_answer',
				'answer_count' => 'COUNT(rv_user)'
			],
			[
				'rv_contest' => $contest
			],
			__METHOD__,
			[
				'GROUP BY' => 'rv_page_id',
			]
		);

		if ( !empty( $votesRes ) ) {
			foreach ( $votesRes as $res ) {
				$votes[$res->rv_page_id][$res->rv_answer] = $res->answer_count;
			}
		}

		return $votes;
	}

	public static function saveContest( $newRow, IContextSource $context ) {
		//TODO: do some upsert magic
	}

	public static function validateId( $id ) {
		if ( strlen( $id ) > 255 ) {
			return 'ratePage-contest-id-too-long';
		}
		if ( strlen( $id ) > 0 && !ctype_alnum( $id ) ) {
			return 'ratePage-contest-id-invalid';
		}
	}
}