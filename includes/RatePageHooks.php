<?php
/**
 * RatePage extension hooks
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class RatePageHooks {
	const PROP_NAME = 'page_views';

	/**
	 * Conditionally register the unit testing module for the ext.ratePage module
	 * only if that module is loaded
	 *
	 * @param array $testModules The array of registered test modules
	 * @param ResourceLoader $resourceLoader The reference to the resource loader
	 * @return true
	 */
	public static function onResourceLoaderTestModules( array &$testModules, ResourceLoader &$resourceLoader ) {
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

	public static function onPageViewUpdates( WikiPage $wikipage, User $user ) {
		global $wgRPViewTrackingAllowedNamespaces;

		if (!isnull($wgRPViewTrackingAllowedNamespaces) && 
			!in_array($wikipage->getTitle()->getNamespace(), $wgRPViewTrackingAllowedNamespaces))
			return;
			
		RatePage::updatePageViews($wikipage->getTitle());
	}

	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		global $wgRPRatingAllowedNamespaces, $wgRPViewTrackingAllowedNamespaces;
		$out->addJsConfigVars([
			'wgRPRatingAllowedNamespaces' => $wgRPRatingAllowedNamespaces,
			'wgRPViewTrackingAllowedNamespaces' => $wgRPViewTrackingAllowedNamespaces
		]);
	}

	public static function onInfoAction( IContextSource $context, &$pageInfo ) {
		global $wgRPViewTrackingAllowedNamespaces;

		if (!isnull($wgRPViewTrackingAllowedNamespaces) && 
			!in_array($context->getTitle()->getNamespace(), $wgRPViewTrackingAllowedNamespaces))
			return;

		$pageViews = RatePage::getPageViews($context->getTitle());
		
		$pageInfo['header-basic'][] = [
			$context->msg( 'ratePage-view-count-label' ),
			number_format( $pageViews, 0, '', ' ' )
		];
	}

	/**
	 * Adds the required table storing votes into the database when the
	 * end-user (sysadmin) runs /maintenance/update.php
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$patchPath = __DIR__ . '/../sql/';

		$updater->addExtensionTable(
			'ratepage_vote',
			$patchPath . 'create-table--ratepage-vote.sql'
		);
	}
}
