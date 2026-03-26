<?php

function routeToController($uri, $routes, $USER_ID = 1) {
    if(array_key_exists($uri, $routes))
        require base_path($routes[$uri]);
    else 
        abort();   
}

function abort($code = Response::NOT_FOUND) {
    http_response_code($code);

    view("{$code}.php",['heading' => 'go back']); // used "" to take $code as a variable NOT LITERAL

    die();
}

$routes = require base_path('routes.php');

$uri = parse_url($_SERVER['REQUEST_URI'])['path'];

routeToController($uri, $routes);