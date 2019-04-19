<?php
/**
 * RatePage core code
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class RatePage {
	const PROP_NAME = 'page_views';
    const MIN_RATING = 1;
    const MAX_RATING = 5;

    /**
     * Gets the current number of page views from the database (integer).
     */
	public static function getPageViews( Title $title ) {
        if ($title->getArticleID() < 0) 
            return 0;   //no such page

		$dbr = wfGetDB( DB_REPLICA );
		$page_views = $dbr->selectField( 'page_props',
			'pp_value',
			[
				'pp_page' => $title->getArticleID(),
				'pp_propname' => self::PROP_NAME
			],
			__METHOD__
		);

		if ($page_views == false)
			return 0;
		
		return (int) $page_views;
	}

    /**
     * Increments the page views counter in the database by one.
     * If there is no page views record for the specified page in the DB, a new one is created.
     */
	public static function updatePageViews( Title $title ) {
        if ($title->getArticleID() < 0) 
            return 0;   //no such page

        $dbw = wfGetDB( DB_MASTER );
        $dbw->startAtomic( __METHOD__ );

        $pv = $dbw->selectField( 'page_props',
            'pp_value',
            [
                'pp_page' => $title->getArticleID(),
                'pp_propname' => self::PROP_NAME
            ],
            __METHOD__
        );

        if ($pv == false) $page_views = 0;
        else $page_views = (int) $pv;

		if ($page_views == 0)
		{
			$dbw->insert( 'page_props',
				[
					'pp_page' => $title->getArticleID(),
					'pp_propname' => self::PROP_NAME,
					'pp_value' => '1'
				],
				__METHOD__
			);
		}
		else
		{
			$dbw->update( 'page_props',
				[ 'pp_value' => strval($page_views + 1) ],
				[
					'pp_page' => $title->getArticleID(),
					'pp_propname' => self::PROP_NAME
				],
				__METHOD__
			);
		}

        $dbw->endAtomic( __METHOD__ );
		return $page_views + 1;
    }
    
    public static function getPageRating( Title $title ) {
        if ($title->getArticleID() < 0) 
            return [];   //no such page

        $dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select( 'ratepage_vote',
			[ 'rv_answer as answer', "count(rv_page_id) as 'count'" ],
			[
				'rv_page_id' => $title->getArticleID()
			],
            __METHOD__,
            [ 
                'GROUP BY' => 'rv_answer',
                'ORDER BT' => 'rv_answer'
            ]
        );
        
        $pageRating = [];
        foreach ($res as $row)
            $pageRating[$row->answer] = (int) $row->count;

        for ($i=self::MIN_RATING; $i<=self::MAX_RATING; $i++)
            if (!isset($pageRating[$i]))
                $pageRating[$i] = 0;
		
		return $pageRating;
    }

    public static function getUserVote( Title $title, string $user, string $ip ) {
        if ($title->getArticleID() < 0) 
            return false;   //no such page

        $dbr = wfGetDB( DB_REPLICA );
        $res = $dbr->selectField( 'ratepage_vote',
            'rv_answer',
            [
                'rv_page_id' => $title->getArticleID(),
                'rv_user' => $user
            ],
            __METHOD__
        );
        if ($res != false && !is_null($res)) return $res;

        if ($ip != $user)
        {
            $res = $dbr->selectField( 'ratepage_vote',
                'rv_answer',
                [
                    'rv_page_id' => $title->getArticleID(),
                    'rv_ip' => $ip
                ],
                __METHOD__
            );
            if ($res != false && !is_null($res)) return $res;
        }

        return -1;
    }

    /**
     * Vote on a page. Returns whether the vote was successful.
     */
    public static function voteOnPage( Title $title, string $user, string $ip, int $answer ) {
        if ($title->getArticleID() < 0) 
            return false;   //no such page

        //check whether the user has voted during a transaction to avoid a duplicate vote
        $dbw = wfGetDB( DB_MASTER );
        $dbw->startAtomic( __METHOD__ );
        $res = $dbw->selectField( 'ratepage_vote',
            'count(rv_user)',
            [
                'rv_page_id' => $title->getArticleID(),
                'rv_user' => $user
            ],
            __METHOD__
        );
        if ($res > 0) {
            //the user has already voted, change the vote
            $dbw->update(
                'ratepage_vote',
                [
                    'rv_answer' => $answer
                ],
                [
                    'rv_page_id' => $title->getArticleID(),
                    'rv_user' => $user
                ],
                __METHOD__
            );

            $dbw->endAtomic( __METHOD__ );
            return true;
        }

        if ($ip != $user)
        {
            $res = $dbw->selectField( 'ratepage_vote',
                'count(rv_ip)',
                [
                    'rv_page_id' => $title->getArticleID(),
                    'rv_ip' => $ip
                ],
                __METHOD__
            );
            if ($res > 0) {
                //the IP has already voted, but the user is now logged in, reject
                $dbw->endAtomic( __METHOD__ );
                return false;
            }
        }

        //insert the vote
        $dbw->insert( 'ratepage_vote',
            [
                'rv_page_id' => $title->getArticleID(),
                'rv_user' => $user,
                'rv_ip' => $ip,
                'rv_answer' => $answer,
                'rv_date' => date('Y-m-d H:i:s')
            ],
            __METHOD__
        );

        $dbw->endAtomic( __METHOD__ );
        return true;
    }
}
