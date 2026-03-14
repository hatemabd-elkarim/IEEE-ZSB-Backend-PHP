<?php

require 'functions.php';

require 'Database.php';

require 'router.php';

// connect to mysql database
$config = require 'config.php';

$db = new Database($config['database']);

$posts = $db->query('select * from posts')->fetchAll();

// dd($posts);