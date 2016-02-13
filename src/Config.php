<?php
namespace System;

class Config
{
	// System name/title
	public static $SystemName = 'QTI System Name';
	
	//
	// MySQL
	//
	
	// SQL server connectiong string
	// See https://en.wikipedia.org/wiki/Data_source_name
	public static $Database = 'mysql:host=127.0.0.1;port=3306;dbname=edu_testing;charset=utf8';
	
	// SQL server username
	public static $DatabaseUsername = 'edu_testing';
	
	// SQL server password
	public static $DatabasePassword = '';
	
	
	//
	// SMTP
	//
	
	// Send mails from this address
	public static $MailSendFrom = 'edusystem@noreply.localhost';
	
	// Specify main and backup SMTP servers
	// Leave empty if you want to use PHP's mail() function
	public static $MailHost = '';
	
	// TCP port to connect to
	public static $MailPort = 587;
	
	// SMTP username
	public static $MailUsername = '';
	
	// SMTP password
	public static $MailPassword = '';
	
	// Enable TLS encryption, `ssl` also accepted
	public static $MailSecure = 'tls';
}
