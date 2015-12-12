<?php
use System\Test;

require_once __DIR__ . '/../vendor/autoload.php';

$Klein = new \Klein\Klein();

$Klein->respond( 'GET', '/', function()
{
	return 'Hello World!';
} );

$Klein->respond( 'GET', '/test', [ 'System\Test', 'HandleRender' ] );
$Klein->respond( 'POST', '/test', [ 'System\Test', 'HandleAnswer' ] );

$Klein->dispatch();
