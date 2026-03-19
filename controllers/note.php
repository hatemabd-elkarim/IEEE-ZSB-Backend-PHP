<?php

// connect to mysql database
$config = require 'config.php';

$db = new Database($config['database']);

$heading = 'Note';

$NOTE_ID = $_GET['id'];

$note = $db->query('select * from notes where id = ?', [$NOTE_ID])->fetch();


if (!$note) { // if the given id does not match any notes
    abort();
}

if ($note['user_id'] !== $USER_ID) {
    abort(Response::FORBIDDEN);
}

require 'views/note.view.php';
