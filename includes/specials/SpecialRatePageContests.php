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

		if ( is_numeric( $subpage ) || $subpage == 'new' ) {
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


	}

	protected function showEditView() {
		$this->checkUserCanViewDetails();

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

	private function checkUserCanViewDetails() {
		if ( !$this->mPermManager->userHasRight( $this->getUser(), 'ratepage-contests-view-details' ) ) {
			throw new PermissionsError( 'ratepage-contests-view-details' );
		}
	}

	private function userCanEdit() {
		return $this->mPermManager->userHasRight( $this->getUser(), 'ratepage-contests-edit' );
	}

	private function userCanClearResults() {
		return $this->mPermManager->userHasRight( $this->getUser(), 'ratepage-contests-clear' );
	}
}