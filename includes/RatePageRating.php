<?php
/**
 * RatePage page rating code
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class RatePageRating
{
    const MIN_RATING = 1;
    const MAX_RATING = 5;

    public static function canPageBeRated(Title $title)
    {
        global $wgRPRatingAllowedNamespaces, $wgRPRatingPageBlacklist;

        if ($title->getArticleID() < 0)
            return false;   //no such page

        if ($title->isRedirect())
            return false;

        if (
            !is_null($wgRPRatingAllowedNamespaces) &&
            !in_array($title->getNamespace(), $wgRPRatingAllowedNamespaces)
        )
            return false;

        if (
            !is_null($wgRPRatingPageBlacklist) &&
            in_array($title->getFullText(), $wgRPRatingPageBlacklist)
        )
            return false;

        return true;
    }

    public static function getPageRating(Title $title)
    {
        if ($title->getArticleID() < 0)
            return [];   //no such page

        $dbr = wfGetDB(DB_REPLICA);
        $res = $dbr->select(
            'ratepage_vote',
            ['rv_answer as answer', "count(rv_page_id) as 'count'"],
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
        for ($i = self::MIN_RATING; $i <= self::MAX_RATING; $i++)
            $pageRating[$i] = 0;

        foreach ($res as $row)
            $pageRating[$row->answer] = (int)$row->count;

        return $pageRating;
    }

    public static function getUserVote(Title $title, string $user, string $ip)
    {
        if ($title->getArticleID() < 0)
            return false;   //no such page

        $dbr = wfGetDB(DB_REPLICA);
        $res = $dbr->selectField(
            'ratepage_vote',
            'rv_answer',
            [
                'rv_page_id' => $title->getArticleID(),
                'rv_user' => $user
            ],
            __METHOD__
        );
        if ($res != false && !is_null($res)) return (int)$res;

        return -1;
    }

    /**
     * Vote on a page. Returns whether the vote was successful.
     */
    public static function voteOnPage(Title $title, string $user, string $ip, int $answer)
    {
        if ($title->getArticleID() < 0)
            return false;   //no such page

        //check whether the user has voted during a transaction to avoid a duplicate vote
        $dbw = wfGetDB(DB_MASTER);
        $dbw->startAtomic(__METHOD__);
        $res = $dbw->selectField(
            'ratepage_vote',
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
                    'rv_answer' => $answer,
                    'rv_date' => date('Y-m-d H:i:s')
                ],
                [
                    'rv_page_id' => $title->getArticleID(),
                    'rv_user' => $user
                ],
                __METHOD__
            );

            $dbw->endAtomic(__METHOD__);
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
                'rv_date' => date('Y-m-d H:i:s')
            ],
            __METHOD__
        );

        $dbw->endAtomic(__METHOD__);
        return true;
    }
}
