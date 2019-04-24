<?php
/**
 * RatePage view count tracking code
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class RatePageViews
{
    const PROP_NAME = 'page_views';

    private static function getCurrentDay()
    {
        return (int) date('Ymd');
    }

    public static function canPageBeTracked(Title $title)
    {
        global $wgRPViewTrackingAllowedNamespaces;

        if ($title->getArticleID() < 0)
            return false;   //no such page

        if (
            !is_null($wgRPViewTrackingAllowedNamespaces) &&
            !in_array($title->getNamespace(), $wgRPViewTrackingAllowedNamespaces)
        )
            return false;

        return true;
    }

    /**
     * Gets the current number of page views from the database (integer).
     */
    public static function getPageViews(Title $title)
    {
        if ($title->getArticleID() < 0)
            return 0;   //no such page

        $dbr = wfGetDB(DB_REPLICA);
        $pageViews = $dbr->selectField(
            'ratepage_stats',
            'sum(rs_hits)',
            [
                'rs_page_id' => $title->getArticleID()
            ],
            __METHOD__
        );

        if ($pageViews == false)
            return 0;

        return (int)$pageViews;
    }

    /**
     * Increments the page views counter in the database by one.
     * If there is no page views record for the specified page in the DB, a new one is created.
     */
    public static function updatePageViews(Title $title)
    {
        if ($title->getArticleID() < 0)
            return 0;   //no such page

        $dbw = wfGetDB(DB_MASTER);
        $dbw->startAtomic(__METHOD__);

        $pv = $dbw->selectField(
            'ratepage_stats',
            'rs_hits',
            [
                'rs_page_id' => $title->getArticleID(),
                'rs_day' => self::getCurrentDay()
            ],
            __METHOD__
        );

        if ($pv == false) $pageViews = 0;
        else $pageViews = (int)$pv;

        if ($pageViews == 0) {
                $dbw->insert(
                    'ratepage_stats',
                    [
                        'rs_page_id' => $title->getArticleID(),
                        'rs_day' => self::getCurrentDay(),
                        'rs_hits' => 1
                    ],
                    __METHOD__
                );
            } else {
                $dbw->update(
                    'ratepage_stats',
                    ['rs_hits' => $pageViews + 1],
                    [
                        'rs_page_id' => $title->getArticleID(),
                        'rs_day' => self::getCurrentDay()
                    ],
                    __METHOD__
                );
            }

        $dbw->endAtomic(__METHOD__);
        return $pageViews + 1;
    }
}
