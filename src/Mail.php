<?php
namespace System;

class Mail
{
	public static function SendEmail( $Address, $Subject, $Body, $From )
	{
		$Mail = new \PHPMailer;
		$Mail->IsHTML( true );
		$Mail->Subject = $Subject;
		$Mail->Body    = $Body;
		$Mail->SMTPDebug = 2; echo '<pre>'; // DEBUG
		
		if( !empty( Config::$MailHost ) )
		{
			$Mail->isSMTP();
			
			$Mail->Host       = Config::$MailHost;
			$Mail->Port       = Config::$MailPort;
			$Mail->SMTPSecure = Config::$MailSecure;
			
			if( !empty( Config::$MailUsername ) )
			{
				$Mail->SMTPAuth = true;
				$Mail->Username = Config::$MailUsername;
				$Mail->Password = Config::$MailPassword;
			}
		}
		
		$Mail->setFrom( Config::$MailSendFrom, $From . ' (' . Config::$SystemName . ')' );
		$Mail->addAddress( $Address );
		
		if( !$Mail->send() )
		{
			throw new \Exception( 'Failed to send email: ' . $Mail->ErrorInfo );
		}
	}
}
