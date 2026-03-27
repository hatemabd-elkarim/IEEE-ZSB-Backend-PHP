## Milestone 6: Namespaces, Autoloading & a Cleaner Architecture
 
> **Objective:** Reorganise the project into a professional directory layout.
 
---
 
## 1. The Problem This Solves
 
By this point the root directory had accumulated a growing mix of files: class definitions, controllers, views, config, and the router all sitting together. Two specific problems emerge as the project scales:
 
- **Security exposure:** Running the PHP server from the project root means every file — including `Database.php`, `config.php`, and `router.php` — is potentially reachable by a direct browser request.
- **Manual class loading:** Every file that needed `Database` or `Validator` had to `require` the right path manually. Adding a new class meant remembering to require it everywhere it was used.
 
This milestone solves both problems: a `public/` folder restricts what is web-accessible, and an autoloader eliminates manual class requires entirely.
 
---
 
## 2. Updated Project Structure
 
```
project/
│
├── public/                     ← Web root — server launched from here
│   └── index.php               ← Entry point: defines BASE_PATH, registers autoloader
│
├── Core/                       ← Framework internals
│   ├── Database.php
│   ├── Validator.php
│   ├── Response.php
│   ├── router.php
│   └── functions.php
│
├── controllers/
│   ├── ...
│   └── notes/                  ← Grouped by feature
│       ├── index.php           ← (was notes.php)
│       ├── show.php            ← (was note.php)
│       └── create.php          ← (was note-create.php)
│
├── views/
│   ├── ...
│   └── notes/                  ← Grouped by feature
│       ├── index.view.php
│       ├── show.view.php
│       └── create.view.php
│
├── config.php
└── routes.php
```
 
The server is now launched with `-t public/` to set the document root to the `public/` folder:
 
```bash
php -S localhost:8888 -t public/
```
 
Any direct browser request to `http://localhost:8888/Core/Database.php` will return a 404 — those files are outside the web root entirely.
 
---
 
## 3. Controller & View Grouping
 
Controllers and views are reorganised into feature subfolders following REST-inspired naming conventions. Instead of flat files like `notes.php`, `note.php`, and `note-create.php`, related files are grouped under `notes/` with descriptive, conventional names:
 
| Old filename | New path | Responsibility |
|---|---|---|
| `notes.php` | `controllers/notes/index.php` | List all notes |
| `note.php` | `controllers/notes/show.php` | Display one note |
| `note-create.php` | `controllers/notes/create.php` | Create a new note |
 
The same applies to views: `notes/index.view.php`, `notes/show.view.php`, `notes/create.view.php`. This grouping makes it immediately clear which files belong to which feature, and mirrors the structure used by most modern PHP frameworks.
 
Routes are updated in `routes.php` to point to the new paths:
 
```php
'/notes'        => 'controllers/notes/index.php',
'/note'         => 'controllers/notes/show.php',
'/note/create'  => 'controllers/notes/create.php',
```
 
---
 
## 4. The `public/index.php` Entry Point
 
```php
<?php
 
const BASE_PATH = __DIR__ . '/../';
 
require BASE_PATH . 'Core/functions.php';
 
spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    require base_path($class . '.php');
});
 
require base_path('Core/router.php');
```
 
`__DIR__` is a PHP magic constant that always resolves to the absolute path of the directory containing the current file — here, `public/`. Appending `/../` moves one level up to the project root. Storing this as a `const` (rather than a variable) makes it globally accessible without needing to be passed around or declared `global`.
 
---
 
## 5. Namespaces
 
A **namespace** declares that a class, function, or constant belongs to a named group, preventing name collisions when two files define a class with the same name. Without namespaces, having both a project-level `Database` class and a third-party library `Database` class in the same application would cause a fatal redeclaration error.
 
Namespaces are declared at the top of a file before any other code:
 
```php
<?php
 
namespace Core;
 
class Database { ... }
class Validator { ... }
class Response { ... }
```
 
To use a namespaced class, the caller either writes its fully qualified name or imports it with `use`:
 
