# IEEE ZSB Backend Phase 2 PHP — Project Documentation

---

## Milestone 0: PHP Fundamentals

> **Objective:** Establish a solid foundation in PHP syntax before applying it to the project structure. This milestone covers variables, output methods, strings, booleans, conditionals, arrays, loops, and functions — all demonstrated through practical examples.

---

## 1. Variables & Output

In PHP, variables are declared with a `$` prefix. Output can be produced using `echo` or `print`. Use camelCase for naming varibales.

**Key difference:**

- `echo` can accept multiple comma-separated values and has no return value — it is slightly faster.
- `print` accepts only one argument and always returns `1`, making it usable inside expressions.

```php
<?php
$greeting = "Hello, World!";

// Using echo
echo $greeting;

// Using print
print $greeting;

// echo with multiple values
echo "Hello", ", ", "World!";

// Shorthand output tag — equivalent to <?php echo $greeting; ?>
?>

<?= $greeting ?>
```

> The `<?= $message ?>` shorthand is a convenient way to embed PHP output directly in HTML templates without writing the full `echo` statement.

---

## 2. String Concatenation

PHP uses the dot (`.`) operator to concatenate strings.

```php
<?php
$name    = "Ahmed";
$greeting = "Hello, " . $name . "!";

echo $greeting; // Hello, Ahmed!
?>
```

---

## 3. Booleans & Conditionals

PHP supports standard boolean values (`true` / `false`) and the usual conditional structures.

```php
<?php
$isLoggedIn = true;

if ($isLoggedIn) {
    echo "Welcome back!";
} else {
    echo "Please log in.";
}

// Ternary shorthand
$greeting = $isLoggedIn ? "Welcome back!" : "Please log in.";
echo $greeting;
?>
```

---

## 4. Arrays

### 4.1 Indexed Arrays

Indexed arrays store ordered lists of values, accessed by a numeric index starting at `0`.

```php
<?php
// A list of books as an indexed array
$books = [
    ["Clean Code", 2008, "Robert C. Martin"],
    ["The Pragmatic Programmer", 1999, "Andrew Hunt"],
    ["Laravel: Up & Running", 2019, "Matt Stauffer"],
];

// Accessing a book
echo $books[0][0]; // Clean Code
echo $books[0][1]; // 2008
echo $books[0][2]; // Robert C. Martin
?>
```

### 4.2 Associative Arrays

Associative arrays, or maps in another languages, use named string keys instead of numeric indexes.

```php
<?php
// Books as associative arrays
$books = [
    [
        "title"        => "Clean Code",
        "releaseYear"  => 2008,
        "author"       => "Robert C. Martin",
    ],
    [
        "title"        => "The Pragmatic Programmer",
        "releaseYear"  => 1999,
        "author"       => "Andrew Hunt",
    ],
    [
        "title"        => "Laravel: Up & Running",
        "releaseYear"  => 2019,
        "author"       => "Matt Stauffer",
    ],
];

// Accessing a field by key
echo $books[0]["title"];  // Clean Code
echo $books[1]["author"]; // Andrew Hunt
?>
```

---

## 5. Loops

### 5.1 `foreach` Loop

`foreach` is the standard way to iterate over arrays in PHP.

```php
<?php
foreach ($books as $book) {
    echo $book["title"] . " by " . $book["author"] . " (" . $book["releaseYear"] . ")";
    echo "\n";
}
?>
```

**Output:**

```
Clean Code by Robert C. Martin (2008)
The Pragmatic Programmer by Andrew Hunt (1999)
Laravel: Up & Running by Matt Stauffer (2019)
```

### 5.2 `foreach` Shorthand in HTML Templates

The `<?= ?>` shorthand can be combined with the alternative `foreach` syntax to produce clean, readable HTML templates.

```php
<?php foreach ($books as $book) : ?>
    <li><?= $book["title"] ?> — <?= $book["author"] ?></li>
<?php endforeach; ?>
```

> This syntax separates PHP logic from HTML markup clearly, which is especially useful when working with view templates.

---

## 6. Functions

### 6.1 Named Function

A named function is declared with the `function` keyword and can be called by name anywhere in the scope.

```php
<?php
// Filter books by a specific author
function filterByAuthor(array $books, string $author): array
{
    $result = [];

    foreach ($books as $book) {
        if ($book["author"] === $author) {
            $result[] = $book;
        }
    }

    return $result;
}

$martinBooks = filterByAuthor($books, "Robert C. Martin");
echo $martinBooks[0]["title"]; // Clean Code
?>
```

