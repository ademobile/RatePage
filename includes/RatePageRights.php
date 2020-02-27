<?php

class RatePageRights {
	public static function getAllGroups( IContextSource $context ) {
		$groups = $context->getConfig()->get( 'GroupPermissions' );
		return array_keys( $groups );
	}

	public static function getGroupsAsColumns( IContextSource $context ) {
		$groups = self::getAllGroups( $context );
		$res = [];

		foreach ( $groups as $group ) {
			$res[ $context->msg( "group-$group" )->escaped() ] = $group;
		}

		return $res;
	}

	public static function checkUserCanExecute( ) {
		//TODO: implement
	}
}