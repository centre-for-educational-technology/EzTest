<?php
namespace System;

class Groups
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		$Groups = $App->Database->prepare( 'SELECT `GroupID`, `GroupName`, (SELECT COUNT(*) FROM `groups_users` WHERE `GroupID` = `groups`.`GroupID`) as `Size` FROM `groups` WHERE `UserID` = :userid ORDER BY `GroupID` DESC' );
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
		$IsSaving = isset( $_POST[ 'save' ] );
		$Students = [];
		
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
		
		if( !$IsNewGroup && !$IsSaving )
		{
			$StudentsGet = $App->Database->prepare( 'SELECT `Email` FROM `groups_users` JOIN `users` ON `StudentID` = `UserID` WHERE `GroupID` = :id' );
			$StudentsGet->bindValue( ':id', $GroupID, \PDO::PARAM_INT );
			$StudentsGet->execute();
			
			while( $Temp = $StudentsGet->fetch() )
			{
				$Students[] = $Temp->Email;
			}
		}
		
		if( $IsSaving )
		{
			$Name = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_STRING );
			
			if( $IsNewGroup )
			{
				$EditGroup = $App->Database->prepare( 'INSERT INTO `groups` (`UserID`, `GroupName`) VALUES (:userid, :name)' );
				$EditGroup->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
			}
			else
			{
				$EditGroup = $App->Database->prepare( 'UPDATE `groups` SET `GroupName` = :name WHERE `UserID` = :userid AND `GroupID` = :id' );
				$EditGroup->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
				$EditGroup->bindValue( ':id', $GroupID, \PDO::PARAM_INT );
			}
			
			$EditGroup->bindValue( ':name', $Name );
			$EditGroup->execute();
			
			if( $IsNewGroup )
			{
				$Group = $App->Database->prepare( 'SELECT `GroupID` FROM `groups` WHERE `UserID` = :userid ORDER BY `GroupID` DESC LIMIT 1' );
				$Group->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
				$Group->execute();
				$Group = $Group->fetch();
				
				$GroupID = $Group->GroupID;
			}
			
			$Students = filter_input( INPUT_POST, 'tags', FILTER_SANITIZE_STRING );
			$Students = empty( $Students ) ? [] : explode( ',', $Students );
			
			if( !empty( $Students ) )
			{
				foreach( $Students as $Key => $Student )
				{
					if( preg_match( '/^\S+@\S+\.\S+$/', $Student ) !== 1 )
					{
						unset( $Students[ $Key ] );
					}
				}
			}
			
			$InsertStudent = $App->Database->prepare( 'DELETE FROM `groups_users` WHERE `GroupID` = :id' );
			$InsertStudent->bindValue( ':id', $GroupID, \PDO::PARAM_INT );
			$InsertStudent->execute();
			
			if( !empty( $Students ) )
			{
				$CreateStudent = $App->Database->prepare( 'INSERT INTO `users` (`Email`) VALUES(:email) ON DUPLICATE KEY UPDATE `UserID` = `UserID`' );
				$InsertStudent = $App->Database->prepare( 'INSERT INTO `groups_users` (`GroupID`, `StudentID`) VALUES(:id, (SELECT `UserID` FROM `users` WHERE `Email` = :email))' );
				$InsertStudent->bindValue( ':id', $TestID, \PDO::PARAM_INT );
				
				foreach( $Students as $Student )
				{
					$CreateStudent->bindValue( ':email', $Student );
					$CreateStudent->execute();
					
					$InsertStudent->bindValue( ':id', $GroupID, \PDO::PARAM_INT );
					$InsertStudent->bindValue( ':email', $Student );
					$InsertStudent->execute();
				}
			}
			
			$Response->redirect( '/groups/edit/' . $GroupID );
			return;
		}
		
		return $App->Twig->render( 'groups_new.html', [
			'title' => $IsNewGroup ? 'Create New Group' : 'Edit Existing Group',
			'tab' => 'groups',
			'students' => implode( ',', $Students ),
			'group' => $Group,
		] );
	}
}
