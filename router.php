<?php

$uri = parse_url($_SERVER['REQUEST_URI'])['path'];

$routes = [
    '/' => 'controllers/index.php',
    '/about' => 'controllers/about.php',
    '/contact' => 'controllers/contact.php',
    '/notes' => 'controllers/notes.php',
    '/note' => 'controllers/note.php'
];

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

routeToController($uri, $routes);