<?php

use Core\App;
use Core\Database;
use Core\Validator;
use Http\Forms\LoginForm;

$db = App::resolve(Database::class);

$email = $_POST['email'];
$password = $_POST['password'];

$loginForm = new LoginForm();
if (!$loginForm->validate($email, $password)) {
    return view('registration/create.view.php', [
        'errors' => $loginForm->errors()
    ]);
}

$user = $db->query('select * from users where email = :email', [
    'email' => $email
])->find();

if ($user) {
    header('location: /');
    exit();
} else {
    $db->query('INSERT INTO users(email, password) VALUES(:email, :password)', [
        'email' => $email,
        'password' => password_hash($password, PASSWORD_BCRYPT)
    ]);

    login([
        "email" => $email,
        "id" => $db->lastInsertId()
    ]);

    header('location: /');
    exit();
}
