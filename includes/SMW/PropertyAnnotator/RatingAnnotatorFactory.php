<?php

namespace RatePage\SMW\PropertyAnnotator;

use RatePage\Rating;
use Title;

class RatingAnnotatorFactory {

	public static function newFromTitle( Title $title ) {
		$ratings = Rating::getPageRating( $title );
		$total = 0;
		$num = 0;

		foreach ( $ratings as $answer => $count ) {
			$total += $answer * $count;
			$num += $count;
		}

		$annotators = [ new RatingsCountPropertyAnnotator( $num ) ];
		if ( $num > 0 ) {
			$annotators[] = new AverageRatingPropertyAnnotator( $total / $num );
		}

		return $annotators;
	}
}