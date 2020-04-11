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

	/**
	 * Load an additional class if MMV is present.
	 */
	public static function onRegistration() : void {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'MultimediaViewer' ) ) {
			global $wgAutoloadClasses;
			$wgAutoloadClasses['RatePageMmvHooks'] = __DIR__ . '/MultimediaViewer/RatePageMmvHooks.php';
		}
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		global $wgRPRatingAllowedNamespaces;
		global $wgRPRatingPageBlacklist;
		global $wgRPFrontendEnabled;

		$out->addJsConfigVars( [ 'wgRPRatingAllowedNamespaces' => $wgRPRatingAllowedNamespaces, 'wgRPRatingPageBlacklist' => $wgRPRatingPageBlacklist ] );

		if ( $wgRPFrontendEnabled ) {
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

		$db = $updater->getDB();

		if ( $db->tableExists( 'ratepage_vote' ) ) {
			$updater->addExtensionField(
				'ratepage_vote',
				'rv_contest',
				$patchPath . 'upgrade-from-0.2-to-0.3.sql'
			);

			$updater->modifyTable(
				'ratepage_vote',
				$patchPath . 'upgrade-from-0.3-to-1.0.sql',
				true
			);
		} else {
			$updater->addExtensionTable(
				'ratepage_vote',
				$patchPath . 'create-table--ratepage-vote.sql'
			);
		}

		$updater->addExtensionTable(
			'ratepage_contest',
			$patchPath . 'create-table--ratepage-contest.sql'
		);

		$updater->addPostDatabaseUpdateMaintenance(
			AddMissingContests::class
		);
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
		$parser->setFunctionHook( 'ratepage', [ self::class, 'renderTagRatePage' ] );
	}

	/**
	 * Renders the ratepage parser function
	 *
	 * @param Parser $parser
	 * @param mixed $page
	 * @param mixed $contest
	 * @param string $width
	 * @return string
	 */
	public static function renderTagRatePage( Parser $parser, $page = false, $contest = '', $width = '300px' ) {

		if ( !$page ) {
			return self::renderError( wfMessage( 'ratePage-missing-argument-page' )->escaped(), $parser );
		}

		$title = Title::newFromText( $page );
		if ( !$title || $title->getArticleID() < 1 ) {
			return self::renderError( wfMessage( 'ratePage-page-does-not-exist' )->escaped(), $parser );
		}

		if ( $contest  && !RatePageContestDB::checkContestExists( $contest ) ) {
			return self::renderError( wfMessage( 'ratePage-no-such-contest', $contest )->escaped(), $parser );
		}

		return '<div class="ratepage-embed" id="' . $title->getArticleID() . 'c' . $contest .
			'" style="width: ' . $width .
			';"></div>';
	}

	private static function renderError( string $text, Parser &$parser ) {
		$parser->addTrackingCategory( 'ratePage-error-category' );
		return '<strong class="error">' . $text . '</strong>';
	}
}