### 6.2 Anonymous Function

An anonymous function has no name and is assigned to a variable. It is useful for one-off or inline logic.

```php
<?php
// An anonymous function that checks if a book was released after a given year
$filterByYear = function (array $books, int $year): array {
    $result = [];

    foreach ($books as $book) {
        if ($book["releaseYear"] > $year) {
            $result[] = $book;
        }
    }

    return $result;
};

$recentBooks = $filterByYear($books, 2000);
?>
```

### 6.3 Generic Filter with `array_filter` and Lambda Functions

`array_filter` accepts an array and a **callback function**. It returns only the elements for which the callback returns `true`. This allows building a single, flexible `filter` utility that works with any condition.

```php
<?php
// Generic filter function using array_filter
$filteredBooks = array_filter($books, function($book) {
    return $book['releaseYear'] > 2000;
});

// Print results
foreach ($releasedBooks as $book) {
    echo $book["title"] . "\n";
}
?>
```

---

## Milestone 1: Project Structure — Views, Partials & Routing Helpers

> **Objective:** Introduce a clean file structure that separates page logic from HTML templates.

---

## 1. The Problem This Solves

Without structure, every page would contain a full copy of the HTML boilerplate — the `<head>`, navigation, footer, and banner — mixed together with the page's own content and logic. Any change to the navbar would require editing every single file. T

1. **Logic vs. template** — each page has a logic file (`index.php`) and a view file (`index.view.php`).
2. **Shared vs. unique** — repeated HTML fragments are extracted into `partials/` and included where needed.

---

## 2. Project File Structure (Initial)

```
project/
│
├── index.php           ← Page logic: sets $heading, requires view
├── about.php
├── contact.php
│
├── functions.php       ← Shared helper functions (e.g. urlIs)
│
└── views/
    ├── index.view.php  ← Page template: assembles partials + unique content
    ├── about.view.php
    ├── contact.view.php
    │
    └── partials/
        ├── header.php  ← <head>, meta tags, CSS links
        ├── nav.php     ← Navigation bar
        ├── banner.php  ← Page heading banner
        └── footer.php  ← Closing scripts, </body>, </html>
```

---

## 3. Page Logic File — `index.php`

Each page's logic file has one responsibility: prepare any data the view needs, then hand off to the view using `require`.

```php
<?php

$heading = 'Home';

require 'views/index.view.php';
```

- `$heading` is declared here and becomes available inside the required view file, because `require` includes the file in the **same variable scope**.
- The same pattern is repeated for `about.php` (`$heading = 'About'`) and `contact.php` (`$heading = 'Contact'`), keeping each logic file minimal.

> `require` vs `include`: both insert a file's contents at the call site, but `require` throws a fatal error if the file is missing, while `include` only emits a warning. For essential files like views and partials, `require` is the correct choice.

---

## 4. View File — `views/index.view.php`

The view file is a pure template. It assembles the page by including partials and adding only the content unique to that page.

```php
<?php require('partials/header.php'); ?>
<?php require('partials/nav.php'); ?>
<?php require('partials/banner.php'); ?>

<main>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <p>Welcome to Homepage</p>
    </div>
</main>

<?php require('partials/footer.php'); ?>
```

- The view contains no business logic — it only assembles structure.
- The `$heading` variable set in `index.php` flows through naturally, since `require` shares scope. The `banner.php` partial uses it directly.

---

## 5. Partials

### 5.1 `banner.php` — Page Heading

The banner partial uses `$heading` to display the correct title on every page without duplication.

```php
<header class="relative bg-white shadow-sm">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">
            <?= $heading ?>
        </h1>
    </div>
</header>
```

Since `$heading` is set in the logic file before the view is required, it is in scope when `banner.php` is included. There is no need to pass it explicitly — PHP's `require` shares the calling scope.

### 5.2 `nav.php` — Active State with `urlIs()`

The navigation bar uses a helper function `urlIs()` to apply different CSS classes to the currently active link, giving users a visual indicator of which page they are on.

```php
<a href='/'
    class="rounded-md px-3 py-2 text-sm font-medium
        <?= urlIs('/') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
    Home
</a>

<a href='/about'
    class="rounded-md px-3 py-2 text-sm font-medium
        <?= urlIs('/about') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
    About
</a>

<a href='/contact'
    class="rounded-md px-3 py-2 text-sm font-medium
        <?= urlIs('/contact') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white' ?>">
    Contact
</a>
```

The ternary expression inside `<?= ?>` outputs one of two Tailwind class strings depending on whether the current URL matches the link's path.

