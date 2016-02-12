<?php
namespace System;

class Login
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		return $App->Twig->render( 'login.html', [
			'system_name' => \System\Config::$SystemName,
			'title' => 'Login to ' . \System\Config::$SystemName,
		] );
	}
	
	public static function Handle( $Request, $Response, $Service, $App )
	{
		$Service->validateParam( 'username', 'Invalid email' )->isEmail();
		$Service->validateParam( 'password', 'Missing password' )->notNull();
		
		$User = $App->Database->prepare( 'SELECT `UserID`, `Password`, `Name` FROM `users` WHERE `Email` = :email' );
		$User->bindValue( ':email', $Request->param( 'username' ) );
		$User->execute();
		$User = $User->fetch();
		
		if( !$User )
		{
			throw new \Exception( 'No such user.' );
		}
		
		if( !password_verify( $Request->param( 'password' ), $User->Password ) )
		{
			throw new \Exception( 'Invalid password.' );
		}
		
		$_SESSION[ 'LoggedIn' ] = true;
		$_SESSION[ 'UserID' ] = (int)$User->UserID;
		$_SESSION[ 'Name' ] = $User->Name;
		
		$Response->redirect( '/' );
	}
	
	public static function HandleLogout( $Request, $Response, $Service, $App )
	{
		session_destroy();
		
		$Response->redirect( '/' );
	}
}
