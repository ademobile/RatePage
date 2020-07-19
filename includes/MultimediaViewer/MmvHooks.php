<?php

namespace RatePage\MultimediaViewer;
use MultimediaViewerHooks;
use User;

/**
 * Enables access to MMV's internal functionality.
 * Class RatePage\MultimediaViewer\RatePageMmvHooks
 */
class MmvHooks extends MultimediaViewerHooks {
	/**
	 * Returns whether MMV should be enabled for this user.
	 *
	 * @param User $user
	 *
	 * @return bool
	 */
	public static function isMmvEnabled( User $user ) : bool {
		return self::shouldHandleClicks( $user );
	}
}