```php
// Fully qualified — works anywhere
$db = new Core\Database($config['database']);
 
// Imported with use — cleaner, equivalent
use Core\Database;
$db = new Database($config['database']);
```
---
 
## 6. Autoloading with `spl_autoload_register()`
 
```php
spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    require base_path($class . '.php');
});
```
 
PHP calls the registered autoload function automatically whenever code references a class that has not yet been loaded. The `$class` parameter receives the **fully qualified class name** — for example, `Core\Database`.
 
The autoloader converts it to a file path in two steps:
 
1. `str_replace('\\', DIRECTORY_SEPARATOR, $class)` — replaces the namespace separator `\` with the OS directory separator (`/` on Unix, `\` on Windows), turning `Core\Database` into `Core/Database`.
2. `require base_path($class . '.php')` — appends `.php` and resolves the full path from the project root, producing something like `/var/www/project/Core/Database.php`.
 
The result: any class in the `Core/` folder with a matching namespace is loaded on demand, with no manual `require` calls needed anywhere. Adding a new class to `Core/` and declaring `namespace Core;` is all it takes to make it available throughout the application.
 
**Before autoloading:**
```php
require base_path('Core/Database.php');
require base_path('Core/Validator.php');
$db = new Database(...);
```
 
**After autoloading:**
```php
use Core\Database;
use Core\Validator;
$db = new Database(...); // loaded automatically on first use
```
 
---
 
## 7. Path Helper Functions — `base_path()` and `view()`
 
With `index.php` now living in `public/`, all relative paths used in `require` statements throughout the project would break — they were written relative to the old root-level `index.php`. Two helper functions in `Core/functions.php` solve this cleanly.
 
### `base_path()`
 
```php
function base_path(string $path): string
{
    return BASE_PATH . $path;
}
```
 
Every `require` that previously used a relative path now goes through `base_path()`, which anchors it to the project root via `BASE_PATH`. Any file anywhere in the project can call `base_path('config.php')` and get the correct absolute path regardless of where it is located.
 
### `view()`
 
```php
function view(string $path, array $attributes = []): void
{
    extract($attributes);
 
    require base_path("views/{$path}");
}
```
 
`view()` replaces the pattern of manually setting variables and then requiring a view file. It accepts the view path and an associative array of data to expose to the template. `extract()` converts each key-value pair in `$attributes` into a local variable — `['heading' => 'About']` becomes `$heading = 'About'` — making those variables available inside the required view file exactly as before.
 
Controllers are dramatically simplified:
 
```php
// Before
$heading = 'About';
require 'views/about.view.php';
 
// After
view('about.view.php', ['heading' => 'About']);
```
 
For the notes feature controllers, the same pattern applies with the subfolder path:
 
```php
// controllers/notes/index.php
use Core\Database;
 
$config = require base_path('config.php');
$db = new Database($config['database']);
 
$notes = $db->query('SELECT * FROM notes WHERE user_id = 1')->get();
 
view('notes/index.view.php', [
    'heading' => 'My Notes',
    'notes'   => $notes,
]);
```
 
---
 
## 8. Updating Partial Paths in Notes Views
 
The top-level views (`about.view.php`, `index.view.php`, `contact.view.php`) continue to require their partials with relative paths because they sit at the same level as the `partials/` folder inside `views/`. The notes views, however, are now one folder deeper inside `views/notes/`, so their partial paths must use `base_path()`:
 
```php
<?php
// views/notes/create.view.php — must use base_path because it is inside a subfolder
require base_path('views/partials/header.php');
require base_path('views/partials/nav.php');
require base_path('views/partials/banner.php');
?>
```
 
This is a direct consequence of moving views into subfolders — relative paths from `views/notes/create.view.php` to `views/partials/` would require `../partials/`, which is fragile. Using `base_path()` anchors every partial require to the project root, making it immune to future folder reorganisation.
 
---
 ## Milestone 7: New router, New methods
 
> **Objective:** Replace the flat routes array with a proper `Router` class that registers and matches routes by both URI and HTTP method.
 
---
 
## 1. Why the Old Router Was Not Enough
 
The previous router matched only on URI. Two routes pointing to `/note` — one for viewing (GET) and one for deleting (DELETE) — were indistinguishable to it. The workaround was a manual `if ($_SERVER['REQUEST_METHOD'] === 'POST')` block inside a single controller, mixing two completely different responsibilities in one file. The new router makes HTTP method a first-class part of route matching, so each action gets its own dedicated controller.
 
---
 
## 2. The `Router` Class — `Core/Router.php`
 
```php
<?php
 
