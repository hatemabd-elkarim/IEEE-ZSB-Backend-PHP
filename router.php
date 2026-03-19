<?php

$routes = require('routes.php');

function routeToController($uri, $routes, $USER_ID = 1) {
    if(array_key_exists($uri, $routes))
        require $routes[$uri];
    else 
        abort();   
}

function abort($code = Response::NOT_FOUND) {
    http_response_code($code);

    require "views/{$code}.php"; // used "" to take $code as a variable NOT LITERAL

    die();
}

$uri = parse_url($_SERVER['REQUEST_URI'])['path'];

routeToController($uri, $routes);