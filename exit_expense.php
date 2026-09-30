<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$expense_id = intval($_GET['id'] ?? 0);

if ($expense_id > 0) {
    redirect('edit_expense.php?id=' . $expense_id);
}

redirect('dashboard.php');
?>