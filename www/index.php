<?php
require_once __DIR__ . '/../vendor/autoload.php';

$Klein = new \Klein\Klein();

$Klein->respond( function( $Request, $Response, $Service, $App )
{
	$App->register( 'Database', function()
	{
        return System\Database::Setup();
	} );
	
	$App->register( 'Twig', function()
	{
		$Loader = new Twig_Loader_Filesystem( __DIR__ . '/../templates' );
		$Environment = new Twig_Environment( $Loader,
		[
			//'cache' => __DIR__ . '/../templates/cache',
		] );
		
		return $Environment;
    } );
} );

$Klein->respond( 'GET', '/', function( $Request, $Response, $Service, $App )
{
	echo $App->Twig->render( 'login.html', [ 'title' => 'Login to ' . \System\Config::$SystemName ] );
} );

$Klein->respond( 'GET', '/questions', [ 'System\Test', 'DisplayAllQuestions' ] );
$Klein->respond( 'GET', '/question/[i:ID]', [ 'System\Test', 'DisplayQuestion' ] );
$Klein->respond( 'POST', '/question/[i:ID]', [ 'System\Test', 'HandleQuestionAnswer' ] );

$Klein->respond( 'GET', '/test', [ 'System\Test', 'HandleRender' ] );

$Klein->dispatch();
