<?php
namespace System;

class Groups
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		$Groups = $App->Database->prepare( 'SELECT `GroupID`, `GroupName`, `Date` FROM `groups` WHERE `UserID` = :userid ORDER BY `Date` DESC' );
		$Groups->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Groups->execute();
		$Groups = $Groups->fetchAll();
		
		return $App->Twig->render( 'groups.html', [
			'title' => 'Groups',
			'tab' => 'groups',
			'groups' => $Groups,
		] );
	}
}
