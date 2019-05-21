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
		$testModules['qunit']['ext.ratePage.tests'] = [ 'scripts' => [ 'tests/RatePage.test.js' ], 'dependencies' => [ 'ext.ratePage' ], 'localBasePath' => __DIR__, 'remoteExtPath' => 'RatePage', ];
		return true;
	}

	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		global $wgRPRatingAllowedNamespaces;
		global $wgRPRatingPageBlacklist;
		global $wgRPFrontendEnabled;

		$out->addJsConfigVars( [ 'wgRPRatingAllowedNamespaces' => $wgRPRatingAllowedNamespaces, 'wgRPRatingPageBlacklist' => $wgRPRatingPageBlacklist ] );

		if ( $wgRPFrontendEnabled && RatePageRating::canPageBeRated( $out->getTitle() ) ) {
			$out->addModules( 'ext.ratePage' );
		}
	}

	/**
	 * Adds the required table storing votes into the database when the
	 * end-user (sysadmin) runs /maintenance/update.php
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$patchPath = __DIR__ . '/../sql/';

		$updater->addExtensionTable( 'ratepage_vote', $patchPath . 'create-table--ratepage-vote.sql' );
	}

	public static function onSkinBuildSidebar( Skin $skin, &$bar ) {
		global $wgRPAddSidebarSection, $wgRPSidebarPosition;

		if ( !$wgRPAddSidebarSection || !RatePageRating::canPageBeRated( $skin->getTitle() ) ) {
			return;
		}

		$pos = $wgRPSidebarPosition;

		$bar = array_slice( $bar, 0, $pos, true ) + array( "ratePage-vote-title" => "" ) + array_slice( $bar, $pos, count( $bar ) - $pos, true );
	}
}