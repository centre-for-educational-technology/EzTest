<?php
namespace System;

class Groups
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		return $App->Twig->render( 'groups.html', [
			'title' => 'Groups',
			'tab' => 'groups',
		] );
	}
}
