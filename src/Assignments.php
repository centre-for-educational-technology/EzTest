<?php
namespace System;

class Assignments
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		$Assignments = $App->Database->prepare(
			'SELECT `AssignmentID`, `assignments`.`Name`, `tests`.`Name` as `TestName`, `assignments`.`TestID`, `assignments`.`GroupID`, `GroupName`, `assignments`.`Date` FROM `assignments` ' .
			'JOIN `tests` ON `tests`.`TestID` = `assignments`.`TestID` ' .
			'JOIN `groups` ON `groups`.`GroupID` = `assignments`.`GroupID` ' .
			'WHERE `assignments`.`UserID` = :userid ORDER BY `AssignmentID` DESC'
		);
		$Assignments->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Assignments->execute();
		$Assignments = $Assignments->fetchAll();
		
		$Groups = $App->Database->prepare( 'SELECT `GroupID`, `GroupName` FROM `groups` WHERE `UserID` = :userid ORDER BY `GroupID` DESC' );
		$Groups->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Groups->execute();
		$Groups = $Groups->fetchAll();
		
		$Tests = $App->Database->prepare( 'SELECT `TestID`, `Name` FROM `tests` WHERE `UserID` = :userid ORDER BY `TestID` DESC' );
		$Tests->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Tests->execute();
		$Tests = $Tests->fetchAll();
		
		return $App->Twig->render( 'assignments.html', [
			'title' => 'Assignments',
			'tab' => 'assignments',
			'assignments' => $Assignments,
			'groups' => $Groups,
			'tests' => $Tests,
			'group_preselected' => (int)filter_input( INPUT_GET, 'groupid' ),
			'test_preselected' => (int)filter_input( INPUT_GET, 'testid' ),
		] );
	}
	
	public static function RenderView( $Request, $Response, $Service, $App )
	{
		$AssignmentID = $Request->ID;
		
		$Assignment = $App->Database->prepare(
			'SELECT `AssignmentID`, `assignments`.`Name`, `tests`.`Name` as `TestName`, `assignments`.`TestID`, `assignments`.`GroupID`, `GroupName`, `assignments`.`Date` FROM `assignments` ' .
			'JOIN `tests` ON `tests`.`TestID` = `assignments`.`TestID` ' .
			'JOIN `groups` ON `groups`.`GroupID` = `assignments`.`GroupID` ' .
			'WHERE `assignments`.`UserID` = :userid AND `AssignmentID` = :id'
		);
		$Assignment->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Assignment->bindValue( ':id', $AssignmentID, \PDO::PARAM_INT );
		$Assignment->execute();
		$Assignment = $Assignment->fetch();
		
		if( !$Assignment )
		{
			$Response->code( 404 );
			
			return 'Assignment not found';
		}
		
		$Students = $App->Database->prepare( 'SELECT `assignments_users`.`UserID`, `Name`, `Email`, `EmailSent`, `LastVisit` FROM `assignments_users` JOIN `users` ON `assignments_users`.`UserID` = `users`.`UserID` WHERE `AssignmentID` = :id' );
		$Students->bindValue( ':id', $AssignmentID, \PDO::PARAM_INT );
		$Students->execute();
		$Students = $Students->fetchAll();
		
		return $App->Twig->render( 'assignments_view.html', [
			'title' => 'Results',
			'tab' => 'assignments',
			'assignment' => $Assignment,
			'students' => $Students,
		] );
	}
	
	public static function HandleNew( $Request, $Response, $Service, $App )
	{
		$Name = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_STRING );
		$Notes = filter_input( INPUT_POST, 'notes', FILTER_SANITIZE_STRING );
		$GroupID = (int)filter_input( INPUT_POST, 'groupid', FILTER_SANITIZE_NUMBER_INT );
		$TestID = (int)filter_input( INPUT_POST, 'testid', FILTER_SANITIZE_NUMBER_INT );
		
		if( $GroupID < 1 )
		{
			throw new \Exception( 'You must select a group to assign.' );
		}
		
		if( $TestID < 1 )
		{
			throw new \Exception( 'You must select a test to assign.' );
		}
		
		$InsertNew = $App->Database->prepare( 'INSERT INTO `assignments` (`UserID`, `GroupID`, `TestID`, `Name`) VALUES (:userid, :groupid, :testid, :name)' );
		$InsertNew->bindValue( ':name', $Name );
		$InsertNew->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$InsertNew->bindValue( ':testid', $TestID, \PDO::PARAM_INT );
		$InsertNew->bindValue( ':groupid', $GroupID, \PDO::PARAM_INT );
		$InsertNew->execute();
		
		$Assignment = $App->Database->prepare( 'SELECT `AssignmentID` FROM `assignments` WHERE `UserID` = :userid ORDER BY `AssignmentID` DESC LIMIT 1' );
		$Assignment->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Assignment->execute();
		$Assignment = $Assignment->fetch();
		
		$Students = $App->Database->prepare( 'SELECT `users`.`UserID`, `Email`, `users`.`Name` FROM `groups_users` JOIN `users` ON `StudentID` = `UserID` WHERE `GroupID` = :id' );
		$Students->bindValue( ':id', $GroupID, \PDO::PARAM_INT );
		$Students->execute();
		$Students = $Students->fetchAll();
		
		$InsertNew = $App->Database->prepare( 'INSERT INTO `assignments_users` (`AssignmentID`, `UserID`, `Hash`) VALUES (:id, :userid, :hash)' );
		$InsertNew->bindValue( ':id', $Assignment->AssignmentID, \PDO::PARAM_INT );
		
		foreach( $Students as $Student )
		{
			$Hash = self::GenerateRandomID( );
			
			$InsertNew->bindValue( ':hash', $Hash );
			$InsertNew->bindValue( ':userid', $Student->UserID, \PDO::PARAM_INT );
			$InsertNew->execute();
			
			// TODO: Send emails
		}
		
		$Response->redirect( '/assignments/view/' . $Assignment->AssignmentID );
	}
	
	private static function GenerateRandomID( $Length = 32 )
	{
		if( function_exists( 'random_bytes' ) )
		{
			$Hash = random_bytes( $Length );
		}
		else if( function_exists( 'mcrypt_create_iv' ) )
		{
			$Hash = mcrypt_create_iv( $Length );
		}
		else if( function_exists( 'openssl_random_pseudo_bytes' ) )
		{
			$Hash = openssl_random_pseudo_bytes( $Length );
		}
		else
		{
			throw new \LogicException( 'Your PHP configuration does not have any cryptographically secure functions available.' );
		}
		
		$Hash = hash( 'sha256', $Hash );
		
		// Split hash into sections of 8 characters
		return implode( '-', str_split( $Hash, 8 ) );
	}
}