---

## 6. The `$_SERVER` Superglobal & `urlIs()`

### 6.1 What is `$_SERVER`?

`$_SERVER` is a **superglobal array** — a built-in PHP variable that is automatically available in every scope (global, function, class) without needing to be declared or passed as a parameter. PHP populates it with information provided by the web server about the current HTTP request.

Relevant keys for routing:

| Key                          | Description                                   | Example value    |
| ---------------------------- | --------------------------------------------- | ---------------- |
| `$_SERVER['REQUEST_URI']`    | The full path and query string of the request | `/about?ref=nav` |
| `$_SERVER['PHP_SELF']`       | The path to the currently executing script    | `/index.php`     |
| `$_SERVER['HTTP_HOST']`      | The domain name from the request              | `localhost`      |
| `$_SERVER['REQUEST_METHOD']` | The HTTP method used                          | `GET`, `POST`    |

For determining the active nav link, `REQUEST_URI` is the most appropriate key — it reflects exactly what the user typed or navigated to in the browser.

### 6.2 `urlIs()` Implementation — `functions.php`

```php
<?php

function urlIs(string $value): bool
{
    return $_SERVER['REQUEST_URI'] === $value;
}
```

- The function compares the requested URI (e.g. `/about`) against the expected value passed as an argument.
- It returns `true` if they match, `false` otherwise.
- This boolean is used directly in the ternary expressions inside `nav.php`.

### 6.3 Important Consideration: Query Strings

`$_SERVER['REQUEST_URI']` includes the **full URI including any query string**. This means a request to `/about?ref=email` would **not** match `urlIs('/about')` — the comparison would fail.

For the current project this is not an issue since the pages use clean URLs with no query parameters. However, for a more robust implementation, `parse_url()` can be used to extract only the path component:

```php
function urlIs(string $value): bool
{
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === $value;
}
```

---

## 7. How the Pieces Connect

```
Browser requests /about
        │
        ▼
about.php
  └─ sets $heading = 'About'
  └─ require 'views/about.view.php'
            │
            ├─ require 'partials/header.php'
            ├─ require 'partials/nav.php'   ← urlIs('/about') returns true → active class applied
            ├─ require 'partials/banner.php' ← outputs $heading = 'About'
            ├─ <main> ... unique content ... </main>
            └─ require 'partials/footer.php'
```

---

## Milestone 2: Building a Router

> **Objective:** Replace direct file access with a centralized routing system.

---

## 1. The Problem This Solves

In the previous milestone, pages were accessed by navigating directly to their PHP files — `/about.php`, `/contact.php`, etc. This approach has several drawbacks:

- The file structure is exposed to the browser.
- There is no single place to intercept or validate requests.
- Unmatched URLs produce raw server errors with no control over the response.

A router solves this by funneling **every request through one file**, which then decides what to load.

---

## 2. Project File Structure (Updated)

```
project/
│
├── index.php           ← Single entry point — requires router.php
├── router.php          ← Maps URIs to controllers, handles 404
├── functions.php
│
├── controllers/        ← Moved from root: page logic files
│   ├── index.php
│   ├── about.php
│   └── contact.php
│
└── views/
    ├── index.view.php
    ├── about.view.php
    ├── contact.view.php
    ├── 404.php         ← Error page for unmatched routes
    │
    └── partials/
        ├── header.php
        ├── nav.php
        ├── banner.php
        └── footer.php
```

---

## 3. Entry Point — Root `index.php`

The root `index.php` now has a single responsibility: boot the application by loading the router, load any functions used inside the logic controllers.

```php
<?php
require 'functions.php';

require 'router.php';
```

Every HTTP request the web server receives is directed here first. The router then takes over.

---

## 4. The Router — `router.php`

```php
<?php

$uri = parse_url($_SERVER['REQUEST_URI'])['path'];

$routes = [
    '/'        => 'controllers/index.php',
    '/about'   => 'controllers/about.php',
    '/contact' => 'controllers/contact.php',
];

function routeToController($uri, $routes) {
    if (array_key_exists($uri, $routes))
        require $routes[$uri];
    else
        abort();
}

function abort($code = 404) {
    http_response_code($code);

    require "views/{$code}.php";

    die();
}

routeToController($uri, $routes);
```

### 4.1 Extracting the Clean Path with `parse_url()`

```php
$uri = parse_url($_SERVER['REQUEST_URI'])['path'];
```

