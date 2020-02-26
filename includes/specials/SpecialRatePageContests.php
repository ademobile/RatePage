<?php

use MediaWiki\MediaWikiServices;

class SpecialRatePageContests extends SpecialPage {
	public $mContest;
	private $mPermManager;

	public function __construct() {
		parent::__construct( 'RatePageContests', 'ratepage-contests-view-list' );

		$this->mPermManager = MediaWikiServices::getInstance()->getPermissionManager();
	}

	/**
	 * @return bool
	 */
	public function doesWrites() {
		return true;
	}

	function execute( $subpage ) {
		$out = $this->getOutput();
		$request = $this->getRequest();

		$this->setHeaders();
		$this->addHelpLink( 'Extension:RatePage' );

		$this->checkPermissions();

		if ( $request->getVal( 'result' ) == 'success' ) {
			$changedFilter = intval( $request->getVal( 'changedcontest' ) );
			$out->wrapWikiMsg( '<p class="success">$1</p>',
				[
					'ratePage-edit-done',
					$changedFilter,
					$this->getLanguage()->formatNum( $changedFilter )
				]
			);
		}

		if ( strlen( $subpage ) ) {
			$this->mContest = $subpage;
			$this->showEditView();
		} else {
			$this->showListView();
		}

		// Links at the top
		$this->addSubtitle();
	}

	protected function showListView() {
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'ratePage-contest-list-title' ) );
		$out->enableOOUI();

		// New contest button
		if ( $this->userCanEdit() ) {
			$link = new OOUI\ButtonWidget( [
				'label' => $this->msg( 'ratePage-contests-new' )->text(),
				'href' => $this->getPageTitle( 'new' )->getFullURL(),
			] );
			$out->addHTML( $link );
		}

		$pager = new ContestsPager( $this, $this->getLinkRenderer() );

		//TODO: add some filtering crap

		$this->getOutput()->addHTML(
			'<br><br>' .
			$pager->getFullOutput()->getText()
		);
	}

	protected function showEditView() {
		if ( !$this->userCanViewDetails() ) {
			throw new PermissionsError( 'ratepage-contests-view-details' );
		}

		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'ratePage-contest-edit-title' ) );



	}

	protected function addSubtitle() {
		$elems = [];
		$lr = $this->getLinkRenderer();
		$out = $this->getOutput();

		if ( isset( $this->mContest ) ) {
			if ( $this->mContest == "new" ) {
				$elems[] = $this->msg( 'ratePage-new-contest-sub' )->parse();
			} else {
				$elems[] = $this->msg( 'ratePage-edit-contest-sub', $this->mContest )->parse();
			}
		}

		$homePage = Title::newFromText( 'Special:RatePageContests' );
		$elems[] =
			$lr->makeLink(
				$homePage,
				new HtmlArmor( $this->msg('ratePage-contest-home')->parse() )
			);

		$linkStr = $this->getLanguage()->pipeList( $elems );
		$out->getOutput()->setSubtitle( $linkStr );
	}

	public function userCanViewDetails() {
		return $this->mPermManager->userHasRight( $this->getUser(), 'ratepage-contests-view-details' );
	}

	public function userCanEdit() {
		return $this->mPermManager->userHasRight( $this->getUser(), 'ratepage-contests-edit' );
	}

	public function userCanClearResults() {
		return $this->mPermManager->userHasRight( $this->getUser(), 'ratepage-contests-clear' );
	}
}