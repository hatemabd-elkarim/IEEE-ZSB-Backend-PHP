# MVC Research Questions - Database security and best practices

---

## 1. Which Application Should Talk Directly to the Database?

**Short Answer**: Only the **Model** should talk directly to the database. Never the Controller or View.

### The Architecture:

```
USER → CONTROLLER → MODEL → DATABASE
                      ↓
                    VIEW
```

### Why Only the Model?

| Layer          | Can Access Database? | Why / Why Not                                                         |
| -------------- | -------------------- | --------------------------------------------------------------------- |
| **View**       | NO                   | Views just display data—they shouldn't fetch it                       |
| **Controller** | NO                   | Controllers coordinate, but don't handle data storage                 |
| **Model**      | YES                  | Models are the **data experts**—their only job is database operations |

### Code Example:

```php
// BAD PRACTICE: Controller talking directly to database
class UserController {
    public function showProfile($userId) {
        $db = new PDO("mysql:host=localhost;dbname=myapp", "user", "pass");
        $stmt = $db->query("SELECT * FROM users WHERE id = $userId");
        $userData = $stmt->fetch();

        include 'views/profile.php';
    }
}
```

```php
// GOOD PRACTICE: Model handles database, Controller uses Model
class UserModel {
    private $db;

    public function __construct() {
        $this->db = new PDO("mysql:host=localhost;dbname=myapp", "user", "pass");
    }

    public function getUserById($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}

class UserController {
    public function showProfile($userId) {
        // Controller uses Model
        $userModel = new UserModel();
        $userData = $userModel->getUserById($userId);

        include 'views/profile.php';
    }
}
```

### Real-World Benefits:

1. **Reusability**: Need user data somewhere else? Just call `$userModel->getUserById()` again
2. **Easy Testing**: Test database logic separately from web logic
3. **Security**: One place to enforce security rules
4. **Maintenance**: Change database structure? Only update the Model
5. **Team Work**: Database expert works on Models, frontend developer works on Views

---

## 2. Why Store Database Passwords in Config Files?

Storing sensitive information in separate configuration files instead of hardcoding is a **critical security practice**.

### BAD PRACTICE: Hardcoded Credentials

```php
// UserModel.php - ANYONE can see this!
class UserModel {
    public function connect() {
        $db = new PDO(
            "mysql:host=localhost;dbname=myapp",
            "admin",           // Hardcoded username
            "SuperSecret123"   // Hardcoded password
        );
        return $db;
    }
}
```

### GOOD PRACTICE: Separate Config File

```php
// config/database.php - Protected, not in version control
<?php
return [
    'host' => 'localhost',
    'dbname' => 'myapp',
    'username' => 'admin',
    'password' => 'SuperSecret123'
];
?>
```

```php
// UserModel.php - Clean and secure
class UserModel {
    private $db;

    public function __construct() {
        // Load config from separate file
        $config = require 'config/database.php';

        $this->db = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']}",
            $config['username'],
            $config['password']
        );
    }
}
```

### Why This Matters:

| Problem with Hardcoding    | How Config Files Solve It                                                |
| -------------------------- | ------------------------------------------------------------------------ |
| **Version Control Leak**   | Add `config/database.php` to `.gitignore`—never commits to GitHub        |
| **Different Environments** | Dev uses `localhost`, production uses real server—just swap config files |
| **Team Collaboration**     | Each developer has their own config with their own database passwords    |
| **Password Changes**       | Change password in ONE file, not searching through files                 |

### Even Better: Environment Variables (.env file)

```php
// .env file (never commit this)
DB_HOST=localhost
DB_NAME=myapp
DB_USER=admin
DB_PASS=SuperSecret123
```

```php
// Load from environment
$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');

$db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
```

---

## 3. What is PDO and Why Use It?

**PDO** (PHP Data Objects) is a modern, secure way to connect to databases in PHP.

### The Evolution:

```
Old Way (mysql_*) → Better Way (mysqli) → Best Way (PDO)
```

### Comparison:

```php
// OLD: mysql_* functions (removed in PHP 7)
$conn = mysql_connect("localhost", "user", "pass");
mysql_select_db("myapp");
$result = mysql_query("SELECT * FROM users WHERE id = $id"); // UNSAFE
$row = mysql_fetch_array($result);
```

```php
// BETTER: mysqli (works, but limited)
$conn = new mysqli("localhost", "user", "pass", "myapp");
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
// Only works with MySQL databases
```

```php
// BEST: PDO (modern, secure, flexible)
$db = new PDO("mysql:host=localhost;dbname=myapp", "user", "pass");
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
// Works with MySQL, PostgreSQL, SQLite, and more
```

