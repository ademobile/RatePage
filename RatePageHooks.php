<?php
/**
 * RatePage extension hooks
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */
class RatePageHooks {
	/**
	 * Conditionally register the unit testing module for the ext.ratePage module
	 * only if that module is loaded
	 *
	 * @param array $testModules The array of registered test modules
	 * @param ResourceLoader $resourceLoader The reference to the resource loader
	 * @return true
	 */
	public static function onResourceLoaderTestModules( array &$testModules, ResourceLoader &$resourceLoader ) {
		$testModules['qunit']['ext.ratePage.tests'] = [
			'scripts' => [
				'tests/RatePage.test.js'
			],
			'dependencies' => [
				'ext.ratePage'
			],
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'RatePage',
		];
		return true;
	}
}
