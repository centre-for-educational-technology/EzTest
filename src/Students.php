<?php
namespace System;

class Students
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		$Students = $App->Database->prepare(
			'SELECT `UserID`, `Email`, `Name` FROM `users` WHERE `UserID` IN ' .
				'(SELECT `StudentID` FROM `groups_users` WHERE `GroupID` IN ' .
					'(SELECT `GroupID` FROM `groups` WHERE `UserID` = :userid)' .
				') ORDER BY `UserID` DESC'
		);
		$Students->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Students->execute();
		$Students = $Students->fetchAll();
		
		return $App->Twig->render( 'students.html', [
			'title' => 'Students',
			'tab' => 'students',
			'students' => $Students,
		] );
	}
}
