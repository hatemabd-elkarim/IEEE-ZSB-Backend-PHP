<?php

$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']); // to allow passing $errors from redirecting

view('session/create.view.php', ['errors' => $errors]);
