<?php
namespace System;

class Assignments
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		return $App->Twig->render( 'assignments.html', [
			'system_name' => \System\Config::$SystemName,
			'title' => 'Assignments - ' . \System\Config::$SystemName,
			'tab' => 'assignments',
		] );
	}
}
