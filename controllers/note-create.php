<?php

$config = require('config.php');
$db = new Database($config['database']);

$heading = 'New Note';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->query('INSERT INTO notes(body,user_id) VALUES(:body, :user_id)', [
        'body' => $_POST['body'],
        'user_id' => $USER_ID
    ]);
}


require 'views/note-create.view.php';
