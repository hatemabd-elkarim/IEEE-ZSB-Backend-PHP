# PHP OOP Research Questions

## 1. Inheritance: Benefits and Examples

### What is Inheritance?

**Inheritance** allows a class (child) to inherit properties and methods from another class (parent). This promotes code reusability and creates a hierarchical relationship between classes.

### Main Benefits:

1. **Code Reusability**: Write common code once in the parent class
2. **Logical Hierarchy**: Models real-world relationships (is-a relationship)
3. **Easier Maintenance**: Update shared functionality in one place
4. **Extensibility**: Add new features without modifying existing code

### Real-World Example: Phone Hierarchy

```php
<?php
// Parent Class - Generic Smartphone
class Smartphone {
    // smartphone generic properties
    protected string $brand;
    protected string $model;
    protected float $screenSize;
    protected int $batteryCapacity;
}

// Child Class - iPhone
class iPhone extends Smartphone {
    // iphone specific properites besides the generic ones
    private string $iosVersion;
    private bool $faceIDEnabled;
}

// Child Class - Sony
class Sony extends Smartphone {
    // sony specific properties besides the generic ones
    private string $androidVersion;
    private bool $hasHeadphoneJack;
}

```

---

## 2. The `final` Keyword

### What does `final` do?

- **On a Class**: Prevents the class from being extended (no child classes allowed)
- **On a Method**: Prevents the method from being overridden in child classes

### Why to Use `final`?

1. **Security**: Prevent critical functionality from being modified
2. **Performance**: Minor optimization (compiler knows method won't be overridden)

### Example: Final Class

```php
<?php
// Final class - cannot be extended
final class iPhone {
    private string $model;
    private string $securityChip = "Secure Enclave";

}
// This would cause an error:
// class CustomiPhone extends iPhone {}
// Fatal error: Class CustomiPhone cannot extend final class iPhone
?>

```

### Example: Final Method

```php
<?php
class Smartphone {
    protected string $brand;
    protected string $imei;

    // This method is final - cannot be overridden
    final public function getIMEI(): string {
        // Critical security feature - must not be tampered with
        return "IMEI: {$this->imei} (Verified by {$this->brand})";
    }

    // This method can be overridden
    public function makeCall(string $number): string {
        return "{$this->brand} calling {$number}...";
    }
}

class iPhone extends Smartphone {
    // We can override makeCall - it's not final
    public function makeCall(string $number): string {
        return "iPhone dialing {$number} with FaceTime Audio...";
    }

    // This would cause an error - getIMEI is final:
    // public function getIMEI(): string {
    //     return "Custom IMEI";
    // }
    // Fatal error: Cannot override final method Smartphone::getIMEI()
}

```

---

## 3. Overriding Methods

### What is Method Overriding?

**Overriding** means a child class provides its own implementation of a method that already exists in the parent class. The child's version replaces (overrides) the parent's version.

### Calling Parent Method: `parent::`

Use `parent::methodName()` to call the original parent method from inside the child's overridden method.

### Example

```php
<?php

class smartPhone {
    public function makeCall() {
        echo "Making a call from dialer\n";
    }
}

class iPhone extends Phone {
    public function makeCall() {
        echo "Making a call using FaceTime\n";
    }
}

class Sony extends Phone {
    public function makeCall() {
        // call parent method
        parent::makeCall();
    }
}

```

---

## 4. Abstract Class vs. Interface

### What's the Difference?

| Feature         | Abstract Class                                                    | Interface                                            |
| --------------- | ----------------------------------------------------------------- | ---------------------------------------------------- |
| **Methods**     | Can have both abstract (no body) and concrete (with body) methods | All methods are abstract (no body) - just signatures |
| **Properties**  | Can have properties                                               | Cannot have properties                               |
| **Inheritance** | A class can extend only ONE abstract class                        | A class can implement MULTIPLE interfaces            |
| **Purpose**     | Provides base implementation + enforces structure                 | Defines a contract (what methods must exist)         |
| **Keyword**     | `extends`                                                         | `implements`                                         |

---

## 5. Polymorphism

### What is Polymorphism?

**Polymorphism** (Greek: "many forms") means different objects can respond to the same method call in their own way. The same method name produces different behavior depending on which object calls it.

### Benefits:

- Write flexible, reusable code
- Treat different objects uniformly
- Easily extend functionality without changing existing code

### Real-World Example: Phone Notifications

```php
<?php
// Common interface - all phones can show notifications
interface Notifiable {
    public function showNotification(string $app, string $message): string;
}

// Different phone brands implement notifications differently
class iPhone implements Notifiable {
    private string $model;

    // iPhone's way of showing notifications
    public function showNotification(string $app, string $message): string {
        return "[{$this->model}] {$app}: {$message} (Slide to open)";
    }
}

class Sony implements Notifiable {
    private string $model;

    // Sony's way of showing notifications
    public function showNotification(string $app, string $message): string {
        return "[{$this->model}] {$app} → {$message} [Tap to expand]";
    }
}

```

---
