<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user's monthly budget
$budget_query = "SELECT monthly_budget FROM users WHERE id = $user_id";
$budget_result = $conn->query($budget_query);
$user = $budget_result->fetch_assoc();
$monthly_budget = $user['monthly_budget'];

// Get current month's expenses
$current_month = date('Y-m');
$expense_query = "SELECT e.*, c.name as category_name, c.icon 
                  FROM expenses e 
                  JOIN categories c ON e.category_id = c.id 
                  WHERE e.user_id = $user_id 
                  AND DATE_FORMAT(e.expense_date, '%Y-%m') = '$current_month'
                  ORDER BY e.expense_date DESC";
$expenses = $conn->query($expense_query);

// Calculate total spent this month
$total_query = "SELECT SUM(amount) as total FROM expenses 
                WHERE user_id = $user_id 
                AND DATE_FORMAT(expense_date, '%Y-%m') = '$current_month'";
$total_result = $conn->query($total_query);
$total_spent = $total_result->fetch_assoc()['total'] ?? 0;

// Get category-wise spending
$category_query = "SELECT c.name, c.icon, SUM(e.amount) as total 
                   FROM expenses e 
                   JOIN categories c ON e.category_id = c.id 
                   WHERE e.user_id = $user_id 
                   AND DATE_FORMAT(e.expense_date, '%Y-%m') = '$current_month'
                   GROUP BY e.category_id";
$category_spending = $conn->query($category_query);

// prepare arrays for chart availability
$cat_names = [];
$cat_totals = [];
if ($category_spending && $category_spending->num_rows > 0) {
    // fetch all rows to build arrays then reset pointer for card rendering
    while ($row = $category_spending->fetch_assoc()) {
        $cat_names[] = $row['name'];
        $cat_totals[] = $row['total'];
    }
    $category_spending->data_seek(0);
}

// Get spending by day for chart
$daily_query = "SELECT DATE(expense_date) as date, SUM(amount) as total 
                FROM expenses 
                WHERE user_id = $user_id 
                AND DATE_FORMAT(expense_date, '%Y-%m') = '$current_month'
                GROUP BY DATE(expense_date)
                ORDER BY date";
$daily_spending = $conn->query($daily_query);

$dates = [];
$amounts = [];
while ($row = $daily_spending->fetch_assoc()) {
    $dates[] = date('d M', strtotime($row['date']));
    $amounts[] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Expense Tracker</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>📊 Expense Tracker</h1>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="add_expense.php" class="btn btn-primary">➕ Add Expense</a>
                <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="card">
                <div class="card-icon">💰</div>
                <h3>Monthly Budget</h3>
                <p class="amount">₹<?php echo number_format($monthly_budget, 2); ?></p>
            </div>
            
            <div class="card">
                <div class="card-icon">💸</div>
                <h3>Total Spent</h3>
                <p class="amount">₹<?php echo number_format($total_spent, 2); ?></p>
            </div>
            
            <div class="card">
                <div class="card-icon">📈</div>
                <h3>Remaining</h3>
                <p class="amount <?php echo ($monthly_budget - $total_spent) < 0 ? 'negative' : ''; ?>">
                    ₹<?php echo number_format($monthly_budget - $total_spent, 2); ?>
                </p>
            </div>
        </div>
        
        <!-- Budget Progress Bar -->
        <?php if ($monthly_budget > 0): ?>
        <div class="budget-progress">
            <div class="progress-header">
                <span>Budget Usage</span>
                <span><?php echo round(($total_spent / $monthly_budget) * 100, 1); ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress" style="width: <?php echo min(($total_spent / $monthly_budget) * 100, 100); ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Charts Section -->
        <div class="charts-row">
            <div class="chart-container">
                <h3>Daily Spending Trend</h3>
                <?php if(empty($dates)): ?>
                    <p class="no-data">No spending recorded yet.</p>
                <?php else: ?>
                    <canvas id="dailyChart"></canvas>
                <?php endif; ?>
            </div>
            
            <div class="chart-container">
                <h3>Category Distribution</h3>
                <?php if(empty($cat_names)): ?>
                    <p class="no-data">No categories to display.</p>
                <?php else: ?>
                    <canvas id="categoryChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Category-wise Spending -->
        <div class="category-spending">
            <h2>📊 Spending by Category</h2>
            <div class="category-grid">
                <?php if ($category_spending && $category_spending->num_rows > 0): ?>
                    <?php 
                    $category_spending->data_seek(0); // Reset pointer
                    while($category = $category_spending->fetch_assoc()): 
                    ?>
                        <div class="category-card">
                            <span class="category-icon"><?php echo $category['icon']; ?></span>
                            <h4><?php echo $category['name']; ?></h4>
                            <p class="amount">₹<?php echo number_format($category['total'], 2); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-data">No expenses recorded this month</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Expenses -->
        <div class="recent-expenses">
            <h2>📝 Recent Expenses</h2>
            <?php if ($expenses && $expenses->num_rows > 0): ?>
                <table class="expense-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($expense = $expenses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($expense['expense_date'])); ?></td>
                            <td>
                                <span class="category-icon small"><?php echo $expense['icon']; ?></span>
                                <?php echo $expense['category_name']; ?>
                            </td>
                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                            <td class="amount-column">₹<?php echo number_format($expense['amount'], 2); ?></td>
                            <td>
                                <a href="edit_expense.php?id=<?php echo $expense['id']; ?>" class="btn-edit">✏️ Edit</a>
                                <a href="delete_expense.php?id=<?php echo $expense['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this expense?')">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No expenses recorded yet. <a href="add_expense.php">Add your first expense!</a></p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Daily Spending Chart
    const dailyCanvas = document.getElementById('dailyChart');
    if (dailyCanvas) {
        const dailyCtx = dailyCanvas.getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates ?: ['']); ?>,
                datasets: [{
                    label: 'Daily Spending (₹)',
                    data: <?php echo json_encode($amounts ?: [0]); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Category Chart
    <?php 
    // arrays already prepared before output
    $cat_colors = ['#667eea', '#764ba2', '#f687b3', '#f6ad55', '#68d391', '#4fd1c5', '#fbb6ce', '#9f7aea'];
    ?>

    const categoryCanvas = document.getElementById('categoryChart');
    if (categoryCanvas) {
        const categoryCtx = categoryCanvas.getContext('2d');
        new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($cat_names); ?>,
            datasets: [{
                data: <?php echo json_encode($cat_totals); ?>,
                backgroundColor: <?php echo json_encode(array_slice($cat_colors, 0, count($cat_names))); ?>,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    }
    </script>
</body>
</html>