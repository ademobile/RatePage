<?php

namespace RatePage;

use AddMissingContests;
use DatabaseUpdater;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use MWException;
use OutputPage;
use Parser;
use RatePage\MultimediaViewer\MmvHooks;
use Skin;
use Title;

/**
 * RatePage extension hooks
 *
 * @file
 * @ingroup Extensions
 */
class Hooks {
	// TODO: get rid of all the globals, ugh
	const PROP_NAME = 'page_views';

	/**
	 * Load an additional class if MMV is present.
	 */
	public static function onRegistration() : void {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'MultimediaViewer' ) ) {
			global $wgAutoloadClasses;
			$wgAutoloadClasses['RatePage\MultimediaViewer\MmvHooks'] = __DIR__ . '/MultimediaViewer/MmvHooks.php';
		}
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$jsVars = [
			'RPRatingAllowedNamespaces',
			'RPRatingPageBlacklist',
		];
		foreach ( $jsVars as $var ) {
			$out->addJsConfigVars( "wg$var", $config->get( $var ) );
		}

		// why the hell is this not passed on to frontend by MF?!
		$out->addJsConfigVars( 'wgRPTarget', $out->getTarget() );

		if ( !$config->get( 'RPFrontendEnabled' ) ) {
			return;
		}

		$out->addModules( 'ext.ratePage' );

		if ( $config->get( 'RPUseMMVModule' ) && class_exists( 'RatePage\MultimediaViewer\MmvHooks' ) ) {
			if ( MmvHooks::isMmvEnabled( $out->getUser() ) ) {
				$out->addModules( 'ext.ratePage.mmv' );
			}
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

		$updater->addExtensionTable(
			'ratepage_vote',
			$patchPath . 'create-table--ratepage-vote.sql'
		);
		$updater->addExtensionField(
			'ratepage_vote',
			'rv_contest',
			$patchPath . 'update/upgrade-from-0.2-to-0.3.sql'
		);
		$updater->modifyExtensionField(
			'ratepage_vote',
			'rv_contest',
			$patchPath . 'update/upgrade-from-0.3-to-1.0.sql'
		);

		$updater->addExtensionTable(
			'ratepage_contest',
			$patchPath . 'create-table--ratepage-contest.sql'
		);
		$updater->addExtensionField(
			'ratepage_contest',
			'rpc_see_before_vote',
			$patchPath . 'update/add-field--rpc-see-before-vote.sql'
		);

		$updater->addPostDatabaseUpdateMaintenance( AddMissingContests::class );
	}

	public static function onSkinBuildSidebar( Skin $skin, &$bar ) {
		global $wgRPAddSidebarSection, $wgRPSidebarPosition;

		if ( !$wgRPAddSidebarSection || !Rating::canPageBeRated( $skin->getTitle() ) || $skin->getOutput()
				->getTarget() === 'mobile' ) {
			return;
		}

		$query = $skin->getRequest()->getQueryValues();
		if ( array_key_exists( 'action', $query ) && $query['action'] != 'view' ) {
			return;
		}     //this not a view, probably a history or edit or something

		$pos = $wgRPSidebarPosition;

		$bar = array_slice( $bar, 0, $pos, true ) +
			[ "ratePage-vote-title" => "" ] +
			array_slice( $bar, $pos, count( $bar ) - $pos, true );
	}

	/**
	 * @param Parser $parser
	 *
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
	 *
	 * @return string
	 */
	public static function renderTagRatePage( Parser $parser, $page = false, $contest = '', $width = '300px' ) {

		if ( !$page ) {
			return self::renderError(
				wfMessage( 'ratePage-missing-argument-page' )->escaped(),
				$parser
			);
		}

		$title = Title::newFromText( $page );
		if ( !$title || $title->getArticleID() < 1 ) {
			return self::renderError(
				wfMessage( 'ratePage-page-does-not-exist' )->escaped(),
				$parser
			);
		}

		if ( $contest && !ContestDB::checkContestExists( $contest ) ) {
			return self::renderError(
				wfMessage( 'ratePage-no-such-contest', $contest )->escaped(),
				$parser
			);
		}

		return '<div class="ratepage-embed" data-page-id="' . $title->getArticleID() . '" data-contest="' .
			$contest . '" style="width: ' . $width . ';"></div>';
	}

	private static function renderError( string $text, Parser &$parser ) {
		$parser->addTrackingCategory( 'ratePage-error-category' );

		return '<strong class="error">' . $text . '</strong>';
	}
}