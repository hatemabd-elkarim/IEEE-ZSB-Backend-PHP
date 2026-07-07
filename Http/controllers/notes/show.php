<?php

use Core\App;
use Core\Database;

$db = App::resolve(Database::class);

$USER_ID = $_SESSION['user']['id'];

$NOTE_ID = $_GET['id'];

$note = $db->query('select * from notes where id = ?', [$NOTE_ID])->findOrFail();

authorize($note['user_id'] === $USER_ID);

view('notes/show.view.php',[
    'heading' => 'Note',
    'note' => $note
]);
