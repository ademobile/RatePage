<?php
/**
 * RatePage extension hooks
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class RatePageHooks
{
	const PROP_NAME = 'page_views';

	/**
	 * Conditionally register the unit testing module for the ext.ratePage module
	 * only if that module is loaded
	 *
	 * @param array $testModules The array of registered test modules
	 * @param ResourceLoader $resourceLoader The reference to the resource loader
	 * @return true
	 */
	public static function onResourceLoaderTestModules(array &$testModules, ResourceLoader &$resourceLoader)
	{
		$testModules['qunit']['ext.ratePage.tests'] = [
			'scripts' => [
				'tests/RatePage.test.js'
			],
			'dependencies' => [
				'ext.ratePage'
			],
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'RatePage',
		];
		return true;
	}

	public static function onPageViewUpdates(WikiPage $wikipage, User $user)
	{
		if (!RatePageViews::canPageBeTracked($wikipage->getTitle()))
			return;

		RatePageViews::updatePageViews($wikipage->getTitle());
	}

	public static function onBeforePageDisplay(OutputPage $out, Skin $skin)
	{
		global $wgRPRatingAllowedNamespaces, $wgRPViewTrackingAllowedNamespaces;
		global $wgRPRatingPageBlacklist;

		$out->addJsConfigVars([
			'wgRPRatingAllowedNamespaces' => $wgRPRatingAllowedNamespaces,
			'wgRPViewTrackingAllowedNamespaces' => $wgRPViewTrackingAllowedNamespaces,
			'wgRPRatingPageBlacklist' => $wgRPRatingPageBlacklist
		]);
	}

	public static function onInfoAction(IContextSource $context, &$pageInfo)
	{
		if (!RatePageViews::canPageBeTracked($context->getTitle()))
			return;

		$pageViews = RatePageViews::getPageViews($context->getTitle());

		$pageInfo['header-basic'][] = [
			$context->msg('ratePage-view-count-label'),
			number_format($pageViews, 0, '', ' ')
		];
	}

	/**
	 * Adds the required table storing votes into the database when the
	 * end-user (sysadmin) runs /maintenance/update.php
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates($updater)
	{
		$patchPath = __DIR__ . '/../sql/';

		$updater->addExtensionTable(
			'ratepage_vote',
			$patchPath . 'create-table--ratepage-vote.sql'
		);

		$updater->addExtensionTable(
			'ratepage_stats',
			$patchPath . 'create-table--ratepage-stats.sql'
		);
	}

    public static function onSkinBuildSidebar( Skin $skin, &$bar )
    {
        if (!RatePageRating::canPageBeRated($skin->getTitle()))
            return;

        global $wgRPSidebarPosition;
        $pos = $wgRPSidebarPosition;

        $bar =  array_slice($bar, 0, $pos, true) +
            array("ratePage-vote-title"  => "") +
            array_slice($bar, $pos, count($bar)-$pos, true);
    }
}