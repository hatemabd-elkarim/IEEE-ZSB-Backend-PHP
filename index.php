<?php

require 'functions.php';

require 'Database.php';

require 'router.php';

// connect to mysql database
$config = require 'config.php';

$db = new Database($config['database']);

/*
$id = $_GET['id']; 
$posts = $db->query('select * from posts where id = ?', [$id])->fetchAll(); 

// NEVER put user input directly into CRUD SQL. Always use prepared statements.

dd($posts);
*/