As noted in Milestone 1, `$_SERVER['REQUEST_URI']` includes the query string (e.g. `/about?ref=email`). `parse_url()` parses a URL string into its components and returns them as an associative array. Extracting only the `'path'` key discards the query string, giving a clean URI like `/about` regardless of what parameters were appended.

| `parse_url()` key | Value for `/about?ref=email` |
| ----------------- | ---------------------------- |
| `'path'`          | `/about`                     |
| `'query'`         | `ref=email`                  |

This also fixes the edge case identified in `urlIs()` from Milestone 1 — the router now uses the same clean path extraction.

### 4.2 The Routes Table

```php
$routes = [
    '/'        => 'controllers/index.php',
    '/about'   => 'controllers/about.php',
    '/contact' => 'controllers/contact.php',
];
```

`$routes` is a simple associative array mapping URI paths to controller file paths. Adding a new page to the application requires only adding one line here and creating the corresponding controller file — no other file needs to change.

### 4.3 `routeToController()` — Dispatching the Request

```php
function routeToController($uri, $routes) {
    if (array_key_exists($uri, $routes))
        require $routes[$uri];
    else
        abort();
}
```

`array_key_exists()` checks whether the incoming URI has a registered route. If it does, the corresponding controller file is required and executed. If it does not, `abort()` is called with the default code of `404`.

### 4.4 `abort()` — Controlled Error Responses

```php
function abort($code = 404) {
    http_response_code($code);

    require "views/{$code}.php";

    die();
}
```

Three things happen in sequence:

1. **`http_response_code($code)`** — Sets the HTTP status code of the response. This is critical: without it, even an error page would be sent to the browser with a `200 OK` status, which is semantically incorrect and harmful for search engines and API clients.

2. **`require "views/{$code}.php"`** — Loads the appropriate error view. The double-quoted string `"views/{$code}.php"` is used intentionally — double quotes in PHP interpret variables inside `{}`, so if `$code` is `404`, this resolves to `"views/404.php"`. A single-quoted string like `'views/{$code}.php'` would treat `{$code}` as a **literal string** and fail to find the file.

3. **`die()`** — Halts execution immediately after the error page is rendered, preventing any further code from running.

> The `abort()` function's `$code = 404` parameter uses a **default argument value**. If called as `abort()` it sends a 404; if called as `abort(500)` it would send a 500 Internal Server Error — making the function reusable for any HTTP error code, provided the corresponding view file exists.

---

## 5. How a Request Flows Through the Router

```
Browser requests /about
        │
        ▼
index.php (entry point)
  └─ require 'router.php'
            │
            ├─ parse_url($_SERVER['REQUEST_URI'])['path']  →  '/about'
            │
            ├─ array_key_exists('/about', $routes)  →  true
            │
            └─ require 'controllers/about.php'
                        │
                        ├─ $heading = 'About'
                        └─ require 'views/about.view.php'
                                    └─ (assembles partials + content)


Browser requests /unknown
        │
        ▼
index.php → router.php
  └─ array_key_exists('/unknown', $routes)  →  false
  └─ abort()
        ├─ http_response_code(404)
        ├─ require 'views/404.php'
        └─ die()
```

---

## Milestone 3: Database Connection with PDO

> **Objective:** Introduce a dedicated `Database` class that encapsulates the PDO connection and query execution. Configuration is separated into its own file, the connection string is built dynamically, and prepared statements are used to prevent SQL injection.

---

## 1. Project file structure (Updated)

```
project/
│
├── index.php       ← Now requires Database.php and config.php
├── router.php
├── functions.php
├── Database.php    ← NEW: PDO wrapper class
├── config.php      ← NEW: Application configuration
│
├── controllers/
└── views/
```

---

## 2. PDO

**PDO (PHP Data Objects)** is a database abstraction layer built into PHP. Rather than using database-specific functions, PDO provides a single, consistent API that works with MySQL, PostgreSQL, SQLite, and others — swapping the database engine requires changing only the connection string, not the query code.

Key characteristics:

| Feature             | Description                                            |
| ------------------- | ------------------------------------------------------ |
| Database-agnostic   | One API for MySQL, PostgreSQL, SQLite, and more        |
| Prepared statements | Built-in protection against SQL injection              |
| Fetch modes         | Control how results are returned (array, object, etc.) |
| Error handling      | Configurable exception-based error reporting           |

---

## 3. Configuration File — `config.php`

```php
<?php

return [
    'database' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'dbname'  => 'myapp',
        'charset' => 'utf8mb4',
    ]

    // Space available for other service configurations
];
```

