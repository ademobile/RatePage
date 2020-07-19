<?php

use SMW\PropertyRegistry;

class RatePageSmwHooks {
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
		wfDebugLog('ratepage', 'dedede');
		$definitions = [
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
}
