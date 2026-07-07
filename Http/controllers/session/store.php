<?php

use Http\Forms\LoginForm;
use Core\Authenticator;
use Core\Session;


$email = $_POST['email'];
$password = $_POST['password'];
$authenticator = new Authenticator();

$loginForm = new LoginForm();
if ($loginForm->validate($email, $password)) {
    if ($authenticator->attempt($email, $password)) {
        redirect('/');
    }

    $loginForm->error('email', 'No matching account found for that email address and password.');
}

Session::flash('old', [
    'email' => $email
]);

Session::flash('errors', $loginForm->errors());

redirect('/login');
