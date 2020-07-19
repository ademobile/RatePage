<?php

namespace RatePage\SMW;

use SMW\PropertyAnnotator;

class AverageRatingPropertyAnnotator implements PropertyAnnotator {

	/** @var float */
	private $avgRating;

	/**
	 * AverageRatingPropertyAnnotator constructor.
	 *
	 * @param float $avgRating
	 */
	public function __construct( float $avgRating ) {
		$this->avgRating = $avgRating;
	}

	public function getSemanticData() {
		// TODO: Implement getSemanticData() method.
	}

	public function addAnnotation() {
		// TODO: Implement addAnnotation() method.
	}
}