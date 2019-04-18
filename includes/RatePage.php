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

    /**
     * Gets the current number of page views from the database (integer).
     */
	public static function getPageViews( Title $title ) {
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
            array_push($pageRating, $row);
		
		return $pageRating;
    }
}
