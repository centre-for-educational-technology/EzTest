<?php
namespace System;

class Tests
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		return $App->Twig->render( 'tests.html', [
			'system_name' => \System\Config::$SystemName,
			'title' => 'Tests - ' . \System\Config::$SystemName,
			'tab' => 'tests',
		] );
	}
}
