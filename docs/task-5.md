# PHP Website Security & Hacking Protection

## Section 1: Definitions & Analogies

### What is Security?

Security is defined as the state of being protected or safe. In web contexts, it involves defending publicly accessible systems against unauthorized use. Websites are likened to open houses where anyone can enter, requiring measures to make access difficult for unauthorized visitors.

### Key Principles

- **Knowledge and Action**: Security requires both understanding threats and implementing defenses. Expertise develops through experience, as hackers evolve.
- **Balance**: Overly restrictive security can frustrate legitimate users. For example, account lockouts after failed logins can be exploited by competitors to deny access.
- **Updates**: Always use the latest stable software versions, as older ones contain known vulnerabilities.

### What is Hacker?

A hacker is anyone who uses something in a way it was not intended. In web security, hackers attempt to break into websites or systems.

### Types of Hackers

- **White Hat**: Ethical hackers hired to test and improve security.
- **Black Hat**: Malicious hackers who exploit systems for gain, such as stealing resources or building botnets.

### Black Hat Subtypes

- **Curious Users**: Explore sites out of curiosity, potentially discovering secrets.
- **Script Kiddies**: Use pre-written scripts without understanding them.
- **Thrill Seekers**: Hack for excitement, targeting high-profile sites.
- **Activists**: Politically motivated, targeting governments or organizations.
- **Trophy Hunters**: Seek bragging rights from breaching notable targets.
- **Professionals**: Hack for money, ranging from low to high skill.

### Relevant Threats

For developers, focus on curious users, script kiddies, and professionals.

### What is social engineering?

Social engineering is persuading individuals to voluntarily reveal confidential information. It's often easier than technical exploits.

### Common Vectors

- **Weak Password Hygiene**: Passwords written on notes near workstations.
- **Dumpster Diving**: Discarding sensitive documents without shredding.
- **Keyloggers**: Software that records keystrokes and sends logs to attackers.
- **Social Media Reconnaissance**: Using public profiles to answer security questions.
- **Phishing**: Fake emails with links to cloned sites that capture credentials.

### Defenses

- Enable two-step verification (e.g., SMS codes).
- Check URLs before entering credentials.
- Treat unsolicited emails suspiciously.
- Avoid storing credentials in plain sight.

## Section 2: Private code

### Separate Private and Public Folders

Store sensitive code (functions, classes, config) in a non-web-accessible folder. Recommended structure:

```
project/
├── private/          ← Sensitive files (not web-accessible)
│   ├── functions.php
│   └── index.php     ← Empty, prevents directory listing
└── public/           ← Web root
    ├── index.php     ← Entry point
    └── images/
        └── index.php ← Empty, prevents directory listing
```

Include private files from public/index.php using relative paths:

```php
include('../private/functions.php');
```

### Preventing Directory Listings

- Add empty `index.php` files to folders to avoid Apache's default listing.
- Use `.htaccess` to forbid indexes:

```apache
Options -Indexes
```

### Always Use PHP Extensions for Sensitive Data

Store sensitive data in `.php` files, not `.txt` or `.json`, as PHP files are executed server-side and not served as plain text.

```php
// Bad: config.json (readable in browser)
{ "db_password": "topsecret" }

// Good: config.php (processed by PHP)
<?php $db_password = 'topsecret';
```

## Section 3: Secured Includes

### The Vulnerability

Using user input directly in `include` allows path traversal or arbitrary code execution:

```php
$page = $_GET['page'];
include($page);  // Dangerous
```

- **Path Traversal**: Attackers can access `../../etc/passwd`.
- **Arbitrary Code Execution**: `include` runs PHP in any file, even images with embedded code.

### Hiding PHP in Images

Images can have PHP appended via hex editors, executing when included.

### Defenses

- Concatenate `.php` server-side:

```php
$page = $_GET['page'];
include($page . '.php');
```

- Crop uploaded images to destroy embedded code.
- Whitelist files using `glob()`:

```php
$folder = './includes/';
$files = glob($folder . '*.php');
$page = $_GET['page'];
$target = $folder . $page . '.php';

if (in_array($target, $files)) {
    require($target);
} else {
    echo 'File not found.';
}
```

- Use `file_get_contents()` for non-PHP content:

```php
echo file_get_contents($target);
```

## Section 3: Single Page Loading

### Problem with Multiple Entry Points

Each PHP file is a potential attack vector. Multiple files require securing each individually.

### Single Entry Point Pattern

Route all traffic through one `index.php`, including subpages dynamically:

```
project/
├── public/
│   └── index.php      ← Single entry point
└── private/
    └── includes/
        ├── index.php  ← Empty (prevents listing)
        ├── home.php
        ├── posts.php
        └── 404.php
```

Example `index.php`:

```php
$folder = '../private/includes/';
$files = glob($folder . '*.php');
$page = $_GET['page'] ?? 'home';
$filename = $folder . $page . '.php';

if (in_array($filename, $files)) {
    include($filename);
} else {
    include($folder . '404.php');
}
```

### Using .htaccess for Clean URLs

Rewrite URLs for clean paths:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```

Update code to use `$_GET['url']`.

## Section 4: Clean code

### Problem with Repeated Code

Duplicated database logic across files complicates maintenance and security.

### Creating functions.php

Centralize database operations:

```php
function connect(): PDO
{
    $host = 'localhost';
    $db = 'security_db';
    $user = 'root';
    $pass = '';

    return new PDO("mysql:host={$host};dbname={$db}", $user, $pass);
}

function db_read(string $query): array|false
{
    $connection = connect();
    $statment = $connection->prepare($query);
    $statment->execute();

    $data = [];

    while ($row = $statment->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }

    return empty($data) ? false : $data;
}
```

### Usage

Include in main `index.php` and use functions in subpages:

```php
// public/index.php
require('../private/includes/functions.php');

// private/includes/posts.php
$result = db_read('SELECT * FROM posts');
if ($result) {
    foreach ($result as $row) {
        echo '<h2>' . $row['title'] . '</h2>';
    }
}
```

### Benefits

- One place for security updates (e.g., sanitization).
- Reduces code duplication and audit complexity.
- Encourages OOP for better encapsulation.