namespace Core;
 
class Router
{
    protected $routes = [];
 
    public function add($method, $uri, $controller)
    {
        $this->routes[] = [
            'uri'        => $uri,
            'controller' => $controller,
            'method'     => $method,
        ];
    }
 
    public function get($uri, $controller)    { $this->add('GET',    $uri, $controller); }
    public function post($uri, $controller)   { $this->add('POST',   $uri, $controller); }
    public function delete($uri, $controller) { $this->add('DELETE', $uri, $controller); }
    public function patch($uri, $controller)  { $this->add('PATCH',  $uri, $controller); }
    public function put($uri, $controller)    { $this->add('PUT',    $uri, $controller); }
 
    public function route($uri, $method)
    {
        foreach ($this->routes as $route) {
            if ($route['uri'] === $uri && $route['method'] === strtoupper($method)) {
                return require base_path($route['controller']);
            }
        }
 
        $this->abort();
    }
 
    protected function abort($code = 404)
    {
        http_response_code($code);
        require base_path("views/{$code}.php");
        die();
    }
}
```
 
### Design decisions
 
Each route is stored as an associative array with three keys: `uri`, `controller`, and `method`. The convenience methods `get()`, `post()`, `delete()`, `patch()`, and `put()` each call `add()` with the corresponding method string.
 
```php
$router->get('/notes', 'controllers/notes/index.php');    // clear intent
$router->delete('/note', 'controllers/notes/destroy.php'); // vs add('DELETE', ...)
```
 
`route()` iterates the registered routes and matches on **both** `uri` and `method`. `strtoupper($method)` normalises the incoming method string so that comparison is always case-insensitive. If no route matches, `abort()` is called — now a protected instance method on the class rather than a global function, keeping the responsibility encapsulated.
 
---
 
## 3. Method Spoofing with `_method`
 
HTML forms only support `GET` and `POST` natively — there is no `method="DELETE"` or `method="PATCH"` in the HTML specification. To support these HTTP verbs from a browser form, a convention called **method spoofing** is used: the form submits as `POST` but includes a hidden field named `_method` that declares the intended verb.
 
In `public/index.php`:
 
```php
$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];
```
 
If `$_POST['_method']` exists, it overrides the actual HTTP method. Otherwise the real method from `$_SERVER['REQUEST_METHOD']` is used. This means a form like:
 
```html
<form method="POST" action="/note">
    <input type="hidden" name="_method" value="PATCH">
    ...
</form>
```
 
arrives at the server as a POST request, but `$method` is resolved to `PATCH` before being passed to `$router->route()`. The router then matches it against `$router->patch('/note', ...)` correctly.
 
The same pattern is used for DELETE:
 
```html
<form method="POST" action="/note">
    <input type="hidden" name="_method" value="DELETE">
    <input type="hidden" name="id" value="<?= $note['id'] ?>">
    <button>Delete</button>
</form>
```
 
---
 
## 4. Updated `routes.php`
 
Routes are now registered using the router's method-specific helpers instead of returning an array. The file is executed in the scope where `$router` is available:
 
```php
<?php
 
$router->get('/',       'controllers/index.php');
$router->get('/about',  'controllers/about.php');
$router->get('/contact','controllers/contact.php');
 
$router->get('/notes',         'controllers/notes/index.php');
$router->get('/note',          'controllers/notes/show.php');
$router->get('/notes/create',  'controllers/notes/create.php');
$router->post('/notes',        'controllers/notes/store.php');
 
