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

		$Test = $App->Database->prepare( 'SELECT `Name` from `tests` WHERE `TestID` = :testid' );
		$Test->bindValue( ':testid', $TestID, \PDO::PARAM_INT );
		$Test->execute();
		$Test = $Test->fetch();
		
		$Students = $App->Database->prepare( 'SELECT `users`.`UserID`, `Email`, `users`.`Name` FROM `groups_users` JOIN `users` ON `StudentID` = `UserID` WHERE `GroupID` = :id' );
		$Students->bindValue( ':id', $GroupID, \PDO::PARAM_INT );
		$Students->execute();
		$Students = $Students->fetchAll();
		
		$InsertNew = $App->Database->prepare( 'INSERT INTO `assignments_users` (`AssignmentID`, `UserID`, `Hash`) VALUES (:id, :userid, :hash)' );
		$InsertNew->bindValue( ':id', $Assignment->AssignmentID, \PDO::PARAM_INT );

		$UpdateEmailSent = $App->Database->prepare( ' UPDATE `assignments_users` SET `EmailSent` = :emailsent WHERE `Hash` = :hash');
		$UpdateEmailSent->bindValue( ':emailsent', 1, \PDO::PARAM_INT );
		
		foreach( $Students as $Student )
		{
			$Hash = self::GenerateRandomID( );
			
			$InsertNew->bindValue( ':hash', $Hash );
			$InsertNew->bindValue( ':userid', $Student->UserID, \PDO::PARAM_INT );
			$InsertNew->execute();
			
			try {
				$Body = $App->Twig->render( 'emails/new_test.html', [
					'UserName' => $Student->Name,
					'AdminName' => $_SESSION[ 'Name' ],
					'AssignmentName' => $Name,
					'AssignmentNote' => $Notes,
					'TestName' => $Test->Name,
					'TestURL' => self::generateTestUrl($Hash),
				] );
				Mail::sendEmail( [ 'name' => $Student->Name, 'email' => $Student->Email ], 'New Assignment', $Body, $_SESSION[ 'Name' ], [
					[
						'path' => __DIR__ . '/../www/assets/img/eztest.png',
						'cid' => 'system_logo',
						'name' => 'system_logo.png'
					]
				] );
				$UpdateEmailSent->bindValue( ':hash', $Hash);
				$UpdateEmailSent->execute();
			} catch (\Exception $e) {
				if ( !$e instanceof Exceptions\MailException ) {
					throw $e;
				}
			}
		}
		
		$Response->redirect( '/assignments/view/' . $Assignment->AssignmentID );
	}
	
	private static function GenerateRandomID( $Length = 32 )
	{
		$Hash = hash( 'sha256', Students::generateRandomHash($Length) );
		
		// Split hash into sections of 8 characters
		return implode( '-', str_split( $Hash, 8 ) );
	}

	private static function generateTestUrl( $Hash )
	{
		$URL = 'http://';

		if (isset($_SERVER['HTTPS']) &&
			($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
			isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
			$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
		)
		{
			$URL = 'https://';
		}

		$URL .= $_SERVER[ 'HTTP_HOST' ];
		$URL .= '/private/' . $Hash;

		return $URL;
	}
}
