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
	
	public static function HandleNew( $Request, $Response, $Service, $App )
	{
		var_dump($_POST);
		
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
		
		$Students = $App->Database->prepare( 'SELECT `users`.`UserID`, `Email` FROM `groups_users` JOIN `users` ON `StudentID` = `UserID` WHERE `GroupID` = :id' );
		$Students->bindValue( ':id', $GroupID, \PDO::PARAM_INT );
		$Students->execute();
		$Students = $Students->fetchAll();
		
		var_dump( $Students );
		
		
		
	//	$Response->redirect( '/assignments?success' );
	}
}
