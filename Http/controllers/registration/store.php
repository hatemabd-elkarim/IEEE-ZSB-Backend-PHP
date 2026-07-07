<?php

use Core\App;
use Core\Database;
use Core\Authenticator;
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
    redirect(
        '/login',
        ['errors' => ['email' => 'An account with provided email address already exists.']] // to allow passing $errors from redirecting
    );
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
