<?php
namespace System;

class Login
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		return $App->Twig->render( 'login.html', [
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
	
	public static function HandleRegistration( $Request, $Response, $Service, $App )
	{
		$Service->validateParam( 'username', 'Invalid email' )->isEmail();
		$Service->validateParam( 'realname', 'Missing full name' )->notNull();
		$Service->validateParam( 'password', 'Missing password' )->notNull();
		
		$User = $App->Database->prepare( 'SELECT `Email` FROM `users` WHERE `Email` = :email' );
		$User->bindValue( ':email', $Request->param( 'username' ) );
		$User->execute();
		$User = $User->fetch();
		
		if( $User )
		{
			throw new \Exception( 'This email is already taken.' );
		}
		
		$User = $App->Database->prepare( 'INSERT INTO `users` (`Email`, `Name`, `Password`) VALUES (:email, :name, :password)' );
		$User->bindValue( ':email', $Request->param( 'username' ) );
		$User->bindValue( ':name', $Request->param( 'realname' ) );
		$User->bindValue( ':password', password_hash( $Request->param( 'password' ), PASSWORD_DEFAULT ) );
		$User->execute();
		
		$User = $App->Database->prepare( 'SELECT `UserID`, `Name` FROM `users` WHERE `Email` = :email' );
		$User->bindValue( ':email', $Request->param( 'username' ) );
		$User->execute();
		$User = $User->fetch();
		
		if( !$User )
		{
			throw new \Exception( 'Registration failed, sorry about that.' );
		}
		
		$_SESSION[ 'LoggedIn' ] = true;
		$_SESSION[ 'UserID' ] = (int)$User->UserID;
		$_SESSION[ 'Name' ] = $User->Name;
		
		$Response->redirect( '/' );
	}
}
