# PHP OOP Research Questions

## 1. Traits:

### The Problem

PHP doesn't allow a class to extend multiple parent classes. For example:

```php
<?php
class Child extends Parent1, Parent2 {
    // Error
}
?>
```

### The Solution:

**Traits** are like reusable code snippets that can be inserted into multiple classes. They let you share methods across different classes without inheritance.

### When to Use Traits

- When multiple unrelated classes need the same functionality
- BUT inheritance doesn't make sense logically
- To avoid code duplication
- To solve multiple inheritance problem

### Example

```php
<?php
// Define a Trait
trait feature1 {
    public function call($person) {
        echo "Calling $person";
    }
}

trait feature2 {
    public function takePhoto() {
        echo "New photo captured" ;
    }
}

// Use traits in different classes
class IPhone {
    use feature1, feature2;  // Using multiple traits

    public $IOSVersion;
}

class Sony {
    use feature1, feature2;  // Same traits, different class

    public $AndroidVersion;
}

// Usage
$myIPhone = new IPhone();

$mySony = new Sony();

echo $myIPhone->call("Ali") . "\n";
// Output: Calling Ali

echo $mySony->takePhoto() . "\n";
// Output: New photo captured
?>
```

---

## 2. Namespaces:

### What is a Namespace?

A **namespace** is like a folder for your code, but it is a virtual one. It groups related classes together and prevents conflicts when two classes have the same name or collisions.

### The Problem Without Namespaces

```php
<?php
// File: Apple/iPhone.php
class Phone {
    public function call() {
        return "Calling with iPhone";
    }
}

// File: Samsung/Galaxy.php
class Phone {
    public function call() {
        return "Calling with Galaxy";
    }
}
?>

// File: main.php
require "Apple/iphone.php";
require "Samsung/Galaxy.php";

// ERROR: Cannot redeclare class Phone on requiring both files
```

### The Solution: Use Namespaces

```php
<?php
// File: Apple/iPhone.php
namespace Apple;

class Phone {
    public function call() {
        return "Calling with iPhone";
    }
}

// File: Samsung/Galaxy.php
namespace Samsung;

class Phone {
    public function call() {
        return "Calling with Galaxy";
    }
}

// File: main.php
// Now we can use both Phone classes

// Method 1: Using full namespace path
$iphone = new \Apple\Phone();
$galaxy = new \Samsung\Phone();


// Method 2: Using 'use' keyword and alias
use Apple\Phone as ApplePhone;
use Samsung\Phone as SamsungPhone;

$phone1 = new ApplePhone();
$phone2 = new SamsungPhone();

?>
```

---

## 3. Autoloading:

### The Old Way (Before Autoloading)

```php
<?php
// You had to manually require EVERY class file
require 'classes/User.php';
require 'classes/Product.php';
require 'classes/Order.php';
require 'classes/Payment.php';
require 'classes/Shipping.php';
// ... hundreds of require statements

$user = new User();
$product = new Product();
?>
```

**Problems:**

- Tedious and time-consuming
- Easy to forget a file
- All files loaded even if not used
- Hard to maintain

### The New Way: Autoloading

**Autoloading** automatically includes class files when you use them, without manual `require` statements.

### Simple Autoloader Example

```php
<?php
spl_autoload_register(function($className) {
    $file = "classes/" . $className . ".php";

    if (file_exists($file)) {
        require $file;
    }
});

// Now just use classes - they load automatically!
$user = new User();
$product = new Product();
$order = new Order();

?>
```

---

## 4. Magic Methods: `__get` and `__set`

### What Are They?

**Magic methods** are special methods that PHP calls automatically in certain situations. They start with double underscores `__`.

### `__get($property)` - Triggered When Reading Inaccessible Properties

Called automatically when you try to access a property that doesn't exist or is private.

### `__set($property, $value)` - Triggered When Writing to Inaccessible Properties

Called automatically when you try to set a property that doesn't exist or is private.

### Simple Example

```php
<?php
class User {
    private $data;

    public function __get($property) {
        return "This property [$property] is not found or not accessible";
    }

    public function __set($property, $value) {
         return "This property [$property] is not found or not accessible";
    }
}

```

### They can be used as traditional setters and getters to provide validations

````php
<?php
class Product {
    private $data = [];

    public function __set($property, $value) {
        // Validate price
        if ($property === 'price' && $value < 0) {
            throw new Exception("Price cannot be negative!");
        }

        // Validate stock
        if ($property === 'stock' && !is_int($value)) {
            throw new Exception("Stock must be an integer!");
        }

        $this->data[$property] = $value;
    }

    public function __get($property) {
        return $this->data[$property] ?? "Not found";
    }
}


---

## 5. Static Methods and Properties

### What Does `static` Mean?

**Static** members belong to the **class itself**, not to individual objects. They're shared across all instances.

### Key Differences

| Feature          | Regular (Instance)  | Static                |
| ---------------- | ------------------- | --------------------- |
| **Belongs to**   | Object              | Class                 |
| **Access via**   | `$object->method()` | `ClassName::method()` |
| **Keyword**      | `$this`             | `self::`              |
| **Need object?** | Yes (use `new`)     | No                    |

### Simple Example

```php
<?php
class Student {
    // Static property - shared by all instances
    public static $count = 0;

    // Instance property - unique per object
    public $id;
    public $name;

    public function __construct($name) {
        self::$count++;
        $this->id = self::$count;
        $this->name = $name;
    }

    // Static method - can be called without creating an object
    public static function getCount() {
        return self::$count;
    }
}

// Access static method WITHOUT creating an object
echo "Total Students: " . Student::getCount() . "\n";
// Output: Total Students: 0

$std1 = new Student1("Hatem");
$std2 = new Student2("Ayman");
$std3 = new Student3("Nabil");

echo "Total Students: " . Student::getCount() . "\n";
// Output: Total Students: 3

?>
````

### You DON'T Need `new` for Static Methods

```php
<?php
class MathHelper {
    // Static method - no object needed
    public static function add($a, $b) {
        return $a + $b;
    }

    public static function multiply($a, $b) {
        return $a * $b;
    }
}

// Call directly on the class - NO 'new' needed
echo MathHelper::add(5, 3) . "\n";
echo MathHelper::multiply(4, 7) . "\n";

// No need to do this:
// $math = new MathHelper();
// echo $math->add(5, 3);
?>
```

---
