<?php

namespace RatePage;

use IContextSource;
use User;

class Rights {
	public static function getAllGroups( IContextSource $context ) {
		$groups = $context->getConfig()->get( 'GroupPermissions' );

		return array_keys( $groups );
	}

	public static function getGroupsAsColumns( IContextSource $context ) {
		$groups = self::getAllGroups( $context );
		$res = [];

		foreach ( $groups as $group ) {
			$res[$context->msg( "group-$group" )->escaped()] = $group;
		}

		return $res;
	}

	public static function checkUserCanExecute( $allowed, User $user ) {
		$groups = explode( ',', $allowed );

		return (bool) sizeof( array_intersect( $groups, $user->getEffectiveGroups() ) );
	}

	public static function checkUserPermissionsOnContest( $contestId, User $user ) {
		$eg = $user->getEffectiveGroups();
		$contest = ContestDB::loadContest( $contestId );

		if ( !$contest ) {
			return [
				'vote' => false,
				'see' => false
			];
		}


		return [
			'vote' => (
				(bool) sizeof( array_intersect( explode( ',', $contest->rpc_allowed_to_vote ), $eg ) ) &&
				( (bool) $contest->rpc_enabled )
			),
			'see' => (bool) sizeof( array_intersect( explode( ',', $contest->rpc_allowed_to_see ), $eg ) )
		];
	}
}
