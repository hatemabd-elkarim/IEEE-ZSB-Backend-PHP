<?php

// connect to mysql database
$config = require base_path('config.php');

$db = new Database($config['database']);

$notes = $db->query('select * from notes where user_id = ?',[$USER_ID])->findALl();

view('notes/index.view.php',[
    'heading' => 'My Notes',
    'notes' => $notes,
]);
