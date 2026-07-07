## Milestone 9: Final Refactors — Clean Architecture & Unit Testing

> **Objective:** Extract validation logic into dedicated classes.

---

## 1. Updated Project Structure

```
project/
│
├── public/
│   └── index.php
│
├── Core/
│   ├── App.php
│   ├── Authenticator.php       ← NEW
│   ├── Container.php
│   ├── Database.php
│   ├── Response.php
│   ├── Router.php
│   ├── Session.php             ← NEW
│   ├── ValidationException.php ← NEW
│   ├── Validator.php
│   ├── functions.php
│   └── Middleware/
│       ├── Auth.php
│       ├── Guest.php
│       └── Middleware.php
│
├── Http/                       ← NEW
│   └── Forms/
│       └── LoginForm.php
│
├── controllers/  (moved to Http/ in final structure)
├── views/
├── tests/                      ← NEW
│   ├── ContainerTest.php
│   └── ValidatorTest.php
│
├── vendor/                     ← Composer-managed
├── composer.json
└── ...
```

---

## 2. Form Request Objects — `Http/Forms/LoginForm.php`

```php
<?php

namespace Http\Forms;

use Core\Validator;

class LoginForm
{
    protected $errors = [];

    public function validate($email, $password)
    {
        if (!Validator::email($email)) {
            $this->errors['email'] = 'Please provide a valid email address.';
        }

        if (!Validator::string($password)) {
            $this->errors['password'] = 'Please provide a valid password.';
        }

        return empty($this->errors);
    }

    public function errors()
    {
        return $this->errors;
    }

    public function error($field, $message)
    {
        $this->errors[$field] = $message;
    }
}
```

Previously, validation logic lived directly inside each controller. As the number of forms grows, this leads to duplicated validation rules and controllers bloated with logic that is not their responsibility. A **form class** encapsulates the validation rules for a specific form in one place.

`validate()` runs all the field checks and returns `true` if the form is valid, `false` otherwise. The controller calls it as a single condition rather than running individual checks inline. `error()` allows the controller to attach additional errors after validation — for example, appending a credential mismatch error that cannot be determined until the database is consulted.

The `Http\Forms` namespace reflects the folder path `Http/Forms/` — consistent with the autoloading convention established in Milestone 6.

### Updated `controllers/session/store.php`

```php
<?php

use Core\Authenticator;
use Http\Forms\LoginForm;

$email    = $_POST['email'];
$password = $_POST['password'];

$form = new LoginForm();

if ($form->validate($email, $password)) {
    if ((new Authenticator)->attempt($email, $password)) {
        redirect('/');
    }

    $form->error('email', 'No matching account found for that email address and password.');
}

return view('session/create.view.php', [
    'errors' => $form->errors()
]);
```

The controller is now concerned only with orchestration: create the form, validate, attempt login, handle the result. The validation rules themselves are entirely inside `LoginForm`.

---

## 3. The `Authenticator` Class — `Core/Authenticator.php`

```php
<?php

namespace Core;

class Authenticator
{
    public function attempt($email, $password)
    {
        $user = App::resolve(Database::class)
            ->query('SELECT * FROM users WHERE email = :email', [
                'email' => $email,
            ])->find();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $this->login(['email' => $email]);
                return true;
            }
        }

        return false;
    }

    public function login($user)
    {
        $_SESSION['user'] = ['email' => $user['email']];
        session_regenerate_id(true);
    }

    public function logout()
    {
        $_SESSION = [];
        session_destroy();

        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
}
```

`login()` and `logout()` are moved from the global `functions.php` into an `Authenticator` class. This groups authentication responsibilities cohesively and removes global function pollution. The methods themselves are unchanged; they now live in a proper namespace and can be type-hinted, mocked in tests, or extended.

`attempt()` is a new method that combines the database lookup and password verification into a single intent-revealing call. It returns `true` on success and `false` on failure, so the controller can branch cleanly without repeating the lookup/verify logic. It resolves `Database` through the service container — `Authenticator` does not need to know how the database is configured.