`config.php` uses `return` to expose its data as a plain PHP array. This means it does not produce any output and does not define any global variables — it simply hands its value back to whoever `require`d it:

```php
$config = require 'config.php';
// $config is now the full array
// $config['database'] is the database-specific slice
```

Centralising configuration here means that changing the database name, host, or charset requires editing only this one file, regardless of how many places in the application use the database.

---

## 4. The `Database` Class — `Database.php`

```php
<?php

class Database
{
    public $connection;

    public function __construct($config, $username = 'root', $password = '')
    {
        $dsn = 'mysql:' . http_build_query($config, '', ';');

        $this->connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    public function query($query, $params = [])
    {
        $statement = $this->connection->prepare($query);

        $statement->execute($params);

        return $statement;
    }
}
```

### 4.1 Building the DSN Dynamically with `http_build_query()`

A **DSN (Data Source Name)** is the connection string PDO uses to locate and connect to the database. For MySQL it follows this format:

```
mysql:host=localhost;port=3306;dbname=myapp;charset=utf8mb4
```

Rather than hardcoding this string, `http_build_query()` is used to build it dynamically from the config array:

```php
$dsn = 'mysql:' . http_build_query($config, '', ';');
```

This approach means adding a new DSN parameter or editing it(e.g. `'unix_socket'`) requires only adding it to `config.php` — the `Database` class does not need to change.

### 4.2 PDO Constructor Options

```php
$this->connection = new PDO($dsn, $username, $password, [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
```

The fourth argument to `new PDO()` is an options array. `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` sets every query's default fetch mode to return rows as **associative arrays** (keyed by column name) rather than the default which returns both numeric and associative indexes, doubling the memory usage for no benefit.

### 4.3 The `query()` Method & Prepared Statements

```php
public function query($query, $params = [])
{
    $statement = $this->connection->prepare($query);

    $statement->execute($params);

    return $statement;
}
```

Rather than executing a query string directly, `query()` uses a **two-step prepare → execute** pattern:

1. **`prepare($query)`** — Sends the query template to the database server. The server parses and compiles it once. Placeholders (`?`) mark where values will be inserted.
2. **`execute($params)`** — Sends the actual values separately. The database server substitutes them into the already-compiled query.

The method returns the `PDOStatement` object, allowing the caller to choose how to fetch results: `->fetchAll()` for all rows, `->fetch()` for one row

---

## 5. SQL Injection

### 5.1 The Attack

SQL injection occurs when user-supplied input is embedded directly into a SQL query string. Consider this naive approach:

```php
// DANGEROUS — never do this
$id = $_GET['id'];
$posts = $db->connection->query("SELECT * FROM posts WHERE id = $id");
```

If a user visits `/posts?id=1`, the query becomes:

```sql
SELECT * FROM posts WHERE id = 1
```

But if they visit `/posts?id=1 OR 1=1`, it becomes:

```sql
SELECT * FROM posts WHERE id = 1 OR 1=1
```

This returns every row in the table. A more destructive payload like `1; DROP TABLE posts--` could delete data entirely. Because the input was concatenated directly into the query string, the database cannot distinguish between the intended SQL and the injected SQL — it sees one combined string.

### 5.2 How Prepared Statements Prevent It

```php
// SAFE — using prepared statements
$id = $_GET['id'];
$posts = $db->query('SELECT * FROM posts WHERE id = ?', [$id])->fetchAll();
```

The `?` placeholder is never replaced by string concatenation. Instead, the query template and the values travel to the database server **separately**. The server has already parsed the query structure before the value arrives — it knows `?` is a data value, not executable SQL. No matter what the user submits, it will always be treated as a plain value and cannot alter the query's structure.

> The rule is simple: **user input must never be concatenated into a SQL string**. Always use `?` placeholders and pass values through `execute()`.

---

## 6. Wiring It Together — Root `index.php`

```php
<?php

require 'functions.php';
require 'Database.php';
require 'router.php';

$config = require 'config.php';

$db = new Database($config['database']);
```

The load order matters here:

1. `functions.php` — helper functions needed throughout the application.
2. `Database.php` — the class definition must be loaded before it can be instantiated.
3. `router.php` — sets up routing (does not yet use `$db`, but loads early as the application's core).
4. `config.php` — returns the configuration array, assigned to `$config`.
5. `new Database($config['database'])` — only the `'database'` slice of config is passed; other future services in `config.php` remain private to their own components.

The `$db` instance is now available to any controller file that is `require`d by the router, since `require` shares the same scope.

---
