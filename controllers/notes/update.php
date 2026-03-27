<?php

use Core\App;
use Core\Database;
use Core\Validator;

$db = App::resolve(Database::class);

$USER_ID = $_SESSION['user']['id'];

$NOTE_ID = $_POST['id'];
// find the corresponding note
$note = $db->query('select * from notes where id = ?', [$NOTE_ID
])->findOrFail();

// authorize that the current user can edit the note
authorize($note['user_id'] === $USER_ID);

// validate the form
$errors = [];

if (! Validator::string($_POST['body'], 1, 1000)) {
    $errors['body'] = 'A body of no more than 1,000 characters is required.';
}

// if no validation errors, update the record in the notes database table.
if (count($errors)) {
    return view('notes/edit.view.php', [
        'heading' => 'Edit Note',
        'errors' => $errors,
        'note' => $note
    ]);
}

$db->query('update notes set body = :body where id = :id', [
    'id' => $_POST['id'],
    'body' => $_POST['body']
]);

// redirect the user
header('location: /notes');
die();
