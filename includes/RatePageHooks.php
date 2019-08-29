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

		if ( !$wgRPAddSidebarSection || !RatePageRating::canPageBeRated( $skin->getTitle() ) )
			return;

		$query = $skin->getRequest()->getQueryValues();
		if ( array_key_exists('action', $query ) && $query['action'] != 'view' )
			return;     //this not a view, probably a history or edit or something

		$pos = $wgRPSidebarPosition;

		$bar = array_slice( $bar, 0, $pos, true ) + array( "ratePage-vote-title" => "" ) + array_slice( $bar, $pos, count( $bar ) - $pos, true );
	}

	/**
	 * @param Parser $parser
	 * @throws MWException
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'ratepage', [ self::class, 'renderTagRatePage' ] );
	}

	/**
	 * Renders the <ratepage> parser tag
	 *
	 * @param $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	public static function renderTagRatePage( $input, array $args, Parser $parser, PPFrame $frame ) {

		if ( !isset( $args['page'] ) ) {
			return self::renderError( wfMessage( 'ratePage-missing-argument-page' )->escaped(), $parser );
		}

		$title = Title::newFromText( $args['page'] );
		if ( $title->getArticleID() < 1 ) {
			return self::renderError( wfMessage( 'ratePage-page-does-not-exist' )->escaped(), $parser );
		}

		$contest = '';
		if ( isset( $args['contest'] ) ) {
			$contest = $args['contest'];
		}

		return '<div class="ratepage-embed" page-id="' . $title->getArticleID() .
			'" contest="' . $contest .
			'"></div>';
	}

	private static function renderError( string $text, Parser &$parser ) {
		$parser->addTrackingCategory( 'ratePage-error-category' );
		return '<strong class="error">' . $text . '</strong>';
	}
}