---

## 4. The PRG Problem & Session Flashing

### The Problem

When a form submission fails validation, the previous approach returned a view directly from the controller within the same request:

```php
// Problematic — returns a view on POST
return view('session/create.view.php', ['errors' => $errors]);
```

The browser is now on a POST URL displaying an error page. If the user refreshes, the browser re-submits the POST request — potentially inserting duplicate records or triggering duplicate actions. This is the **Post/Redirect/Get (PRG)** problem.

The correct solution is to always redirect after a POST, even on failure. But a redirect is a new GET request — the errors from the failed POST need to survive across that redirect. Session flashing solves this.

### Session Flashing

A **flash** is data written to the session that is intended to last for exactly one request — it is read on the next request and then deleted. This makes it perfect for passing error messages or form data across a redirect.

```
POST /session (validation fails)
  └─ Session::flash('errors', $errors)
  └─ Session::flash('old', $_POST)
  └─ redirect('/login')           ← errors stored in session

GET /login (redirected)
  └─ Session::get('errors')       ← errors read from session
  └─ render view with errors
  └─ Session::unflash()           ← errors deleted from session

GET /login (user refreshes)
  └─ Session::get('errors')       ← null, errors are gone
  └─ render empty form
```

---

## 5. The `Session` Class — `Core/Session.php`

```php
<?php

namespace Core;

class Session
{
    public static function has($key): bool
    {
        return (bool) static::get($key);
    }

    public static function put($key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null): mixed
    {
        return $_SESSION['_flash'][$key] ?? $_SESSION[$key] ?? $default;
    }

    public static function flash($key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function unflash(): void
    {
        unset($_SESSION['_flash']);
    }

    public static function flush(): void
    {
        $_SESSION = [];
    }

    public static function destroy(): void
    {
        static::flush();
        session_destroy();

        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
}
```

`Session` wraps `$_SESSION` in a typed, static API. Key design decisions:

- **`get()`** checks `$_SESSION['_flash'][$key]` first, then `$_SESSION[$key]`, then returns `$default`. Flash data and regular session data share the same getter — callers do not need to know whether a value was flashed or stored permanently.
- **`flash()`** stores data under the `_flash` sub-key, isolating it from regular session data.
- **`unflash()`** deletes the entire `_flash` sub-key in one call. This is invoked at the end of every request in `public/index.php`, ensuring flash data lives for exactly one request.
- **`destroy()`** consolidates the three-step logout process from `Authenticator::logout()` — flush, destroy server session, expire cookie — behind a single meaningful method name.

### The `old()` Helper — `Core/functions.php`

```php
function old($key, $default = '')
{
    return Session::get('old')[$key] ?? $default;
}
```

`old()` retrieves a previously submitted field value from the flashed `'old'` data. It is used in form views to repopulate inputs after a failed submission, so the user does not have to retype everything:

```html
<input name="email" value="<?= old('email') ?>" />
```

On first load, `Session::get('old')` returns `null`, so `$default` (`''`) is used. After a failed POST that flashed `$_POST` as `'old'`, the value is retrieved and pre-filled.

---

## 6. `ValidationException` — `Core/ValidationException.php`

```php
<?php

namespace Core;

class ValidationException extends \Exception
{
    public readonly array $errors;
    public readonly array $old;

    public static function throw($errors, $old): never
    {
        $instance = new static('The form failed to validate.');

        $instance->errors = $errors;
        $instance->old    = $old;

        throw $instance;
    }
}
```

`ValidationException` extends PHP's built-in `\Exception` and carries two `readonly` properties: `$errors` (the validation messages) and `$old` (the submitted form data). `readonly` means these can be set once at construction and never modified — making the exception object immutable.

The static `throw()` factory method constructs and immediately throws the exception in one call, giving form objects a clean way to signal failure without the controller needing to check a return value.

### Handling in `public/index.php`

