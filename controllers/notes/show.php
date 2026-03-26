<?php

use Core\Database;

// connect to mysql database
$config = require base_path('config.php');

$db = new Database($config['database']);

$NOTE_ID = $_GET['id'];

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = $db->query('select * from notes where id = ?', [$NOTE_ID])->findOrFail();

    authorize($note['user_id'] === $USER_ID);

    $db->query('delete from notes where id = ?', [$NOTE_ID]);

    header('location: /notes');

    exit();

} else {
    $note = $db->query('select * from notes where id = ?', [$NOTE_ID])->findOrFail();

    authorize($note['user_id'] === $USER_ID);

    view('notes/show.view.php',[
        'heading' => 'Note',
        'note' => $note
    ]);
}