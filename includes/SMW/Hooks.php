<?php

namespace RatePage\SMW;

use MediaWiki\MediaWikiServices;
use RatePage\Rating;
use RatePage\SMW\PropertyAnnotator\RatingAnnotatorFactory;
use SMW\PropertyRegistry;
use SMW\SemanticData;
use SMW\Store;

class Hooks {
	// property ids
	const PROP_RATING_AVERAGE = '__rp_average';
	const PROP_RATING_COUNT = '__rp_count';

	// canonical labels
	const PROP_LABEL_RATING_AVERAGE = 'Average rating';
	const PROP_LABEL_RATING_COUNT = 'Ratings count';

	/**
	 * Register custom SMW properties
	 * @param PropertyRegistry $propertyRegistry
	 * @return bool
	 */
	static function onInitProperties ( PropertyRegistry $propertyRegistry ) : bool {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$definitions = [];

		if ( $config->get( 'RPEnableSMWRatings' ) ) {
			$definitions += self::getRatingPropDefinitions();
		}

		foreach ( $definitions as $propertyId => $definition ) {
			$propertyRegistry->registerProperty(
				$propertyId,
				$definition['type'],
				$definition['label'],
				$definition['viewable'],
				$definition['annotable']
			);

			$propertyRegistry->registerPropertyAlias(
				$propertyId,
				wfMessage( $definition['alias'] )->text()
			);

			$propertyRegistry->registerPropertyAliasByMsgKey(
				$propertyId,
				$definition['alias']
			);

			$propertyRegistry->registerPropertyDescriptionByMsgKey(
				$propertyId,
				$definition['description']
			);
		}

		return true;
	}

	private static function getRatingPropDefinitions() {
		return [
			self::PROP_RATING_AVERAGE => [
				'label' => self::PROP_LABEL_RATING_AVERAGE,
				'type'  => '_num',
				'alias' => 'ratePage-property-average-alias',
				'description' => 'ratePage-property-average-description',
				'viewable' => true,
				'annotable' => false
			],
			self::PROP_RATING_COUNT => [
				'label' => self::PROP_LABEL_RATING_COUNT,
				'type'  => '_num',
				'alias' => 'ratePage-property-count-alias',
				'description' => 'ratePage-property-average-description',
				'viewable' => true,
				'annotable' => false
			]
		];
	}

	public static function onBeforeDataUpdateComplete( Store $store, SemanticData $semanticData ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$title = $semanticData->getSubject()->getTitle();
		if ( $title === null || !Rating::canPageBeRated( $title ) ) {
			return true;
		}

		$annotators = [];
		if ( $config->get( 'RPEnableSMWRatings' ) ) {
			$annotators += RatingAnnotatorFactory::newFromTitle( $title );
		}

		foreach ( $annotators as $annotator ) {
			$annotator->addAnnotation( $semanticData );
		}

		return true;
	}
}
