<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TicketApp\Application;
use Symfony\Component\HttpFoundation\Request;

// Create application instance
$app = new Application();

// Handle the request
$request = Request::createFromGlobals();
$response = $app->handleRequest($request);

// Send the response
$response->send();
