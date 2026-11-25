<?php
include "db.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.html#login");
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$month_year = date("Y-m");

$b_stmt = $conn->prepare("SELECT amount FROM budgets WHERE user_id = ? AND month_year = ?");
$b_stmt->bind_param("is", $user_id, $month_year);
$b_stmt->execute();
$b_row = $b_stmt->get_result()->fetch_assoc();
$budget = $b_row ? (float)$b_row["amount"] : 0.0;

$e_stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total_spent FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?");
$e_stmt->bind_param("is", $user_id, $month_year);
$e_stmt->execute();
$e_row = $e_stmt->get_result()->fetch_assoc();
$spent = (float)$e_row["total_spent"];

$remaining = $budget - $spent;

$list_stmt = $conn->prepare("SELECT e.id, e.expense_date, c.name AS category, e.description, e.amount
                            FROM expenses e
                            JOIN categories c ON e.category_id = c.id
                            WHERE e.user_id = ?
                            ORDER BY e.expense_date DESC, e.id DESC");
$list_stmt->bind_param("i", $user_id);
$list_stmt->execute();
$expenses = $list_stmt->get_result();

$n_stmt = $conn->prepare("SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 5");
$n_stmt->bind_param("i", $user_id);
$n_stmt->execute();
$notifications = $n_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f1f5f9; color: #1e293b; }
        header { background: #1e3a8a; color: #fff; padding: 14px 6%; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        nav a { color: #fff; text-decoration: none; margin-left: 14px; font-weight: 500; }
        .container { max-width: 1020px; margin: 28px auto; padding: 0 20px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin: 18px 0 24px; }
        .box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .box span { color: #64748b; font-size: 0.9rem; }
        .box strong { display: block; font-size: 1.5rem; margin-top: 6px; color: #0f172a; }
        .alert { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 18px; font-weight: 600; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); margin-bottom: 24px; }
        h3 { color: #1e3a8a; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; color: #334155; }
        ul { padding-left: 18px; }
        li { margin-bottom: 6px; font-size: 0.95rem; }
    </style>
</head>
<body>

<header>
    <div><strong>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?></strong></div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="set_budget.php">Set Budget</a>
        <a href="add_expense.php">Add Expense</a>
        <a href="reports.php">Reports & Export</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <h2>Monthly Overview (<?php echo htmlspecialchars($month_year); ?>)</h2>

    <?php if ($budget > 0 && $spent > $budget): ?>
        <div class="alert">⚠️ Budget Exceeded! You spent Rs. <?php echo number_format($spent, 2); ?> against your budget of Rs. <?php echo number_format($budget, 2); ?>.</div>
    <?php elseif ($budget > 0 && $spent >= ($budget * 0.8)): ?>
        <div class="alert">⚠️ Warning: You have used 80% or more of your monthly budget!</div>
    <?php endif; ?>

    <div class="summary">
        <div class="box">
            <span>Monthly Budget</span>
            <strong>Rs. <?php echo number_format($budget, 2); ?></strong>
        </div>
        <div class="box">
            <span>Total Spent</span>
            <strong>Rs. <?php echo number_format($spent, 2); ?></strong>
        </div>
        <div class="box">
            <span>Remaining Balance</span>
            <strong>Rs. <?php echo number_format($remaining, 2); ?></strong>
        </div>
    </div>

    <div class="card">
        <h3>Recent Notifications</h3>
        <?php if ($notifications->num_rows > 0): ?>
            <ul>
                <?php while ($n = $notifications->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($n["message"]); ?> <small style="color:#64748b;">(<?php echo htmlspecialchars($n["created_at"]); ?>)</small></li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p style="color:#64748b;">No notifications yet.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Recorded Expenses</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($expenses->num_rows > 0): ?>
                    <?php while ($row = $expenses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["expense_date"]); ?></td>
                            <td><?php echo htmlspecialchars($row["category"]); ?></td>
                            <td><?php echo htmlspecialchars($row["description"]); ?></td>
                            <td><?php echo number_format((float)$row["amount"], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#64748b;">No expenses recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>