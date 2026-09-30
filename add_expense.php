<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Fetch categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $category_id = intval($_POST['category_id']);
    $amount = floatval($_POST['amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $expense_date = $_POST['expense_date'];
    
    $sql = "INSERT INTO expenses (user_id, category_id, amount, description, expense_date) 
            VALUES ($user_id, $category_id, $amount, '$description', '$expense_date')";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Expense added successfully!";
        redirect('dashboard.php');
    } else {
        $error = "Error adding expense: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - Expense Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>📊 Expense Tracker</h1>
            <div class="nav-links">
                <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="form-container">
            <h2>➕ Add New Expense</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="expense-form">
                <div class="form-group">
                    <label for="category_id">📂 Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php while($category = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo $category['icon'] . ' ' . $category['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount">💰 Amount (₹)</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" placeholder="Enter amount" required>
                </div>
                
                <div class="form-group">
                    <label for="description">📝 Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="What did you spend on?" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="expense_date">📅 Date</label>
                    <input type="date" id="expense_date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary">💾 Save Expense</button>
            </form>
        </div>
    </div>
</body>
</html>