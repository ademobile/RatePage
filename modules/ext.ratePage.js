/**
 * ratePage stars
 * tested on minerva, timeless, vector and monobook
 **/
mw.RatePage = function () {
	var self = {};

	/**
	 * Rate a page.
	 * @param pageId
	 * @param contest
	 * @param answer
	 * @param callback
	 */
	self.ratePage = function ( pageId, contest, answer, callback ) {
		( new mw.Api() ).post( {
			action: 'ratepage',
			format: 'json',
			pageid: pageId,
			contest: contest,
			answer: answer
		} )
			.done( function ( data ) {
				if ( !data.userVote || data.userVote === -1 ) {
					mw.notify( mw.message( 'ratePage-vote-error' ).text(), {type: 'error'} );
					return;
				}

				var voteCount = null, avg = null;

				if ( data.pageRating ) {
					voteCount = 0;
					for ( var i = 1; i <= 5; i++ ) voteCount += ( data.pageRating[i] );
					avg = 0;
					for ( i = 1; i <= 5; i++ ) avg += ( data.pageRating[i] * i );
					avg = avg / voteCount;
				}

				callback( avg, voteCount, data.userVote, data.canVote, data.canSee );
			} );
	};

	/**
	 * Get ratings for a bunch of pages at once.
	 * @param idToCallbackMap
	 * @param contest
	 */
	self.getRating = function ( idToCallbackMap, contest ) {
		( new mw.Api() ).post( {
			action: 'query',
			prop: 'pagerating',
			format: 'json',
			prcontest: contest,
			pageids: Object.keys( idToCallbackMap )
		} )
			.done( function ( data ) {
				Object.keys( data.query.pages ).forEach( function ( pageid ) {
					var voteCount = null, avg = null;
					var d = data.query.pages[pageid].pagerating;

					if ( d.pageRating ) {
						voteCount = 0;
						for ( var i = 1; i <= 5; i++ ) voteCount += ( d.pageRating[i] );
						avg = 0;
						for ( i = 1; i <= 5; i++ ) avg += ( d.pageRating[i] * i );
						avg = avg / voteCount;
					}

					idToCallbackMap[pageid]( avg, voteCount, d.userVote, d.canVote, d.canSee );
				} );
			} );
	};

	/**
	 * Update the rating widget.
	 * @param average
	 * @param vCount
	 * @param userVote
	 * @param canVote
	 * @param canSee
	 * @param isNew
	 * @param parent
	 */
	self.updateStars = function ( average, vCount, userVote, canVote, canSee, isNew, parent ) {
		function typeForLastStar( f2 ) {
			if ( f2 < 0.05 ) {
				return 'ratingstar-plain';
			} else if ( f2 < 0.4 ) {
				return 'ratingstar-1-4';
			} else if ( f2 < 0.65 ) {
				return 'ratingstar-2-4';
			} else {
				return 'ratingstar-3-4';
			}
		}

		if ( !parent.attr( 'data-page-id' ) ) {
			parent = parent.parent();
		}

		if ( canVote ) {
			parent.find( '.ratingsinfo-yourvote' ).text( mw.message( 'ratePage-prompt' ).text() );
		} else {
			parent.find( '.ratingsinfo-yourvote' ).text( mw.message( 'ratePage-vote-cannot-vote' ).text() );
		}

		if ( ( userVote && userVote !== -1 ) || ( !canVote && canSee ) ) {
			if ( userVote && userVote !== -1 ) {
				parent.find( '.ratingsinfo-yourvote' ).text( mw.message( 'ratePage-vote-info', userVote.toString() ).text() );
			}

			if ( !average ) {
				if ( canSee ) {
					parent.find( '.ratingsinfo-avg' ).text( "" );
				} else {
					parent.find( '.ratingsinfo-avg' ).text( mw.message( 'ratePage-vote-cannot-see' ) );
				}

				for ( var i = 1; i <= 5; i++ ) {
					if ( i <= userVote ) {
						parent.find( '.ratingstar[data-ratingstar-no="' + i.toString() + '"]' )
							.removeClass( "ratingstar-plain ratingstar-1-4 ratingstar-2-4 ratingstar-3-4 ratingstar-full" )
							.addClass( "ratingstar-full" );
					} else {
						parent.find( '.ratingstar[data-ratingstar-no="' + i.toString() + '"]' )
							.removeClass( "ratingstar-1-4 ratingstar-2-4 ratingstar-3-4 ratingstar-full" )
							.addClass( "ratingstar-plain" );
					}
				}
			} else {
				parent.find( '.ratingsinfo-avg' ).text( mw.message( 'ratePage-vote-average-info', average.toFixed( 2 ), vCount.toString() ).text() );

				var f1 = parseInt( average.toFixed( 1 ).slice( 0, -1 ).replace( '.', '' ) );
				for ( i = 1; i <= 5; i++ ) {
					if ( i <= f1 ) {
						parent.find( '.ratingstar[data-ratingstar-no="' + i.toString() + '"]' )
							.removeClass( "ratingstar-plain ratingstar-1-4 ratingstar-2-4 ratingstar-3-4 ratingstar-full" )
							.addClass( "ratingstar-full" );
					} else if ( i === f1 + 1 ) {
						parent.find( '.ratingstar[data-ratingstar-no="' + i.toString() + '"]' )
							.removeClass( "ratingstar-plain ratingstar-1-4 ratingstar-2-4 ratingstar-3-4 ratingstar-full" )
							.addClass( typeForLastStar( average - f1 ) );
					} else {
						parent.find( '.ratingstar[data-ratingstar-no="' + i.toString() + '"]' )
							.removeClass( "ratingstar-1-4 ratingstar-2-4 ratingstar-3-4 ratingstar-full" )
							.addClass( "ratingstar-plain" );
					}
				}
			}
		}

		if ( isNew && canVote ) {
			parent.find( '.ratingstar' ).addClass( 'canvote' );

			/* add behavior to the stars */
			var stars = parent.find( '.ratingstar' );
			stars.click( function () {
				var answer = $( this ).attr( 'data-ratingstar-no' );
				var p = $( this ).parent();
				var pageId = p.attr( 'data-page-id' );

				if ( !pageId ) {
					self.ratePage( mw.config.get( 'wgArticleId' ), '', answer,
						function ( avg, voteCount, userVote, canVote, canSee ) {
							self.updateStars( avg, voteCount, userVote, canVote, canSee, false, p );
						} );
				} else {
					var contest = p.attr( 'data-contest' );
					self.ratePage( pageId, contest, answer,
						function ( avg, voteCount, userVote, canVote, canSee ) {
							self.updateStars( avg, voteCount, userVote, canVote, canSee, false, p );
						} );
				}
			} );

			if ( mw.config.get( 'skin' ) !== "minerva" ) {
				stars.mouseover( function () {
					var no = $( this ).attr( 'data-ratingstar-no' );
					for ( var i = 1; i <= no; i++ ) {
						$( this ).siblings( '.ratingstar[data-ratingstar-no="' + i.toString() + '"]' ).addBack()
							.addClass( 'ratingstar-mousedown' );
					}
				} ).mouseout( function () {
					$( this ).siblings( '.ratingstar' ).addBack().removeClass( 'ratingstar-mousedown' );
				} );
			}
		}
	};

	self.submitStarMap = function ( starMap ) {
		// get data in batches
		Object.keys( starMap ).forEach( function ( contest ) {
			self.getRating( starMap[contest], contest );
		} );
	};

	/**
	 * Initialize an embedded widget.
	 * @param stars
	 * @param starMap
	 */
	self.initializeTag = function ( stars, starMap ) {
		var pageId = stars.attr( 'data-page-id' );
		var contest = stars.attr( 'data-contest' ) || '';
		for ( var i = 1; i <= 5; i++ ) {
			stars.append( '<div class="ratingstar ratingstar-embed ratingstar-plain" title="' +
				mw.message( 'ratePage-caption-' + i.toString() ).text() +
				'" data-ratingstar-no="' + i.toString() +
				'"></div>'
			);
		}
		stars.append( '<div class="ratingsinfo-embed"><div class="ratingsinfo-yourvote"></div><div class="ratingsinfo-avg"></div></div>' );

		if ( !starMap[contest] ) starMap[contest] = {};
		starMap[contest][pageId] = function ( avg, voteCount, userVote, canVote, canSee ) {
			self.updateStars( avg, voteCount, userVote, canVote, canSee, true, stars );
		};
	};

	/**
	 * Initialize the sidebar widget and embedded rating widgets.
	 */
	self.initialize = function () {
		// a map for batch requesting
		var starMap = {};

		/* process all embedded widgets */
		$( 'div.ratepage-embed' ).each( function () {
			self.initializeTag( $( this ), starMap );
		} );

		/* and now the main rating widget in the sidebar or footer */
		if (
			(
				mw.config.get( 'wgRPRatingAllowedNamespaces' ) == null ||
				mw.config.get( 'wgRPRatingAllowedNamespaces' ).indexOf( mw.config.get( 'wgNamespaceNumber' ) ) !== -1
			) &&
			mw.config.get( 'wgRPRatingPageBlacklist' ).indexOf( mw.config.get( 'wgPageName' ) ) === -1 &&
			mw.config.get( 'wgRevisionId' ) !== 0 ) {

			/* add main rating stars (in sidebar or footer) */
			var stars;

			if ( mw.config.get( 'skin' ) === "minerva" ) {
				stars = $( '<div class="post-content footer-element active footer-ratingstars" style="margin-top: 22px"> \
			<h3>' + mw.message( "ratePage-vote-title" ).text() + '</h3> \
			<div class="pageRatingStars"> \
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="1"></div> \
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="2"></div> \
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="3"></div> \
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="4"></div> \
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="5"></div> \
			</div><span class="ratingsinfo-mobile"><span class="ratingsinfo-yourvote"></span> <span class="ratingsinfo-avg"></span></span></div>' );

				$( '.last-modified-bar' ).after( stars );
			} else {
				/* for timeless */
				$( '#p-ratePage-vote-title' ).removeClass( "emptyPortlet" );
				stars = $( '<div id="ratingstars" />' );
				$( '#p-ratePage-vote-title > div' ).append( stars );

				for ( var i = 1; i <= 5; i++ ) {
					stars.append( '<div class="ratingstar ratingstar-desktop ratingstar-plain" title="' +
						mw.message( 'ratePage-caption-' + i.toString() ).text() +
						'" data-ratingstar-no="' + i.toString() +
						'"></div>'
					);
				}
				stars.after( '<div class="ratingsinfo-desktop"><div class="ratingsinfo-yourvote"></div><div class="ratingsinfo-avg"></div></div>' );
			}

			if ( !starMap[''] ) starMap[''] = {};
			starMap[''][mw.config.get( 'wgArticleId' )] = function ( avg, voteCount, userVote, canVote, canSee ) {
				self.updateStars( avg, voteCount, userVote, canVote, canSee, true, stars );
			};
		}

		self.submitStarMap( starMap );
	};

	return self;
}();

$( document ).ready( mw.RatePage.initialize() );