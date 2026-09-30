<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$expense_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

$sql = "DELETE FROM expenses WHERE id = $expense_id AND user_id = $user_id";

if ($conn->query($sql)) {
    $_SESSION['success'] = "Expense deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting expense!";
}

redirect('dashboard.php');
?>