```php
try {
    $router->route($uri, $method);
} catch (ValidationException $exception) {
    Session::flash('errors', $exception->errors);
    Session::flash('old', $exception->old);

    return redirect($router->previousUrl());
}

Session::unflash();
```

The try/catch wraps every request. If any controller throws a `ValidationException`, the errors and old input are flashed to the session and the user is redirected to the previous URL — fully implementing the PRG pattern at the framework level. No individual controller needs to handle this redirect itself; throwing the exception is sufficient.

`Session::unflash()` runs after every successful request, clearing flash data at the end of the lifecycle.

---

## 7. Composer

**Composer** is PHP's dependency manager and the de facto standard for managing third-party packages and autoloading in modern PHP projects. It reads a `composer.json` file to determine which packages to install and generates a `vendor/autoload.php` file that handles class loading automatically.

Before Composer, PHP projects required manual `require` statements for every library file. Composer replaces this with a single require:

```php
require BASE_PATH . 'vendor/autoload.php';
```

From that point, any class from any installed package — and any class configured in `composer.json`'s autoload section — is available on demand without additional requires.

### PSR-4 Autoloading

Composer supports the **PSR-4** autoloading standard, where namespace prefixes are mapped to directory paths in `composer.json`:

```json
{
  "autoload": {
    "psr-4": {
      "Core\\": "Core/",
      "Http\\": "Http/"
    }
  }
}
```

This supersedes the custom `spl_autoload_register()` from Milestone 6 — Composer generates an optimised autoloader that handles all registered namespaces, including those of installed packages.

### Installing Packages

Packages are installed via the terminal:

```bash
composer require vendor/package-name
composer require --dev pestphp/pest
```

`--dev` marks a package as a development dependency — it will not be installed in a production deployment.

---

## 8. Unit Testing with Pest

**Pest** is a PHP testing framework built on top of PHPUnit that provides an expressive, function-based API for writing tests. Rather than extending test classes and writing methods, tests are written as closures passed to `test()` or `it()` functions.

Tests are placed in the `tests/` directory and run from the terminal:

```bash
./vendor/bin/pest
```

### `ContainerTest.php`

```php
<?php

use Core\Container;

test('it can resolve something out of the container', function () {
    $container = new Container();

    $container->bind('foo', fn() => 'bar');

    $result = $container->resolve('foo');

    expect($result)->toEqual('bar');
});
```

This test verifies the fundamental behaviour of the `Container` class: bind a key to a resolver, resolve it, and confirm the returned value matches the expected output. `expect()->toEqual()` is Pest's assertion syntax — readable as plain English.

### `ValidatorTest.php`

```php
<?php

use Core\Validator;

it('validates a string', function () {
    expect(Validator::string('foobar'))->toBeTrue();
    expect(Validator::string(false))->toBeFalse();
    expect(Validator::string(''))->toBeFalse();
});

it('validates a string with a minimum length', function () {
    expect(Validator::string('foobar', 20))->toBeFalse();
});

it('validates an email', function () {
    expect(Validator::email('foobar'))->toBeFalse();
    expect(Validator::email('foobar@example.com'))->toBeTrue();
});

it('validates that a number is greater than a given amount', function () {
    expect(Validator::greaterThan(10, 1))->toBeTrue();
    expect(Validator::greaterThan(10, 100))->toBeFalse();
})->only();
```

Each `it()` block describes one behavioural expectation in natural language. Multiple `expect()` calls inside one test cover different cases for the same behaviour. The `->only()` chain at the end of the last test tells Pest to run only that test when executing the suite — useful during development when focusing on a single failing test, equivalent to skipping all others temporarily.

### Why Testing Matters

Without tests, verifying that a refactor has not broken existing behaviour requires manually navigating every part of the application. With tests, running `./vendor/bin/pest` confirms in seconds that `Container::resolve()` and `Validator::string()` still behave correctly after any change. As the test suite grows, it becomes a safety net that makes refactoring confident rather than risky.

---
