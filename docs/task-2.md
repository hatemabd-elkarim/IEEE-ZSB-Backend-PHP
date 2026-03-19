## Milestone 4: New feature, Authorization & Refactors
 
> **Objective:** Add pages for viewing and creating notes
 
---
 
## 1. Extracting Routes — `routes.php`
 
As the application grows, keeping the routes array inside `router.php` becomes cluttered. It is extracted into its own file that simply returns the array, mirroring the pattern used by `config.php`:
 
```php
<?php
 
return [
    '/'             => 'controllers/index.php',
    '/about'        => 'controllers/about.php',
    '/contact'      => 'controllers/contact.php',
    '/notes'        => 'controllers/notes.php',
    '/note'         => 'controllers/note.php',
    '/note/create'  => 'controllers/note-create.php',
];
```
 
`router.php` now loads this with `$routes = require 'routes.php'` and passes it to `routeToController()` as before. The routing logic does not change — only the separation of concerns improves.
 
---
 
## 2. New Controllers & Views
 
Three new controllers are added, each following the same pattern established in previous milestones:
 
| Controller | View | Purpose |
|---|---|---|
| `controllers/notes.php` | `views/notes.view.php` | Lists all notes belonging to the current user |
| `controllers/note.php` | `views/note.view.php` | Displays a single note by ID |
| `controllers/note-create.php` | `views/note-create.view.php` | Form to create a new note |
 
### `controllers/notes.php`
 
```php
<?php
 
$config = require 'config.php';
$db = new Database($config['database']);
 
$heading = 'My Notes';
 
$notes = $db->query('SELECT * FROM notes WHERE user_id = ?', [$USER_ID])->findAll();
 
require 'views/notes.view.php';
```
 
`$USER_ID` is injected by `routeToController()` (see section 3 below) and acts as a stand-in for a real authenticated user ID. Every query is scoped to that user — the `WHERE user_id = ?` clause ensures a user can only ever retrieve their own rows.
 
---
 
## 3. Hardcoded User & `routeToController()` Update
 
Since authentication is not yet implemented, the current user is represented by a hardcoded default parameter:
 
```php
function routeToController($uri, $routes, $USER_ID = 1) {
    if (array_key_exists($uri, $routes))
        require $routes[$uri];
    else
        abort();
}
```
 
`$USER_ID = 1` is a default argument — if no user ID is passed, it falls back to `1`. Because `require` shares scope, `$USER_ID` becomes available inside every controller file that is loaded. This is a deliberate simplification: the variable name and mechanism are already in place for when a real session-based user ID replaces the hardcoded value.
 
---
 
## 4. HTTP Status Constants — `Response.php`
 
```php
<?php
 
class Response {
    const NOT_FOUND = 404;
    const FORBIDDEN = 403;
}
```
 
Rather than scattering magic numbers like `403` and `404` throughout the codebase, a `Response` class defines them as named constants. `const` values on a class are accessed statically via `Response::NOT_FOUND` and `Response::FORBIDDEN`, making call sites self-documenting and ensuring that changing a status code requires editing only one place.
 
---
 
## 5. The `authorize()` Helper — `functions.php`
 
```php
function authorize($condition, $status = Response::FORBIDDEN)
{
    if (!$condition) {
        abort($status);
    }
}
```
 
`authorize()` takes a boolean condition and aborts with an HTTP error if it evaluates to `false`. The default status is `Response::FORBIDDEN` (403), but any status code can be passed.
 
This is used in `controllers/note.php` to verify that the note being requested belongs to the current user:
 
```php
$NOTE_ID = $_GET['id'];
 
$note = $db->query('SELECT * FROM notes WHERE id = ?', [$NOTE_ID])->findOrFail();
 
authorize($note['user_id'] === $USER_ID);
```
 
The flow is:
 
1. Fetch the note by its ID — if no row is found, `findOrFail()` aborts with 404.
2. Compare the note's `user_id` column against `$USER_ID` — if they do not match, `authorize()` aborts with 403.
3. Only if both checks pass does execution continue to `require 'views/note.view.php'`.
 
This two-step guard — existence check then ownership check — is a fundamental pattern in any application that manages user-owned resources.
 
### The 403 View — `views/403.php`
 
```php
<?php require('partials/header.php'); ?>
<?php require('partials/nav.php'); ?>
 
<main>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold">You are not authorized to view this page.</h1>
        <p class="mt-4">
            <a href='/' class="text-blue underline">Go Back Home</a>
        </p>
    </div>
</main>
 
<?php require('partials/footer.php'); ?>
```
 
Because `abort()` dynamically resolves the view path from the status code (`"views/{$code}.php"`), adding a new error page requires only dropping a new view file into `views/` — no changes to `abort()` or the `Response` class are needed.
 
---
 
## 6. Database Class Refactor
 
The `Database` class is refactored to store the prepared statement as an instance property and return `$this` from `query()`, enabling a **fluent interface** — a style where methods are chained one after another on the same object.
 
```php
<?php
 
class Database
{
    public $connection;
    public $statement;
 
    public function __construct($config, $username = 'root', $password = '')
    {
        $dsn = 'mysql:' . http_build_query($config, '', ';');
 
        $this->connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
 
    public function query($query, $params = [])
    {
        $this->statement = $this->connection->prepare($query);
        $this->statement->execute($params);
 
        return $this;
    }
 
    public function findAll()
    {
        return $this->statement->fetchAll();
    }
 
    public function find()
    {
        return $this->statement->fetch();
    }
 
    public function findOrFail()
    {
        $result = $this->find();
 
        if (!$result) {
            abort();
        }
 
        return $result;
    }
}
```
 
