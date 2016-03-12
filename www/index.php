<?php
require_once __DIR__ . '/../vendor/autoload.php';

session_start();

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
			'debug' => true,
			//'cache' => __DIR__ . '/../templates/cache',
		] );
		$Environment->addExtension( new Twig_Extension_Debug() );
		$Environment->addExtension( new Twig_Extensions_Extension_Array() );
		
		$Environment->addGlobal( 'system_name', \System\Config::$SystemName );
		
		if( isset( $_SESSION[ 'LoggedIn' ] ) )
		{
			$Environment->addGlobal( 'logged_in', true );
			$Environment->addGlobal( 'logged_in_userid', $_SESSION[ 'UserID' ] );
			$Environment->addGlobal( 'logged_in_name', $_SESSION[ 'Name' ] );
		}
		
		return $Environment;
    } );
} );

$Klein->onError( function( $Klein, $Message, $Type )
{
	if( empty( $Message ) )
	{
		$Message = 'Unknown failure.';
	}
	
	echo $Klein->App()->Twig->render( 'error.html', [
		'title' => 'Error',
		'type' => $Type,
		'message' => $Message,
	] );
} );

if( !isset( $_SESSION[ 'LoggedIn' ] ) )
{
	$Klein->respond( 'GET', '/login', [ 'System\Login', 'Render' ] );
	$Klein->respond( 'POST', '/login', [ 'System\Login', 'Handle' ] );
	
	$Klein->onHttpError( function( $Code, $Router )
	{
		if( $Code === 404 )
		{
			$Router->response()->redirect( '/login' );
		}
	} );
}
else
{
	$Klein->respond( 'GET', '/logout', [ 'System\Login', 'HandleLogout' ] );
	
	$Klein->respond( 'GET', '/', function( $Request, $Response, $Service, $App )
	{
		$Response->redirect( '/questions' );
	} );
	
	$Klein->respond( 'GET', '/questions', [ 'System\Questions', 'Render' ] );
	$Klein->respond( 'POST', '/questions/upload', [ 'System\Questions', 'HandleFileUpload' ] );
	
	$Klein->respond( 'GET', '/tests', [ 'System\Tests', 'Render' ] );
	$Klein->respond( 'GET', '/tests/new', [ 'System\Tests', 'RenderNewTest' ] );
	$Klein->respond( 'POST', '/tests/new', [ 'System\Tests', 'RenderNewTest' ] );
	$Klein->respond( 'GET', '/tests/edit/[i:ID]', [ 'System\Tests', 'RenderNewTest' ] );
	$Klein->respond( 'POST', '/tests/edit/[i:ID]', [ 'System\Tests', 'RenderNewTest' ] );
	
	$Klein->respond( 'GET', '/groups', [ 'System\Groups', 'Render' ] );
	$Klein->respond( 'GET', '/students', [ 'System\Students', 'Render' ] );
	$Klein->respond( 'GET', '/assignments', [ 'System\Assignments', 'Render' ] );
}

$Klein->respond( 'GET', '/question/[i:ID]', [ 'System\Test', 'DisplayQuestion' ] );
$Klein->respond( 'POST', '/question/[i:ID]', [ 'System\Test', 'HandleQuestionAnswer' ] );

$Klein->dispatch();
