<?php
namespace System;

class Students
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		return $App->Twig->render( 'students.html', [
			'system_name' => \System\Config::$SystemName,
			'title' => 'Students - ' . \System\Config::$SystemName,
			'tab' => 'students',
		] );
	}
}