### What Changed and Why
 
**Before the refactor**, `query()` returned the raw `PDOStatement`:
 
```php
// Old usage — caller must know about PDOStatement
$notes = $db->query('SELECT * FROM notes WHERE user_id = ?', [$USER_ID])->fetchAll();
```
 
**After the refactor**, `query()` returns `$this` (the `Database` instance), and the fetch operation is expressed through a named method:
 
```php
$notes = $db->query('SELECT * FROM notes WHERE user_id = ?', [$USER_ID])->findAll();
$note  = $db->query('SELECT * FROM notes WHERE id = ?', [$NOTE_ID])->findOrFail();
```
 
This improves the code in two ways. First, callers no longer need to know that the underlying result is a `PDOStatement` — they interact only with the `Database` class's own vocabulary. Second, `findOrFail()` bundles the fetch and the 404 abort into a single expressive call, eliminating the need for a manual `if (!$result) abort()` block in every controller.
 
| Method | Behaviour |
|---|---|
| `findAll()` | Returns all matching rows as an array of associative arrays |
| `find()` | Returns the first matching row, or `false` if none |
| `findOrFail()` | Returns the first matching row, or aborts with 404 if none |
 
---
 
## Milestone 5: Form Handling & Validation
 
> **Objective:** Implement the note creation form with server-side validation.
 
---
 
## 1. The `Validator` Class — `Validator.php`
 
```php
<?php
 
class Validator
{
    public static function string($value, $min = 1, $max = INF)
    {
        $value = trim($value);
 
        return strlen($value) >= $min && strlen($value) <= $max;
    }
 
    public static function email($value)
    {
        $value = trim($value);
 
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
```
 
`Validator` groups input validation rules as `static` methods. Static methods belong to the class itself rather than to any instance — they are called as `Validator::string(...)` without needing `new Validator()`. This makes sense here because validation rules are stateless: they take a value, apply a rule, and return a result without needing to remember anything between calls.
 
### `string()` Validation
 
```php
public static function string($value, $min = 1, $max = INF)
{
    $value = trim($value);
    return strlen($value) >= $min && strlen($value) <= $max;
}
```
 
- `trim()` removes leading and trailing whitespace before measuring length, preventing a user from submitting spaces only and satisfying a minimum length check.
- `$max = INF` means the upper bound defaults to unlimited — only a `$min` is needed for a basic "required field" check.
- Returns `true` if the cleaned value's length falls within the specified bounds, `false` otherwise.
 
### `email()` Validation
 
```php
public static function email($value)
{
    $value = trim($value);
    return filter_var($value, FILTER_VALIDATE_EMAIL);
}
```
 
`filter_var()` with `FILTER_VALIDATE_EMAIL` is PHP's built-in email format validator. It returns the sanitised value on success or `false` on failure, which works naturally as a boolean in a conditional.
 
---
 
## 2. The Note Creation Controller — `note-create.php`
 
```php
<?php
 
require 'Validator.php';
 
$config = require('config.php');
$db = new Database($config['database']);
 
$heading = 'New Note';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
 
    if (!Validator::string($_POST['body'], 1, 1000)) {
        $errors['body'] = 'A body of no more than 1000 characters is required.';
    }
 
    if (empty($errors)) {
        $db->query('INSERT INTO notes (body, user_id) VALUES (:body, :user_id)', [
            'body'    => $_POST['body'],
            'user_id' => $USER_ID,
        ]);
    }
}
 
require 'views/note-create.view.php';
```
 
### Request Method Detection
 
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST')
```
 
A single controller file handles both the initial page load (GET) and the form submission (POST). On a GET request the `if` block is skipped entirely and the empty form is rendered. On a POST request, submitted data is validated and either saved or returned with errors. This pattern — one URL, two behaviours depending on method — keeps related logic in one place.
 
### The `$errors` Array
 
`$errors` is an associative array keyed by field name. If validation fails for a field, its error message is stored under the corresponding key (`$errors['body']`). The view checks for each key and renders the message beneath the relevant input. If `$errors` remains empty after all checks, the data is safe to insert.
 
### Named Placeholders in the INSERT Query
 
```php
$db->query('INSERT INTO notes (body, user_id) VALUES (:body, :user_id)', [
    'body'    => $_POST['body'],
    'user_id' => $USER_ID,
]);
```
 
This INSERT uses **named placeholders** (`:body`, `:user_id`) rather than positional `?` placeholders. Named placeholders are matched to the `$params` array by key name, making the mapping explicit and the query easier to read — particularly when a query has many parameters and positional order would be easy to get wrong.
 
---
 
## 3. The Form View — `views/note-create.view.php`
 
```php
<textarea name="body" rows="3"><?= $_POST['body'] ?? '' ?></textarea>
 
<?php if (isset($errors['body'])) : ?>
    <p class="text-red-500 text-xs mt-2"><?= $errors['body'] ?></p>
<?php endif; ?>
```
 
Two small but important details:
 
**Form repopulation:** `$_POST['body'] ?? ''` uses the null coalescing operator to output the previously submitted value inside the textarea after a failed submission. On a fresh GET request `$_POST['body']` does not exist, so `''` is output instead — preventing an undefined variable notice and keeping the form empty on first load.
 
**Conditional error display:** `isset($errors['body'])` checks whether a message exists for that field before rendering it. On a GET request `$errors` is not defined at all, so `isset()` correctly returns `false` without throwing an undefined variable warning.
 
---