<?php
namespace System;

class Config
{
	// SQL server connectiong string
	// See https://en.wikipedia.org/wiki/Data_source_name
	public static $Database = 'mysql:host=127.0.0.1;port=3306;dbname=edu_testing;charset=utf8';
	
	// SQL server username
	public static $DatabaseUsername = 'root';
	
	// SQL server password
	public static $DatabasePassword = '';
}
