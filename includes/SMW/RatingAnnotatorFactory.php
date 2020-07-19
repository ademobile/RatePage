<?php

namespace RatePage\SMW;

class RatingAnnotatorFactory {

	public static function newFromTitle( Title $title ) {
		$ratings = RatePageRating::getPageRating( $title );
		$total = 0;
		$num = 0;

		foreach ( $ratings as $answer => $count ) {
			$total += $answer * $count;
			$num += $count;
		}

		return [
			new AverageRatingPropertyAnnotator( $total / $num )
		];
	}
}