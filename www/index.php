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
			//'cache' => __DIR__ . '/../templates/cache',
		] );
		
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
	
	$Klein->respond( 'GET', '/', function() { echo '<a href="/login">Login</a>'; } );
}
else
{
	$Klein->respond( 'GET', '/logout', [ 'System\Login', 'HandleLogout' ] );
	
	$Klein->respond( 'GET', '/', function() { echo 'You are logged in! <a href="/logout">Logout</a>'; } );
}

$Klein->respond( 'GET', '/questions', [ 'System\Test', 'DisplayAllQuestions' ] );
$Klein->respond( 'GET', '/question/[i:ID]', [ 'System\Test', 'DisplayQuestion' ] );
$Klein->respond( 'POST', '/question/[i:ID]', [ 'System\Test', 'HandleQuestionAnswer' ] );

$Klein->respond( 'GET', '/test', [ 'System\Test', 'HandleRender' ] );

$Klein->dispatch();
