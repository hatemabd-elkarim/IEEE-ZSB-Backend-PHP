<?php

// connect to mysql database
$config = require base_path('config.php');

$db = new Database($config['database']);

$NOTE_ID = $_GET['id'];

$note = $db->query('select * from notes where id = ?', [$NOTE_ID])->findOrFail();

authorize($note['user_id'] === $USER_ID);

view('notes/show.view.php',[
    'heading' => 'Note',
    'note' => $note
]);
