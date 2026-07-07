<?php

$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

view('session/create.view.php', ['errors' => $errors]);
