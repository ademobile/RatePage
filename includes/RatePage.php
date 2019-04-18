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
		$page_views = self::getPageViews($title);
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

		return $page_views + 1;
	}
}