$router->get('/note/edit',     'controllers/notes/edit.php');
$router->patch('/note',        'controllers/notes/update.php');
$router->delete('/note',       'controllers/notes/destroy.php');
```
 
Notice that `/note` now has three separate registrations — GET, PATCH, and DELETE — each pointing to a different controller. This was impossible with the old URI-only router.
 
---
 
## 5. The Service Container — `Core/Container.php`
 
```php
<?php
 
namespace Core;
 
use Exception;
 
class Container
{
    protected $bindings = [];
 
    public function bind($key, $resolver)
    {
        $this->bindings[$key] = $resolver;
    }
 
    public function resolve($key)
    {
        if (!array_key_exists($key, $this->bindings)) {
            throw new Exception("No matching binding found for {$key}");
        }
 
        $resolver = $this->bindings[$key];
 
        return call_user_func($resolver);
    }
}
```
 
A **service container** is a registry that maps a service identifier (a string key, typically the class name) to a factory function that knows how to build it. Rather than every controller that needs a `Database` object creating and configuring one from scratch, the container holds one centralised recipe.
 
- `bind($key, $resolver)` — registers a factory closure under a given key.
- `resolve($key)` — looks up the factory and calls it via `call_user_func()`, returning a fresh instance. If the key is not registered, it throws an `Exception` with a descriptive message rather than failing silently.
 
`call_user_func($resolver)` invokes the stored closure. This defers the actual instantiation until the moment `resolve()` is called — the container does not build anything until something asks for it.
 
---
 
## 6. The `App` Facade — `Core/App.php`
 
```php
<?php
 
namespace Core;
 
class App
{
    protected static $container;
 
    public static function setContainer($container)
    {
        static::$container = $container;
    }
 
    public static function container()
    {
        return static::$container;
    }
 
    public static function bind($key, $resolver)
    {
        static::container()->bind($key, $resolver);
    }
 
    public static function resolve($key)
    {
        return static::container()->resolve($key);
    }
}
```
 
`App` is a **facade** — a static wrapper that provides a globally accessible, simple interface to the container. `static::$container` stores the container instance as a class-level property, shared across all calls regardless of where in the application `App` is used.
 
Controllers never need to construct or configure a `Database` object themselves — they simply call:
 
```php
use Core\App;
use Core\Database;
 
$db = App::resolve(Database::class);
```
 
`Database::class` is a PHP magic constant that returns the fully qualified class name as a string — `'Core\Database'` — which is used as the lookup key in the container.
 
---
 
## 7. Bootstrapping the Container — `bootstrap.php`
 
```php
<?php
 
use Core\App;
use Core\Container;
use Core\Database;
 
$container = new Container();
 
$container->bind('Core\Database', function () {
    $config = require base_path('config.php');
    return new Database($config['database']);
});
 
App::setContainer($container);
```
 
`bootstrap.php` is required once at startup (from `public/index.php`) and wires the container together. The `Database` binding is registered with its fully qualified class name as the key. The factory closure captures no variables from its outer scope — instead it reads `config.php` fresh each time it is called, keeping the factory self-contained.
 
This is now the **only place** in the entire application where `Database` is configured. Every controller resolves it through `App::resolve(Database::class)`.
 
---
 
## 8. Notes CRUD Controllers
 
With the router and container in place, each CRUD operation gets a clean, focused controller.
 
### `controllers/notes/store.php` — Create
 
```php
<?php
 
use Core\App;
use Core\Database;
use Core\Validator;
 
$db = App::resolve(Database::class);
$errors = [];
 
if (!Validator::string($_POST['body'], 1, 1000)) {
    $errors['body'] = 'A body of no more than 1000 characters is required.';
}
 
if (!empty($errors)) {
    return view('notes/create.view.php', [
        'heading' => 'New Note',
        'errors'  => $errors,
    ]);
}
 
$db->query('INSERT INTO notes (body, user_id) VALUES (:body, :user_id)', [
    'body'    => $_POST['body'],
    'user_id' => $USER_ID,
]);
 