### Why PDO is Preferred:

| Feature                | mysqli      | PDO                                     |
| ---------------------- | ----------- | --------------------------------------- |
| **Multiple Databases** | MySQL only  | MySQL, PostgreSQL, SQLite, Oracle, etc. |
| **Syntax**             | Complicated | Clean and simple                        |
| **Named Placeholders** | No          | ✅ Yes (`:name`)                        |
| **Object-Oriented**    | Partly      | Fully                                   |
| **Error Handling**     | Basic       | Advanced exceptions                     |
| **Future-Proof**       | Limited     | Industry standard                       |

### Named Placeholders Example:

```php
// mysqli - position matters, easy to mess up
$stmt = $conn->prepare("INSERT INTO users (name, email, age) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $name, $email, $age); // s=string, i=integer

// PDO - named parameters, much clearer
$stmt = $db->prepare("INSERT INTO users (name, email, age) VALUES (:name, :email, :age)");
$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':age' => $age
]);
```

### Switching Databases:

```php
// Need to switch from MySQL to PostgreSQL? Just change one line

// MySQL
$db = new PDO("mysql:host=localhost;dbname=myapp", "user", "pass");

// PostgreSQL - same code works
$db = new PDO("pgsql:host=localhost;dbname=myapp", "user", "pass");

// SQLite - same code works
$db = new PDO("sqlite:/path/to/database.db");
```

---

## 4. How Prepared Statements Protect Against SQL Injection

**SQL Injection** is when attackers insert malicious SQL code through your inputs to hack your database.

### The Attack (Without Prepared Statements):

```php
// MALICIOUS CODE
$userId = $_GET['id']; // User sends: ?id=1 OR 1=1

$query = "SELECT * FROM users WHERE id = $userId";
$result = $db->query($query);

// What actually runs:
// SELECT * FROM users WHERE id = 1 OR 1=1
// Returns ALL users, Hacker sees everyone's data
```

### Even Worse Attack:

```php
// User sends: ?id=1; DROP TABLE users;--

$query = "SELECT * FROM users WHERE id = $userId";

// What actually runs:
// SELECT * FROM users WHERE id = 1;
// DROP TABLE users;  ← Deletes your entire table
// --
```

### How Prepared Statements Prevent This:

```php
// SAFE: Prepared Statement
$userId = $_GET['id']; // User sends: ?id=1 OR 1=1

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

// What actually runs:
// SELECT * FROM users WHERE id = '1 OR 1=1'
// The whole string "1 OR 1=1" is treated as ONE value, not SQL code
// Attack fails, Returns no results.
```

### The Magic: Separation of Code and Data

**Without Prepared Statements:**

```
SQL Code + User Input = COMBINED → Database executes everything
```

**With Prepared Statements:**

```
Step 1: Database receives SQL structure: "SELECT * FROM users WHERE id = ?"
Step 2: Database receives data separately: "1 OR 1=1"
Step 3: Database knows "?" is DATA, not CODE
Step 4: Data is escaped and treated as literal text

```

---

## 5. Single Row vs. Multiple Rows: Real-World Examples

### Situation 1: Need ONE Row (Single Record)

**Use Case**: Login, View Profile, Check if Email Exists

```php
// Login: Need ONE user's credentials
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(); // Returns ONE row or null

if ($user && password_verify($password, $user['password'])) {
    echo "Welcome, " . $user['name'];
} else {
    echo "Login failed";
}
```

```php
// Check if email already exists (registration)
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$newEmail]);
$existingUser = $stmt->fetch(); // ONE row or null

if ($existingUser) {
    echo "Email already registered!";
} else {
    // Proceed with registration
}
```

```php
// Get product details for a specific product
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(); // ONE product

echo $product['name'];      // "iPhone 15 Pro"
echo $product['price'];     // 999.99
```

### Situation 2: Need MULTIPLE Rows (Array of Records)

**Use Case**: Listing Products, User History, Search Results

```php
// Show all products in a category
$stmt = $db->prepare("SELECT * FROM products WHERE category = ?");
$stmt->execute(['electronics']);
$products = $stmt->fetchAll(); // Array of ALL matching rows

foreach ($products as $product) {
    echo "<div>";
    echo "<h3>" . $product['name'] . "</h3>";
    echo "<p>$" . $product['price'] . "</p>";
    echo "</div>";
}
// Displays: iPhone, Samsung Galaxy, MacBook, etc.
```

```php
// User's order history
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(); // All orders for this user

echo "<h2>Your Orders</h2>";
foreach ($orders as $order) {
    echo "<p>Order #" . $order['id'] . " - $" . $order['total'] . "</p>";
}
```

---
