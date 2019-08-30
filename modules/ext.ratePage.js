/**
 * ratePage stars
 * tested on minerva, timeless, vector and monobook
 **/
( function ( $, mw ) {
	/* first, some helper functions */
	function ratePage( articleName, contest, answer, callback ) {
		( new mw.Api() ).post( {
			action: 'pagerating',
			format: 'json',
			pagetitle: articleName,
			contest: contest,
			answer: answer
		} )
			.done( function ( data ) {
				if ( !data.userVote || data.userVote == -1 ) {
					mw.notify( mw.message( 'ratePage-vote-error' ).text(), {type: 'error'} );
					return;
				}
				var voteCount = 0;
				for ( var i = 1; i <= 5; i++ ) voteCount += ( data.pageRating[i] );
				var avg = 0;
				for ( i = 1; i <= 5; i++ ) avg += ( data.pageRating[i] * i );
				avg = avg / voteCount;

				callback( data.userVote, avg, voteCount );
			} );
	}

	function getRating( articleName, contest, callback ) {
		( new mw.Api() ).post( {
			action: 'pagerating',
			format: 'json',
			pagetitle: articleName,
			contest: contest,
		} )
			.done( function ( data ) {
				var voteCount = 0;
				for ( var i = 1; i <= 5; i++ ) voteCount += ( data.pageRating[i] );
				var avg = 0;
				for ( i = 1; i <= 5; i++ ) avg += ( data.pageRating[i] * i );
				avg = avg / voteCount;

				callback( data.userVote, avg, voteCount );
			} );
	}

	function updateStars( average, vCount, articleName, contest ) {
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

		var parent = null;
		if ( !articleName ) {
			if ( mw.config.get( 'skin' ) === "minerva" ) {
				parent = $( '.pageRatingStars' );
			} else {
				parent = $( '#p-ratePage-vote-title' );
			}
		} else {
			parent = $( "div[page='" + articleName + "'][contest='" + contest + "']" );
		}

		if ( isNaN( average ) ) parent.find( '#ratingsinfo-avg' ).text( "" );
		else parent.find( '#ratingsinfo-avg' ).text( mw.message( 'ratePage-vote-average-info', average.toFixed(2), vCount.toString() ).text() );

		var f1 = parseInt( average.toFixed( 1 ).slice( 0, -1 ).replace( '.', '' ) );
		for ( var i = 1; i <= 5; i++ ) {
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

	/* now process all <ratepage> tags */
	$( 'div.ratepage-embed' ).each( function () {
		var stars = $( this );
		for ( var i = 1; i <= 5; i++ ) {
			stars.append( '<div class="ratingstar ratingstar-desktop ratingstar-plain" title="' +
				mw.message( 'ratePage-caption-' + i.toString() ).text() +
				'" data-ratingstar-no="' + i.toString() + '"></div>'
			);
		}
		stars.append( '<div class="ratingsinfo-desktop"><div id="ratingsinfo-yourvote"></div><div id="ratingsinfo-avg"></div></div>' );
	} );

	/* and now the main rating widget in the sidebar or footer */
	if (
		(
			mw.config.get('wgRPRatingAllowedNamespaces') == null ||
			mw.config.get( 'wgRPRatingAllowedNamespaces' ).indexOf( mw.config.get( 'wgNamespaceNumber' ) ) !== -1
		) &&
		mw.config.get( 'wgRPRatingPageBlacklist' ).indexOf( mw.config.get( 'wgPageName' ) ) === -1 &&
		mw.config.get( 'wgRevisionId' ) !== 0 ) {

		/* add main rating stars (in sidebar or footer) */
		if ( mw.config.get( 'skin' ) === "minerva" ) {
			var htmlCode = '<div class="post-content footer-element active footer-ratingstars"> \
			<h3>' + mw.message( "ratePage-vote-title" ).text() + '</h3> \
			<div class="pageRatingStars"> \
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="1"></div> \
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="2"></div> \
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="3"></div> \
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="4"></div> \
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="5"></div> \
			</div><span class="ratingsinfo-mobile"><span id="ratingsinfo-yourvote"></span> <span id="ratingsinfo-avg"></span></span></div>';

			$( '.last-modified-bar' ).after( htmlCode );
		} else {
			/* for timeless */
			$( '#p-ratePage-vote-title' ).removeClass( "emptyPortlet" );
			$( '#p-ratePage-vote-title > div' ).append( '<div id="ratingstars" />' );

			var stars = $( "#ratingstars" );
			for ( var i = 1; i <= 5; i++ ) {
				stars.append( '<div class="ratingstar ratingstar-desktop ratingstar-plain" title="' +
					mw.message( 'ratePage-caption-' + i.toString() ).text() +
					'" data-ratingstar-no="' + i.toString() + '"></div>'
				);
			}
			stars.after( '<div class="ratingsinfo-desktop"><div id="ratingsinfo-yourvote"></div><div id="ratingsinfo-avg"></div></div>' );
		}

		/* initialize the stars */
		getRating( mw.config.get( 'wgPageName' ), '',
			function ( userVote, avg, voteCount ) {
				if ( userVote !== -1 ) {
					$( '#ratingsinfo-yourvote' ).text( mw.message( 'ratePage-vote-info', userVote.toString() ).text() );
					updateStars( avg, voteCount );
				} else
					$( '#ratingsinfo-yourvote' ).html( mw.message( 'ratePage-prompt' ).text() );
			} );
	}

	/* add behavior to the stars */
	$( '.ratingstar' ).click( function () {
		var answer = $( this ).attr( 'data-ratingstar-no' );
		ratePage( mw.config.get( 'wgPageName' ), '', answer,
			function ( userVote, avg, voteCount ) {
				$( '#ratingsinfo-yourvote' ).text( mw.message( 'ratePage-vote-info', userVote.toString() ).text() );
				updateStars( avg, voteCount );
			} );
	} );

	if ( mw.config.get( 'skin' ) !== "minerva" ) {
		$( '.ratingstar-desktop' ).mouseover( function () {
			var no = $( this ).attr( 'data-ratingstar-no' );
			for ( var i = 1; i <= no; i++ ) {
				$( '.ratingstar[data-ratingstar-no="' + i.toString() + '"]' ).addClass( 'ratingstar-mousedown' );
			}
		} ).mouseout( function () {
			$( '.ratingstar' ).removeClass( 'ratingstar-mousedown' );
		} );
	}
}( jQuery, mw ) );