header('location: /notes');
exit();
```
 
Validation runs first. If errors are found, the create view is returned immediately with the error messages — the insert never runs. If validation passes, the note is inserted and the user is redirected to `/notes`. This **POST → validate → redirect** pattern prevents duplicate submissions on page refresh.
 
### `controllers/notes/destroy.php` — Delete
 
```php
<?php
 
use Core\App;
use Core\Database;
 
$db = App::resolve(Database::class);
 
$note = $db->query('SELECT * FROM notes WHERE id = :id', [
    'id' => $_POST['id']
])->findOrFail();
 
authorize($note['user_id'] === $USER_ID);
 
$db->query('DELETE FROM notes WHERE id = :id', [
    'id' => $_POST['id']
]);
 
header('location: /notes');
exit();
```
 
The note is fetched first — if it does not exist, `findOrFail()` aborts with 404. Ownership is then verified with `authorize()` before the delete query runs. The `id` comes from `$_POST['id']` (the hidden form field), not from `$_GET`, because the delete form submits via POST.
 
### `controllers/notes/edit.php` — Show Edit Form
 
```php
<?php
 
use Core\App;
use Core\Database;
 
$db = App::resolve(Database::class);
 
$note = $db->query('SELECT * FROM notes WHERE id = ?', [$_GET['id']])->findOrFail();
 
authorize($note['user_id'] === $USER_ID);
 
view('notes/edit.view.php', [
    'heading' => 'Edit Note',
    'errors'  => [],
    'note'    => $note,
]);
```
 
The note is fetched by `$_GET['id']` (from the URL query string) and passed to the edit view, where its current `body` is pre-populated into the textarea.
 
### `controllers/notes/update.php` — Save Edits
 
```php
<?php
 
use Core\App;
use Core\Database;
use Core\Validator;
 
$db = App::resolve(Database::class);
 
$note = $db->query('SELECT * FROM notes WHERE id = ?', [$_POST['id']])->findOrFail();
 
authorize($note['user_id'] === $USER_ID);
 
$errors = [];
 
if (!Validator::string($_POST['body'], 1, 1000)) {
    $errors['body'] = 'A body of no more than 1,000 characters is required.';
}
 
if (count($errors)) {
    return view('notes/edit.view.php', [
        'heading' => 'Edit Note',
        'errors'  => $errors,
        'note'    => $note,
    ]);
}
 
$db->query('UPDATE notes SET body = :body WHERE id = :id', [
    'id'   => $_POST['id'],
    'body' => $_POST['body'],
]);
 
header('location: /notes');
die();
```
 
The pattern mirrors `store.php`: fetch → authorize → validate → act → redirect. On a validation failure the edit view is returned with the errors and the original note data intact, so the user does not lose the form state.
 
---
 
## 9. The Edit View & Method Spoofing in Action
 
```php
<form method="POST" action="/note">
    <input type="hidden" name="_method" value="PATCH">
    <input type="hidden" name="id" value="<?= $note['id'] ?>">
 
    <textarea name="body"><?= $note['body'] ?></textarea>
 
    <?php if (isset($errors['body'])) : ?>
        <p class="text-red-500 text-xs mt-2"><?= $errors['body'] ?></p>
    <?php endif; ?>
 
    <button type="submit">Update</button>
</form>
```
 
The form posts to `/note` with `_method = PATCH`. `public/index.php` reads `$_POST['_method']` and passes `PATCH` to `$router->route()`, which matches `$router->patch('/note', 'controllers/notes/update.php')`. The hidden `id` field carries the note's identifier through the POST body, where `update.php` reads it from `$_POST['id']`.
 
---
## Milestone 8: Registration, Login, Logout & Route Middleware
 
> **Objective:** Implement a full authentication system using PHP sessions and a middleware on routes
---
 
## 1. PHP Sessions
 
A **session** is a server-side mechanism for persisting data across HTTP requests. Because HTTP is stateless — each request is independent — sessions give the server a way to remember who a user is between page loads.
 
When `session_start()` is called (placed at the very top of `public/index.php`), PHP either creates a new session or resumes an existing one identified by a session ID stored in a browser cookie (`PHPSESSID` by default). All data stored in `$_SESSION` is kept on the server, tied to that ID. The browser only ever holds the ID — never the actual data.
 
```
Browser                         Server
  │── GET /notes ──────────────────>│
  │   Cookie: PHPSESSID=abc123      │
  │                                 │── Look up session abc123
  │                                 │── $_SESSION['user'] = ['id' => 4, ...]
  │<── 200 OK ──────────────────────│
