<?php
namespace System;

class Groups
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		$Groups = $App->Database->prepare( 'SELECT `GroupID`, `GroupName`, (SELECT COUNT(*) FROM `groups_users` WHERE `GroupID` = `groups`.`GroupID`) as `Size` FROM `groups` WHERE `UserID` = :userid ORDER BY `Date` DESC' );
		$Groups->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Groups->execute();
		$Groups = $Groups->fetchAll();
		
		return $App->Twig->render( 'groups.html', [
			'title' => 'Groups',
			'tab' => 'groups',
			'groups' => $Groups,
		] );
	}
	
	public static function RenderNewGroup( $Request, $Response, $Service, $App )
	{
		$GroupID = $Request->ID;
		$IsNewGroup = $GroupID < 1;
		
		if( !$IsNewGroup )
		{
			$Group = $App->Database->prepare( 'SELECT `GroupID`, `GroupName` FROM `groups` WHERE `UserID` = :userid AND `GroupID` = :id' );
			$Group->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
			$Group->bindValue( ':id', $GroupID, \PDO::PARAM_INT );
			$Group->execute();
			$Group = $Group->fetch();
			
			if( !$Group )
			{
				$Response->code( 404 );
				
				return 'Group not found';
			}
		}
		else
		{
			$Group = new \stdClass();
		}
		
		$Students = filter_input( INPUT_POST, 'tags', FILTER_SANITIZE_STRING );
		$Students = empty( $Students ) ? [] : explode( ',', $Students );
		
		if( !$IsNewGroup && empty( $Students ) )
		{
			$StudentsGet = $App->Database->prepare( 'SELECT `Email` FROM `groups_users` JOIN `users` ON `StudentID` = `UserID` WHERE `GroupID` = :id' );
			$StudentsGet->bindValue( ':id', $GroupID, \PDO::PARAM_INT );
			$StudentsGet->execute();
			
			while( $Temp = $StudentsGet->fetch() )
			{
				$Students[] = $Temp->Email;
			}
		}
		
		return $App->Twig->render( 'groups_new.html', [
			'title' => $IsNewGroup ? 'Create New Group' : 'Edit Existing Group',
			'tab' => 'groups',
			'students' => implode( ',', $Students ),
			'group' => $Group,
		] );
	}
}
