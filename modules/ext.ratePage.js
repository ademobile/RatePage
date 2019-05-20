/**
 * ratePage stars
 * tested on minerva, timeless, vector and monobook
 **/
( function ($, mw) {
	if (mw.config.get('wgRPRatingAllowedNamespaces').indexOf(mw.config.get('wgNamespaceNumber')) === -1 || mw.config.get('wgRPRatingPageBlacklist').indexOf(mw.config.get('wgPageName')) !== -1 || mw.config.get('wgRevisionId') === 0) return;
	function updateStars(average, vCount) {
		function typeForLastStar(f2) {
			if (f2 < 0.05) {
				return 'ratingstar-plain';
			} else if (f2 < 0.4) {
				return 'ratingstar-1-4';
			} else if (f2 < 0.65) {
				return 'ratingstar-2-4';
			} else {
				return 'ratingstar-3-4';
			}
		}
		if (isNaN(average)) $('#ratingsinfo-avg').text("");
 			else $('#ratingsinfo-avg').text(mw.message('ratePage-vote-average-info', average.toString(), vCount.toString()).text());


		var f1 = parseInt(average.toFixed(1).slice(0,-1).replace('.', ''));
		for (var i = 1; i <= 5; i++) {
			if (i <= f1) {
				$('.ratingstar[data-ratingstar-no="'+i.toString()+'"]').removeClass( "ratingstar-plain ratingstar-1-4 ratingstar-2-4 ratingstar-3-4 ratingstar-full" ).addClass( "ratingstar-full" );
			} else if (i == f1+1) {
				$('.ratingstar[data-ratingstar-no="'+i.toString()+'"]').removeClass( "ratingstar-plain ratingstar-1-4 ratingstar-2-4 ratingstar-3-4 ratingstar-full" ).addClass( typeForLastStar(average-f1) );
			} else {
				$('.ratingstar[data-ratingstar-no="'+i.toString()+'"]').removeClass( "ratingstar-1-4 ratingstar-2-4 ratingstar-3-4 ratingstar-full" ).addClass( "ratingstar-plain" );
			}
       	}
	}
	function ratePage(articleName, answer) {
		(new mw.Api()).post({
	    	action: 'pagerating',
	    	format: 'json',
	    	pagetitle: articleName,
	    	answer: answer
		})
		.done(function (data) {
			if ( !data.userVote || data.userVote == -1 ) {
				mw.notify(mw.message('ratePage-vote-error').text(), {type: 'error'});
				return;
        	}
	     	var voteCount = 0;
	       	for (var i = 1; i <= 5; i++) voteCount += (data.pageRating[i]);
	       	var avg = 0;
	       	for (i = 1; i <= 5; i++) avg += (data.pageRating[i]*i);
	       	avg = avg/voteCount;
	       	$('#ratingsinfo-yourvote').text(mw.message('ratePage-vote-info', data.userVote.toString()).text()); 
	       	updateStars(avg, voteCount);
		});
	}
	/* add stars */
	if (mw.config.get('skin') == "minerva") {
		var htmlCode = `<div class="post-content footer-element active footer-ratingstars">
			<h3>${mw.message('ratePage-vote-title').text()}</h3>
			<div class="pageRatingStars"><center>
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="1"></div>
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="2"></div>
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="3"></div>
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="4"></div>
			<div class="ratingstar ratingstar-mobile ratingstar-plain" data-ratingstar-no="5"></div>
			<br clear="all" /></center>
			<span class="ratingsinfo-mobile"><span id="ratingsinfo-yourvote"></span> <span id="ratingsinfo-avg"></span></span></div></div>`;
    
		$('.last-modified-bar').after(htmlCode);
	} else  {
			// for timeless
			$('#p-ratePage-vote-title').removeClass("emptyPortlet");
			$('#p-ratePage-vote-title > div').append("<div id='ratingstars'></div>");
			for (var i = 1; i <= 5; i++) {
				$("#ratingstars").append(`<div class="ratingstar ratingstar-desktop ratingstar-plain" title="${mw.message(`ratePage-caption-${i.toString()}`).text()}" data-ratingstar-no="${i.toString()}"></div>`);
			}
			$('#ratingstars').after('<br clear="all" /><span class="ratingsinfo-desktop"><div id="ratingsinfo-yourvote"></div><div id="ratingsinfo-avg"></div></span>');
	}
	
	/* init */
	(new mw.Api()).post({
    	action: 'pagerating',
    	format: 'json',
    	pagetitle: mw.config.get('wgPageName'),
	})
	.done(function (data) {
       	if (data.userVote !== -1) {
       		var voteCount = 0;
       		for (var i = 1; i <= 5; i++) voteCount += (data.pageRating[i]);
       		var avg = 0;
       		for (i = 1; i <= 5; i++) avg += (data.pageRating[i]*i);
       		avg = avg/voteCount;
       		$('#ratingsinfo-yourvote').text(mw.message('ratePage-vote-info', data.userVote.toString()).text()); 
       		updateStars(avg, voteCount);
       	} else $('#ratingsinfo-yourvote').html('<br />');
	});
	
	$('.ratingstar').click(function() {
		var answer = $(this).attr('data-ratingstar-no');
		ratePage(mw.config.get('wgPageName'), answer);
	});
	
	$('.ratingstar-desktop').mouseover(function(){
		var no = $(this).attr('data-ratingstar-no');
		for (var i = 1; i<=no; i++) {
			$(`.ratingstar[data-ratingstar-no="${i.toString()}"]`).addClass('ratingstar-mousedown');
		}
	});
	
	$('.ratingstar-desktop').mouseout(function(){
		$('.ratingstar').removeClass('ratingstar-mousedown');	
	});
}(jQuery, mw) );
