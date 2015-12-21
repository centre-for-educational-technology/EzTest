<?php
namespace System;

class Database
{
	public static function Setup( )
	{
		return new \PDO(
			Config::$Database,
			Config::$DatabaseUsername,
			Config::$DatabasePassword,
			[
				\PDO::ATTR_TIMEOUT            => 5,
				\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
			]
		);
	}
}
