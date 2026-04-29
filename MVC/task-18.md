# MVC Research Questions

## 1. The Controller's Job:

When a user clicks a button to "View Profile," the Controller acts as a **traffic director** between the user's request and the application's response. Here's what happens step-by-step:

### What the Controller Does:

1. **Receives the Request**: Captures the user's click (e.g., `/profile/view/123`)
2. **Validates Input**: Checks if the user ID (123) is valid
3. **Calls the Model**: Asks the Model to fetch user data from the database
4. **Processes the Data**: May format or prepare the data if needed
5. **Loads the View**: Passes the data to the appropriate View file
6. **Returns the Response**: Sends the final HTML page back to the browser

### Simple Code Example:

```php
// ProfileController.php
class ProfileController {

    public function viewProfile($userId) {
        // Step 1: Validate input
        if (!is_numeric($userId)) {
            return "Invalid user ID";
        }

        // Step 2: Get data from Model
        $userModel = new UserModel();
        $userData = $userModel->getUserById($userId);

        // Step 3: Check if user exists
        if (!$userData) {
            return "User not found";
        }

        // Step 4: Pass data to View
        require 'views/profile.php';
    }
}
```

**Key Point**: The Controller doesn't create HTML itself—it just coordinates between the Model (data) and View (presentation).

---

## 2. Dynamic Views: Static HTML vs. Dynamic PHP View

### Static HTML File:

- **Fixed content** that never changes
- Same for every visitor
- Just plain HTML code
- Example: `about.html`

```html
<!-- static.html -->
<h1>Welcome to Our Site</h1>
<p>This page looks the same for everyone.</p>
```

### Dynamic PHP View:

- **Content changes** based on data
- Different for each visitor or situation
- PHP code generates HTML on-the-fly
- Example: `profile.php`

```php
<!-- profile.php (Dynamic View) -->
<h1>Welcome, <?php echo $userData['name']; ?>!</h1>
<p>Email: <?php echo $userData['email']; ?></p>
<p>Member since: <?php echo $userData['joined_date']; ?></p>
```

### The Key Difference:

| Static HTML                        | Dynamic PHP View                            |
| ---------------------------------- | ------------------------------------------- |
| Always shows "Welcome to Our Site" | Shows "Welcome, John!" or "Welcome, Sarah!" |
| No database needed                 | Gets data from database                     |
| Same file = same output            | Same file = different outputs               |

---

## 3. Data Passing: Controller to View

The Controller passes data to the View using **variables**.

### Method 1: Simple Variables

```php
// Controller
class UserController {
    public function showProfile($id) {
        $userModel = new UserModel();

        // Get data from database
        $username = $userModel->getName($id);
        $email = $userModel->getEmail($id);
        $age = $userModel->getAge($id);

        // Pass to View by including it
        include 'views/profile.php';
    }
}
```

```php
<!-- views/profile.php -->
<h1><?php echo $username; ?></h1>
<p>Email: <?php echo $email; ?></p>
<p>Age: <?php echo $age; ?></p>
```

### Method 2: Array (More Organized)

```php
// Controller
public function showProfile($id) {
    $userModel = new UserModel();

    // Put all data in one array
    $data = [
        'name' => $userModel->getName($id),
        'email' => $userModel->getEmail($id),
        'age' => $userModel->getAge($id)
    ];

    // Pass to View
    include 'views/profile.php';
}
```

```php
<!-- views/profile.php -->
<h1><?php echo $data['name']; ?></h1>
<p>Email: <?php echo $data['email']; ?></p>
<p>Age: <?php echo $data['age']; ?></p>
```

**How It Works**: When you `include` the View file, all the variables from the Controller are available in that View file(scope level).

---

## 4. Templating: Avoiding Copy-Paste for Headers & Footers

MVC helps you write your header and footer **once** and reuse them everywhere. No more copying and pasting!

### The Old Way:

```php
<!-- page1.php -->
<html>
<head><title>Page 1</title></head>
<body>
    <nav>Home | About | Contact</nav>  <!-- Copied -->
    <h1>Page 1 Content</h1>
    <footer>© 2024 My Site</footer>    <!-- Copied -->
</body>
</html>

<!-- page2.php -->
<html>
<head><title>Page 2</title></head>
<body>
    <nav>Home | About | Contact</nav>  <!-- Copied AGAIN! -->
    <h1>Page 2 Content</h1>
    <footer>© 2024 My Site</footer>    <!-- Copied AGAIN! -->
</body>
</html>
```

