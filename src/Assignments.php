<?php
namespace System;

class Assignments
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		return $App->Twig->render( 'assignments.html', [
			'title' => 'Assignments',
			'tab' => 'assignments',
		] );
	}
}
