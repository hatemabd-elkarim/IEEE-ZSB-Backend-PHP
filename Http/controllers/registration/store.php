<?php

use Core\App;
use Core\Database;
use Core\Authenticator;
use Core\Session;
use Http\Forms\LoginForm;

$db = App::resolve(Database::class);

$email = $_POST['email'];
$password = $_POST['password'];

$form = LoginForm::validate([
    'email' => $email,
    'password' => $password
]);

$user = $db->query('select * from users where email = :email', [
    'email' => $email
])->find();

if ($user) {
    $form->error(
        'email',
        'An account with that email address already exists.'
    )->throw();
}

$db->query('INSERT INTO users(email, password) VALUES(:email, :password)', [
    'email' => $email,
    'password' => password_hash($password, PASSWORD_BCRYPT)
]);

(new Authenticator())->login([
    "email" => $email,
    "id" => $db->lastInsertId()
]);

redirect('/');
