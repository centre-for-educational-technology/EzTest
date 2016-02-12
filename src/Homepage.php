<?php
namespace System;

class Homepage
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		return $App->Twig->render( 'index.html', [
			'system_name' => \System\Config::$SystemName,
			'title' => \System\Config::$SystemName,
		] );
	}
}
