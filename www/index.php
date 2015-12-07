<?php
require_once __DIR__ . '/../vendor/autoload.php';

$Klein = new \Klein\Klein();

$Klein->respond( 'GET', '/', function()
{
	return 'Hello World!';
} );

$Klein->dispatch();
