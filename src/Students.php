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

	public static function generateRandomHash( $Length = 32 )
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

		return $Hash;
	}
}