**Problem**: If you want to change the navigation, you have to edit 50 files!

### The MVC Way (Good):

```php
<!-- views/includes/header.php -->
<html>
<head><title><?php echo $pageTitle; ?></title></head>
<body>
    <nav>Home | About | Contact</nav>
```

```php
<!-- views/includes/footer.php -->
    <footer>© 2024 My Site</footer>
</body>
</html>
```

```php
<!-- views/profile.php -->
<?php include 'includes/header.php'; ?>

<h1>Profile Page Content</h1>
<p>This is unique to the profile page</p>

<?php include 'includes/footer.php'; ?>
```

```php
<!-- views/home.php -->
<?php include 'includes/header.php'; ?>

<h1>Home Page Content</h1>
<p>This is unique to the home page</p>

<?php include 'includes/footer.php'; ?>
```

### Benefits:

**One place to edit**: Change navigation once, updates everywhere  
**Consistency**: Every page has the same header/footer style  
**Less code**: No repetition  
**Easier maintenance**: Fix bugs in one file, not 50

---

## 5. Logic in Views: Why It's Bad Practice

Views should be **simple and clean**—just for displaying data. Complex logic makes them hard to read, test, and maintain.

### Example (Complex Logic in View):

```php
<!-- Bad View with too much logic -->
<h1>User Dashboard</h1>

<?php
// Complex calculations in View
$totalOrders = 0;
$totalRevenue = 0;
foreach ($orders as $order) {
    if ($order['status'] == 'completed') {
        $totalOrders++;
        $totalRevenue += $order['amount'];
        if ($order['discount'] > 0) {
            $totalRevenue -= ($order['amount'] * $order['discount'] / 100);
        }
    }
}

// Complex if statements
if ($totalRevenue > 10000 && $user['membership'] == 'gold') {
    $bonusPoints = $totalRevenue * 0.05;
} elseif ($totalRevenue > 5000 && $user['membership'] == 'silver') {
    $bonusPoints = $totalRevenue * 0.03;
} else {
    $bonusPoints = $totalRevenue * 0.01;
}
?>

<p>Total Orders: <?php echo $totalOrders; ?></p>
<p>Revenue: $<?php echo $totalRevenue; ?></p>
```

### Another Example (Logic in Controller):

```php
// Controller handles all the complex logic
class DashboardController {
    public function showDashboard($userId) {
        $orderModel = new OrderModel();
        $orders = $orderModel->getUserOrders($userId);

        // Do all calculations HERE
        $totalOrders = $this->calculateCompletedOrders($orders);
        $totalRevenue = $this->calculateRevenue($orders);
        $bonusPoints = $this->calculateBonus($totalRevenue, $user);

        // Pass clean, ready-to-display data to View
        $data = [
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'bonusPoints' => $bonusPoints
        ];

        include 'views/dashboard.php';
    }

    private function calculateCompletedOrders($orders) {
        // Complex logic here
    }

    private function calculateRevenue($orders) {
        // Complex logic here
    }
}
```

```php
<!-- Clean, simple View -->
<h1>User Dashboard</h1>
<p>Total Orders: <?php echo $data['totalOrders']; ?></p>
<p>Revenue: $<?php echo $data['totalRevenue']; ?></p>
<p>Bonus Points: <?php echo $data['bonusPoints']; ?></p>
```

### Why This Matters:

| Problem                 | Explanation                                      |
| ----------------------- | ------------------------------------------------ |
| **Hard to Test**        | Can't test business logic without rendering HTML |
| **Hard to Read**        | Mixing PHP loops with HTML is messy              |
| **Hard to Reuse**       | Can't use the same calculation logic elsewhere   |
| **Designer Unfriendly** | Frontend developers can't work on Views safely   |
| **Violates MVC**        | Views should display, not calculate              |

### Simple Rule:

- **Controller/Model**: Does the _thinking_ (calculations, decisions, database queries)
- **View**: Does the _showing_ (displays the results prettily)

---
