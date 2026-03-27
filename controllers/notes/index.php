<?php

use Core\App;
use Core\Database;

$db = App::resolve(Database::class);

$USER_ID = $_SESSION['user']['id'];

$notes = $db->query('select * from notes where user_id = ?',[$USER_ID])->findALl();

view('notes/index.view.php',[
    'heading' => 'My Notes',
    'notes' => $notes,
]);
