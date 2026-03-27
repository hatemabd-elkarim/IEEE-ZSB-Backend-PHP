<?php

use Core\App;
use Core\Database;
use Core\Validator;

$USER_ID = $_SESSION['user']['id'];

$db = App::resolve(Database::class);
$errors = [];

if (!Validator::string($_POST['body'], 1, 1000)) {
    $errors['body'] = 'A body of no more than 1000 charachters is required';
}

if (! empty($errors)) {
    return view('notes/create.view.php', [
        'heading' => 'New Note',
        'errors' => $errors,
    ]);
}

$db->query('INSERT INTO notes(body, user_id) VALUES(:body, :user_id)', [
    'body' => $_POST['body'],
    'user_id' => $USER_ID
]);

header('location: /notes');
exit();




