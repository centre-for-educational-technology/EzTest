<?php
namespace System;

class Mail
{
	public static function SendEmail( $Address, $Subject, $Body, $From, $EmbeddedImages )
	{
		$Mail = new \PHPMailer;
		$Mail->IsHTML( true );
		$Mail->Subject = $Subject;
		$Mail->Body    = $Body;
		$Mail->SMTPDebug = 0;
		
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
		
		if ( is_array( $Address ) )
		{
			$Mail->addAddress( $Address['email'], $Address['name'] );
		} else {
			$Mail->addAddress( $Address );
		}

		if ( $EmbeddedImages && is_array($EmbeddedImages) && count($EmbeddedImages) > 0 )
		{
			foreach ( $EmbeddedImages as $Image )
			{
				$Mail->addEmbeddedImage( $Image['path'], $Image['cid'], $Image['name'] );
			}
		}
		
		if( !$Mail->send() )
		{
			throw new Exceptions\MailException( 'Failed to send email: ' . $Mail->ErrorInfo );
		}
	}
}
