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

$Klein->respond( 'GET', '/question/[i:ID]', [ 'System\Test', 'DisplayQuestion' ] );

$Klein->respond( 'GET', '/test', [ 'System\Test', 'HandleRender' ] );
$Klein->respond( 'POST', '/test', [ 'System\Test', 'HandleAnswer' ] );

$Klein->dispatch();
