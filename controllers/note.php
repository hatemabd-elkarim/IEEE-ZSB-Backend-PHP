<?php

// connect to mysql database
$config = require 'config.php';

$db = new Database($config['database']);

$heading = 'Note';

$NOTE_ID = $_GET['id'];

$note = $db->query('select * from notes where id = ?', [$NOTE_ID])->fetch();

require 'views/note.view.php';
