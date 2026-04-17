# PHP OOP Research Questions

## 1. Class vs. Object:

**Real-World Analogy: Blueprint vs. House**

A **Class** is like a blueprint for a house. It defines the structure, properties, and behaviors, but it's not a physical house you can live in.

An **Object** is like an actual house built from that blueprint. You can create many houses (objects) from the same blueprint (class), and each house can have different characteristics (property values).

### PHP Example:

```php
<?php
class House { // class: blueprint
    public $color;
    public $rooms;
    public $address;

    public function __construct($color, $rooms, $address) {
        $this->color = $color;
        $this->rooms = $rooms;
        $this->address = $address;
    }

    public function describe() {
        return "A {$this->color} house with {$this->rooms} rooms at {$this->address}";
    }
}

// Objects - Actual Houses built from the blueprint
$house1 = new House("blue", 3, "123 streetx");
$house2 = new House("red", 4, "456 streety");

echo $house1->describe() . "\n";
// -> A blue house with 3 rooms at 123 streetx

echo $house2->describe() . "\n";
// -> A red house with 4 rooms at 456 streety
?>
```

---

## 2. $this vs. self:: : Usage and Differences

### $this

- Refers to the **current instance** of the class (the object)
- Used to access **instance properties and methods**
- Used with **non-static** members
- Context: Object context

### self::

- Refers to the **class itself** (not an instance)
- Used to access **static properties and methods**
- Used with **static** members and **constants**
- Context: Class context

### PHP Example:

```php
<?php
class BankAccount {
    // Instance property (different for each object)
    private $balance;

    // Static property (shared and accessible across all instances, or in other words, it is class property which means that there is no need to create instance to call it)
    private static $totalAccounts = 0;

    // Class constant
    const BANK_NAME = "MyBank";

    public function __construct($initialBalance) {
        $this->balance = $initialBalance;  // Use $this for instance property
        self::$totalAccounts++;  // Use self:: for static property
    }

    // Instance method
    public function deposit($amount) {
        $this->balance += $amount;  // Use $this for instance property
        return $this->balance;
    }

    // Instance method can access instance, static, constant memebers
    public function getBalance() {
        return $this->balance;
    }

    public function getBankName() {
        return self::BANK_NAME;
    }

    // Static method can only access static and constant members
    public static function getTotalAccounts() {
        return self::$totalAccounts;
    }

}

```

---

## 3. Access Modifiers (Encapsulation)

### Three Access Modifiers:

1. **public**: Accessible from anywhere (inside class, outside class, in child classes)
2. **protected**: Accessible only inside the class and child inherited classes
3. **private**: Accessible only inside the class where it's defined

### Why to Make Properties Private?

Making properties private protects data integrity and prevents unauthorized or incorrect modifications that forces a developer to use a controlled method with abstract layer of validation.

### PHP Example

```php
<?php
class BankAccount {
    private $balance; // that means the balance is only accessible inside the class

    public function deposit($amount) {
        if($amount < 0) { // abstract layer of validation
            return 'transaction failed';
        }
        $this->balance += $amount;
        return 'transaction succeeded';
    }

    // note that for private properties, we use setters and getters to deal with
    public function setBalance($amount) {
        // same validation of deposit
        ...
        // no return
    }

    public function getBalance($amount) {
        return $this->balance;
    }
}

myAccount = new BankAccount();
// if i accidently entered -100 it will be handled cleanly
myAccount->deposit(-100);

// but if it balance was public so i need to manually check for positive amount, which gives a weak structure of the class
myAccount->balance += -100; // small typo with no checks.

```

---

## 4. Typed Properties

**Typed Properties** allow you to declare the data type a property must hold.

### Benefits:

1. **Type Safety**: Prevents bugs by ensuring only correct data types are assigned
2. **Self-Documenting**: Makes code clearer about what data is expected
3. **IDE support**: Catches type mismatches immediately and Better autocomplete.

### PHP Example - WITHOUT Typed Properties:

```php
<?php
class Product {
    public $name;
    public $price;
    public $inStock;

    public function __construct($name, $price, $inStock) {
        $this->name = $name;
        $this->price = $price;
        $this->inStock = $inStock;
    }

    public function calculateTotal($quantity) {
        return $this->price * $quantity;  // What if price is a string?
    }
}

// BUG: Wrong types can be passed!
$product = new Product("Laptop", "expensive", "yes");  // price is string!

// This will cause unexpected behavior or errors
// echo $product->calculateTotal(2);  // "expensive" * 2 = error or 0
?>
```

### PHP Example - WITH Typed Properties:

```php
<?php
class Product {
    public string $name;        // Must be string
    public float $price;        // Must be float
    public bool $inStock;       // Must be boolean

    public function __construct(string $name, float $price, bool $inStock) {
        ...
    }

    public function calculateTotal(int $quantity): float {
        ...
    }
}
?>
```

### Common Type Declarations:

```php
<?php
class TypeExamples {
    public int $age;                    // Integer
    public float $salary;               // Float/decimal
    public string $name;                // String
    public bool $isActive;              // Boolean
    public array $hobbies;              // Array
    public ?string $middleName;         // Nullable string (can be null)
    public DateTime $createdAt;         // Object type
}
?>
```

---

## 5. Constructor Methods

### What is `__construct()`?

The **constructor** is a special method (BUT WITH NO RETURN) that automatically runs when you create a new object. It's used to initialize the object's properties and set up its initial state.

### Why Use Constructors with Arguments?

1. **Initialize Properties Immediately**: Set values when object is created
2. **Ensure Required Data**: Force users to provide necessary information
3. **Code Efficiency**: No need to set properties one by one by `[->]` object operator
4. **Consistency**: Ensure objects are always in a valid state

### PHP Example - WITHOUT Constructor:

```php
<?php
class House {
    public $color;
    public $rooms;
    public $address;

}

// Have to set each property manually
$myHouse = new House();
$myHouse->color = "red";
$myHouse->rooms = 3;
$myHouse->address = "123 streetx";


// you can easily forget to assign a property

$neighbourHouse = new House();
$neighbourHouse->color = "red";
// forget to assign number of rooms
$neighbourHouse->address = "123 streetx";

?>
```

### PHP Example - WITH Constructor:

```php
<?php
class House {
    private string $color;
    private int $rooms;
    private string $address;

    // Constructor - runs automatically when new House() is called
    public function __construct(string $color, int $rooms, string $address) {
        $this->color = $color;
        $this->rooms = $rooms;
        $this->address = $address;

        // You can also add validation or setup logic here
        if ($this->rooms < 0) {
            throw new Exception("Number of rooms cannot be negative!");
        }
    }
}

// Clean, one-line object creation with all required data
$myHouse = new House("red", 5, "123 streetx");

?>

```
