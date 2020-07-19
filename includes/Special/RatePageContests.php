<?php

/**
 * Some of this code is based on the AbuseFilter extension.
 * You can find the extension's code and list of authors here:
 * https://github.com/wikimedia/mediawiki-extensions-AbuseFilter
 *
 * AbuseFilter's code is licensed under GPLv2
 */

namespace RatePage\Special;

use Html;
use HtmlArmor;
use MediaWiki\MediaWikiServices;
use MediaWiki\Widget\CheckMatrixWidget;
use OOUI;
use OOUI\FieldLayout;
use PermissionsError;
use RatePage\ContestDB;
use RatePage\Pager\ContestResultsPager;
use RatePage\Pager\ContestsPager;
use RatePage\Rights;
use SpecialPage;
use Status;
use Title;
use WebRequest;
use Xml;

class RatePageContests extends SpecialPage {
	public static $mLoadedRow = null;

	public $mContest;
	private $mPermManager;

	public function __construct() {
		parent::__construct( 'RatePageContests',
			'ratepage-contests-view-list' );

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
		$out->enableOOUI();
		$out->addModules( 'ext.ratePage.contests' );

		$this->checkPermissions();

		if ( $request->getVal( 'result' ) == 'success' ) {
			$changedFilter = $request->getVal( 'changedcontest' );
			$out->wrapWikiMsg( '<p class="success">$1</p>',
				[ 'ratePage-edit-done',
					$changedFilter ] );
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

		// New contest button
		if ( $this->userCanEdit() ) {
			$link = new OOUI\ButtonWidget( [ 'label' => $this->msg( 'ratePage-contests-new' )->text(),
				'href' => $this->getPageTitle( '!new' )->getFullURL(), ] );
			$out->addHTML( $link );
		}

		$pager = new ContestsPager( $this,
			$this->getLinkRenderer() );

		//TODO: add some filtering crap

		$this->getOutput()->addHTML( '<br><br>' . $pager->getFullOutput()->getText() );
		$this->getOutput()->addModules( $pager->getModuleStyles() );
	}

	protected function showEditView() {
		// check permissions
		if ( !$this->userCanViewDetails() ) {
			throw new PermissionsError( 'ratepage-contests-view-details' );
		}

		$new = $this->mContest == "!new";

		if ( $new && !$this->userCanEdit() ) {
			throw new PermissionsError( 'ratepage-contests-edit' );
		}

		$out = $this->getOutput();
		$request = $this->getRequest();
		$out->setPageTitle( $this->msg( 'ratePage-contest-edit-title' ) );

		if ( !$new && !ContestDB::checkContestExists( $this->mContest ) ) {
			$out->addHTML( Xml::tags( 'p',
				null,
				Html::errorBox( $this->msg( 'ratePage-no-such-contest',
					$this->mContest )->parse() ) ) );

			return;
		}

		// show details
		$contest = $this->mContest;
		$newRow = $this->loadRequest( $contest );

		$editToken = $this->getRequest()->getVal( 'wpEditToken' );
		$tokenMatches = $this->getUser()->matchEditToken( $editToken,
			[ 'ratepagecontest',
				$this->mContest ],
			$this->getRequest() );

		if ( $tokenMatches && $this->userCanEdit() ) {
			$status = $this->saveContest( $newRow,
				$request );

			if ( !$status->isGood() ) {
				$err = $status->getErrors();
				$msg = $err[0]['message'];
				if ( $status->isOK() ) {
					$out->addHTML( $this->buildEditor( $newRow ) );
				} else {
					$out->addWikiMsg( $msg );
				}
			} else {
				if ( $status->getValue() === false ) {
					// No change
					$out->redirect( $this->getPageTitle()->getLocalURL() );
				} else {
					$new_id = $status->getValue();
					$out->redirect( $this->getPageTitle( $new_id )->getLocalURL( [ 'result' => 'success',
						'changedcontest' => $new_id, ] ) );
				}
			}
		} else {
			if ( $tokenMatches ) {
				// Lost rights meanwhile
				$out->addHTML( Xml::tags( 'p',
					null,
					Html::errorBox( $this->msg( 'ratePage-edit-notallowed' )->parse() ) ) );
			} elseif ( $request->wasPosted() ) {
				// Warn the user to re-attempt save
				$out->addHTML( Html::warningBox( $this->msg( 'ratePage-edit-token-not-match' )->escaped() ) );
			}

			$out->addHTML( $this->buildEditor( $newRow ) );
		}
	}

	protected function buildEditor( $row ) {
		$new = $this->mContest == "!new";

		// Figure out which permissions were selected
		$selectedPermissions = [];
		if ( !$new ) {
			$selectedPermissions = array_merge( $selectedPermissions,
				array_map( function ( $a ) {
					return "vote-$a";
				},
					explode( ',',
						$row->rpc_allowed_to_vote ) ) );

			$selectedPermissions = array_merge( $selectedPermissions,
				array_map( function ( $a ) {
					return "see-$a";
				},
					explode( ',',
						$row->rpc_allowed_to_see ) ) );
		}

		// Read-only attribute
		$readOnlyAttrib = [];

		if ( !$this->userCanEdit() ) {
			$readOnlyAttrib['disabled'] = 'disabled';
		}

		$form = '';

		$fieldset = new OOUI\FieldsetLayout( [ 'label' => $this->msg( 'ratePage-contest-edit-main' )->escaped() ] );

		$fieldset->addItems( [ new FieldLayout( //TODO: add some info for end user on allowed characters
		//TODO: add edit filter
			new OOUI\TextInputWidget( [ 'value' => $new ? '' : $row->rpc_id,
					'disabled' => !$new ] + ( $new ? [ 'name' => 'wpContestId' ] : [] ) ),
			[ 'label' => $this->msg( 'ratePage-edit-id' )->escaped(),
				'align' => 'top' ] ),
			new FieldLayout( new OOUI\TextInputWidget( [ 'name' => 'wpContestDescription',
					'value' => isset( $row->rpc_description ) ? $row->rpc_description : '' ] + $readOnlyAttrib ),
				[ 'label' => $this->msg( 'ratePage-edit-description' )->escaped(),
					'align' => 'top' ] ),
			new FieldLayout( new OOUI\CheckboxInputWidget( [ 'name' => 'wpContestEnabled',
					'id' => 'wpContestEnabled',
					'selected' => isset( $row->rpc_enabled ) ? $row->rpc_enabled : 1 ] + $readOnlyAttrib ),
				[ 'label' => $this->msg( 'ratePage-edit-enabled' )->escaped(),
					'align' => 'inline' ] ),
			new FieldLayout( new CheckMatrixWidget( [ 'name' => 'wpContestPermissions',
					'columns' => [ $this->msg( 'ratePage-edit-allowed-to-vote' )->escaped() => 'vote',
						$this->msg( 'ratePage-edit-allowed-to-see' )->escaped() => 'see' ],
					'rows' => Rights::getGroupsAsColumns( $this->getContext() ),
					'values' => $selectedPermissions ] + $readOnlyAttrib ) ), ] );

		$form .= $fieldset;

		if ( !$new ) {
			$form .= Html::hidden( 'wpContestId',
				$this->mContest );
		}

		//TODO: add a button for clearing results
		if ( $this->userCanEdit() ) {
			$form .= new OOUI\FieldLayout( new OOUI\ButtonInputWidget( [ 'type' => 'submit',
				'label' => $this->msg( 'ratePage-edit-save' )->text(),
				'useInputTag' => true,
				'accesskey' => 's',
				'flags' => [ 'progressive',
					'primary' ] ] ) );
			$form .= Html::hidden( 'wpEditToken',
				$this->getUser()->getEditToken( [ 'ratepagecontest',
					$this->mContest ] ) );
		}

		$form = Xml::tags( 'form',
			[ 'action' => $this->getPageTitle( $this->mContest )->getFullURL(),
				'method' => 'post' ],
			$form );

		if ( !$new ) {
			$pager = new ContestResultsPager( $row->rpc_id,
				$this->getContext(),
				$this->getLinkRenderer() );
			$form .= '<br><br>' . $pager->getFullOutput()->getText();
			$this->getOutput()->addModules( $pager->getModuleStyles() );
		}

		return $form;
	}

	/**
	 * @param $row
	 * @param WebRequest $request
	 *
	 * @return Status
	 */
	protected function saveContest( $row, WebRequest $request ) {
		$validationStatus = Status::newGood();

		$id = $request->getVal( 'wpContestId' );
		if ( !$id ) {
			$validationStatus->error( 'ratePage-contest-missing-id' );

			return $validationStatus;
		}

		$errorKey = ContestDB::validateId( $id );
		if ( $errorKey ) {
			$validationStatus->error( $errorKey );

			return $validationStatus;
		}

		if ( !$this->userCanEdit() ) {
			$validationStatus->error( 'ratePage-edit-notallowed' );

			return $validationStatus;
		}

		try {
			ContestDB::saveContest( $row,
				$this->getContext() );
		} catch ( \Wikimedia\Rdbms\DBError $dbe ) {
			$validationStatus->error( 'ratePage-duplicate-id' );

			return $validationStatus;
		}

		$validationStatus->value = $id;

		return $validationStatus;
	}

	protected function loadRequest( $contest ) {
		$row = self::$mLoadedRow;
		$request = $this->getRequest();

		if ( !is_null( $row ) ) {
			return $row;
		} elseif ( $request->wasPosted() ) {
			// Nothing, we do it all later
		} else {
			return ContestDB::loadContest( $contest );
		}

		$textLoads = [ 'rpc_id' => 'wpContestId',
			'rpc_description' => 'wpContestDescription' ];

		foreach ( $textLoads as $col => $field ) {
			if ( $col == 'rpc_id' && isset( $row->rpc_id ) ) {
				// Disallow overwriting contest ID
				continue;
			}

			$row->$col = trim( $request->getVal( $field ) ?? '' );
		}

		$row->rpc_enabled = $request->getCheck( 'wpContestEnabled' );

		$perm = $request->getArray( 'wpContestPermissions' ) ?? [];
		$pVote = [];
		$pSee = [];

		foreach ( $perm as $p ) {
			if ( strpos( $p,
					'vote-' ) === 0 ) {
				$pVote[] = substr( $p,
					5 );
			} elseif ( strpos( $p,
					'see-' ) === 0 ) {
				$pSee[] = substr( $p,
					4 );
			}
		}

		$row->rpc_allowed_to_vote = implode( ',',
			$pVote );
		$row->rpc_allowed_to_see = implode( ',',
			$pSee );

		self::$mLoadedRow = $row;

		return $row;
	}

	protected function addSubtitle() {
		$elems = [];
		$lr = $this->getLinkRenderer();
		$out = $this->getOutput();

		if ( isset( $this->mContest ) ) {
			if ( $this->mContest == "!new" ) {
				$elems[] = $this->msg( 'ratePage-new-contest-sub' )->parse();
			} else {
				$elems[] = $this->msg( 'ratePage-edit-contest-sub',
					$this->mContest )->parse();
			}
		}

		$homePage = Title::newFromText( 'Special:RatePageContests' );
		$elems[] = $lr->makeLink( $homePage,
			new HtmlArmor( $this->msg( 'ratePage-contest-home' )->parse() ) );

		$linkStr = $this->getLanguage()->pipeList( $elems );
		$out->getOutput()->setSubtitle( $linkStr );
	}

	public function userCanViewDetails() {
		return $this->mPermManager->userHasRight( $this->getUser(),
			'ratepage-contests-view-details' );
	}

	public function userCanEdit() {
		return $this->mPermManager->userHasRight( $this->getUser(),
			'ratepage-contests-edit' );
	}

	public function userCanClearResults() {
		return $this->mPermManager->userHasRight( $this->getUser(),
			'ratepage-contests-clear' );
	}
}
