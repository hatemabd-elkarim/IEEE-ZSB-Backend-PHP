<?php

// connect to mysql database
$config = require 'config.php';

$db = new Database($config['database']);

$heading = 'My Notes';

$notes = $db->query('select * from notes where user_id = ?',[$USER_ID])->fetchAll();

require 'views/notes.view.php';
