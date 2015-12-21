<?php
require_once __DIR__ . '/../vendor/autoload.php';

$Klein = new \Klein\Klein();

$Klein->respond( function( $request, $response, $service, $app )
{
    $app->register( 'Database', function()
	{
        return System\Database::Setup();
    } );
} );

$Klein->respond( 'GET', '/', function()
{
	return 'Hello World!';
} );

$Klein->respond( 'GET', '/questions', [ 'System\Test', 'DisplayAllQuestions' ] );
$Klein->respond( 'GET', '/question/[i:ID]', [ 'System\Test', 'DisplayQuestion' ] );
$Klein->respond( 'POST', '/question/[i:ID]', [ 'System\Test', 'HandleQuestionAnswer' ] );

$Klein->respond( 'GET', '/test', [ 'System\Test', 'HandleRender' ] );

$Klein->dispatch();