```
 
`$_SESSION` is a superglobal — available in every scope without being passed as a parameter.
 
---
 
## 2. `login()` and `logout()` — `Core/functions.php`
 
### `login()`
 
```php
function login($user)
{
    $_SESSION['user'] = [
        'email' => $user['email'],
        'id'    => (int) $user['id'],
    ];
 
    session_regenerate_id(true);
}
```
 
`login()` stores a minimal representation of the authenticated user in `$_SESSION`. Only the `email` and `id` are stored — not the password or any other sensitive data. The `id` is explicitly cast to `(int)` to guarantee it is always an integer, regardless of how the database driver returned it.
 
`session_regenerate_id(true)` is a critical security step. It replaces the current session ID with a freshly generated one immediately after login. 
 
### `logout()`
 
```php
function logout()
{
    $_SESSION = [];
    session_destroy();
 
    $params = session_get_cookie_params();
    setcookie('PHPSESSID', '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
```
 
Logging out requires three steps, each targeting a different layer:
 
1. **`$_SESSION = []`** — clears all data from the session array in memory for the current request.
2. **`session_destroy()`** — deletes the session file from the server so the session ID is no longer valid.
3. **`setcookie(..., time() - 3600, ...)`** — expires the `PHPSESSID` cookie in the browser by setting its expiry to one hour in the past.
 
`session_get_cookie_params()` retrieves the current session cookie configuration (path, domain, secure flag, httponly flag) so the `setcookie()` call uses identical parameters — ensuring the browser actually replaces and expires the existing cookie rather than creating a new unrelated one.
 
---
 
## 3. Password Hashing
 
Passwords must never be stored as plain text in the database. If the database is ever compromised, plain text passwords expose every user's credentials immediately — and since many people reuse passwords, the damage extends well beyond the application itself.
 
### Hashing on Registration
 
```php
$db->query('INSERT INTO users (email, password) VALUES (:email, :password)', [
    'email'    => $email,
    'password' => password_hash($password, PASSWORD_BCRYPT),
]);
```
 
`password_hash()` with `PASSWORD_BCRYPT` applies the bcrypt algorithm, which automatically generates a random salt and stretches the computation so that brute-forcing is slow. The returned string contains the algorithm, cost factor, salt, and hash all in one — everything needed to verify the password later is stored in that single string.
 
### Verifying on Login
 
```php
if (password_verify($password, $user['password'])) {
    login($user);
    header('location: /');
    exit();
}
```
 
`password_verify()` takes the plaintext password submitted by the user and the stored hash from the database. It extracts the salt and parameters from the hash and recomputes it, returning `true` if the result matches. The plaintext password is never stored or compared directly.
 
---
 
## 4. Registration — `controllers/registration/`
 
### `create.php` — Show the Form
 
```php
<?php
 
view('registration/create.view.php');
```
 
A minimal controller — its only job is to render the empty registration form.
 
### `store.php` — Handle Submission
 
```php
<?php
 
use Core\App;
use Core\Database;
use Core\Validator;
 
$db = App::resolve(Database::class);
 
$email    = $_POST['email'];
$password = $_POST['password'];
 
$errors = [];
 
if (!Validator::email($email)) {
    $errors['email'] = 'Please provide a valid email address.';
}
 
if (!Validator::string($password, 8, 255)) {
    $errors['password'] = 'Please provide a password of at least 8 characters.';
}
 
if (!empty($errors)) {
    return view('registration/create.view.php', ['errors' => $errors]);
}
 
$user = $db->query('SELECT * FROM users WHERE email = :email', [
    'email' => $email
])->find();
 
if ($user) {
    header('location: /');
    exit();
}
 
$db->query('INSERT INTO users (email, password) VALUES (:email, :password)', [
    'email'    => $email,
    'password' => password_hash($password, PASSWORD_BCRYPT),
]);
 
login([
    'email' => $email,
    'id'    => $db->lastInsertId(),
]);
 
header('location: /');
exit();
```
 
The controller follows a clear sequence:
 
1. Validate email format and password length.
2. If errors exist, return the form with messages — no database access.
3. Check whether the email is already registered — if so, redirect silently (no error is shown to avoid confirming whether an email exists, which is a minor privacy concern).
4. Hash the password and insert the new user.
5. Call `login()` with the new user's email and `lastInsertId()` — the auto-incremented ID assigned by the database — then redirect.
 
`$db->lastInsertId()` is a wrapped PDO method that returns the ID generated by the most recent INSERT, eliminating the need for a follow-up SELECT query.
 
---
 
## 5. Login & Logout — `controllers/session/`
 
### `create.php` — Show Login Form
 
```php
<?php
 
view('session/create.view.php');
```
 
### `store.php` — Verify Credentials
 
```php
<?php
 
use Core\App;
use Core\Database;
use Core\Validator;
 
$db = App::resolve(Database::class);
 
$email    = $_POST['email'];
$password = $_POST['password'];
 
$errors = [];
 
if (!Validator::email($email)) {
    $errors['email'] = 'Please provide a valid email address.';
}
 
if (!Validator::string($password)) {
    $errors['password'] = 'Please provide a valid password.';
}
 
if (!empty($errors)) {
    return view('session/create.view.php', ['errors' => $errors]);
}
 
$user = $db->query('SELECT * FROM users WHERE email = :email', [
    'email' => $email
])->find();
 
if ($user) {
    if (password_verify($password, $user['password'])) {
        login($user);
        header('location: /');
        exit();
    }
}
 
return view('session/create.view.php', [
    'errors' => [
        'email' => 'No matching account found for that email address and password.'
    ]
]);
```
 
An important security detail: whether the email was not found at all, or whether the password was wrong, the user sees the **same generic error message**. Distinguishing between the two would let an attacker enumerate which email addresses are registered in the system.
 
### `destroy.php` — Log Out
 
```php
<?php
 
logout();
 
header('location: /');
exit();
```
 
The logout controller calls the `logout()` helper and redirects. The logout form in the navigation uses method spoofing to send a DELETE request:
 
```html
<form method="POST" action="/session">
    <input type="hidden" name="_method" value="DELETE">
    <button>Log Out</button>
</form>
```
 
---
 
## 6. Middleware
 
### The Problem
 
Some routes should only be accessible to guests (unauthenticated users) — like the login and register pages. Others should only be accessible to authenticated users — like the notes pages. Without enforcement, a logged-in user could navigate back to `/login`, or an anonymous user could directly request `/notes`.
 
Middleware intercepts the request before the controller runs and redirects or aborts if the condition is not met.
 
### `Core/Middleware/Auth.php`
 
```php
<?php
 
namespace Core\Middleware;
 
class Auth
{
    public function handle()
    {
        if (!($_SESSION['user'] ?? false)) {
            header('location: /');
            exit();
        }
    }
}
```
 
If no user is in the session, redirect to the home page. The `?? false` null coalescing guard prevents an undefined index warning if `$_SESSION['user']` has never been set.
 
### `Core/Middleware/Guest.php`
 
```php
<?php
 
namespace Core\Middleware;
 
class Guest
{
    public function handle()
    {
        if ($_SESSION['user'] ?? false) {
            header('location: /');
            exit();
        }
    }
}
```
 
The inverse of `Auth` — if a user is already logged in and visits `/login` or `/register`, redirect them away.
 
### `Core/Middleware/Middleware.php`
 
```php
<?php
 
namespace Core\Middleware;
 
class Middleware
{
    public const MAP = [
        'guest' => Guest::class,
        'auth'  => Auth::class,
    ];
 
    public static function resolve($key)
    {
        if (!$key) {
            return;
        }
 
        $middleware = static::MAP[$key] ?? false;
 
        if (!$middleware) {
            throw new \Exception("No matching middleware found for key '{$key}'.");
        }
 
        (new $middleware)->handle();
    }
}
```
 
`Middleware` acts as a resolver — it maps string aliases (`'guest'`, `'auth'`) to their class names via the `MAP` constant, then instantiates and calls `handle()`. Using string aliases in routes keeps `routes.php` readable and decouples it from class names. Adding a new middleware requires only adding an entry to `MAP`.
 
`(new $middleware)->handle()` instantiates the class stored in `$middleware` (a fully qualified class name string) and immediately calls `handle()` on it — a concise way to construct and invoke without storing the instance.
 
---
 
## 7. `only()` on the Router
 
```php
public function only($key)
{
    $this->routes[array_key_last($this->routes)]['middleware'] = $key;
 
    return $this;
}
```
 
`only()` attaches a middleware key to the **most recently registered route**. `array_key_last()` retrieves the last key in the `$routes` array, so `only()` can be chained immediately after any route registration without needing to reference the route explicitly.
 
 
```php
$router->get('/notes', 'controllers/notes/index.php')->only('auth');
```
 
In the `route()` method, the middleware is resolved before the controller is loaded:
 
```php
public function route($uri, $method)
{
    foreach ($this->routes as $route) {
        if ($route['uri'] === $uri && $route['method'] === strtoupper($method)) {
            Middleware::resolve($route['middleware'] ?? null);
            return require base_path($route['controller']);
        }
    }
 
    $this->abort();
}
```
 
`$route['middleware'] ?? null` passes `null` if no middleware was attached, and `Middleware::resolve(null)` returns immediately — routes without middleware are unaffected.
 
### Routes with Middleware
 
```php
$router->get('/register',  'controllers/registration/create.php')->only('guest');
$router->post('/register', 'controllers/registration/store.php')->only('guest');
 
$router->get('/login',     'controllers/session/create.php')->only('guest');
$router->post('/session',  'controllers/session/store.php')->only('guest');
$router->delete('/session','controllers/session/destroy.php')->only('auth');
 
$router->get('/notes',         'controllers/notes/index.php')->only('auth');
$router->get('/notes/create',  'controllers/notes/create.php')->only('auth');
$router->post('/notes',        'controllers/notes/store.php')->only('auth');
$router->get('/note',          'controllers/notes/show.php')->only('auth');
$router->get('/note/edit',     'controllers/notes/edit.php')->only('auth');
$router->patch('/note',        'controllers/notes/update.php')->only('auth');
$router->delete('/note',       'controllers/notes/destroy.php')->only('auth');
```
 
---
 
## 8. Dynamic Navigation — `views/partials/nav.php`
 
The navigation bar now reflects the session state. `$_SESSION['user'] ?? false` is used as the condition throughout — evaluating to the user array if logged in, or `false` if not.
 
```php
<?php if ($_SESSION['user'] ?? false) : ?>
    <a href='/notes' class="...">Notes</a>
<?php endif ?>
```
 
The Notes link is only rendered for authenticated users — guests have no reason to see it and would be redirected by middleware anyway.
 
```php
<?php if ($_SESSION['user'] ?? false) : ?>
    <form method="POST" action="/session">
        <input type="hidden" name="_method" value="DELETE">
        <button class="...">Log Out</button>
    </form>
<?php else : ?>
    <a href="/register" class="...">Register</a>
    <a href="/login" class="...">Log In</a>
<?php endif ?>
```
 
Authenticated users see their avatar and a Log Out button. Guests see Register and Log In links. The active-state styling from `urlIs()` is applied to the Register and Log In links just as it is for the main navigation links.
 
---