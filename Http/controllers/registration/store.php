<?php

use Core\App;
use Core\Database;
use Core\Authenticator;
use Core\Session;
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
    Session::flash('errors', [
        'email' => 'An account with that email address already exists.'
    ]);

    Session::flash('old', [
        'email' => $email
    ]);

    redirect(
        '/login');
} else {
    $db->query('INSERT INTO users(email, password) VALUES(:email, :password)', [
        'email' => $email,
        'password' => password_hash($password, PASSWORD_BCRYPT)
    ]);

    (new Authenticator())->login([
        "email" => $email,
        "id" => $db->lastInsertId()
    ]);

    redirect('/');
}
