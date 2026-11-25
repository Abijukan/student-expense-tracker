<?php
include "db.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.html#login");
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$msg = "";
$cats = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category_id = (int)$_POST["category_id"];
    $amount = (float)$_POST["amount"];
    $expense_date = $_POST["expense_date"];
    $description = trim($_POST["description"]);

    if ($amount <= 0) {
        $msg = "Expense amount must be greater than 0.";
    } else {
        $stmt = $conn->prepare("INSERT INTO expenses (user_id, category_id, amount, description, expense_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iidss", $user_id, $category_id, $amount, $description, $expense_date);

        if ($stmt->execute()) {
            $ym = substr($expense_date, 0, 7);
            $b_stmt = $conn->prepare("SELECT amount FROM budgets WHERE user_id = ? AND month_year = ?");
            $b_stmt->bind_param("is", $user_id, $ym);
            $b_stmt->execute();
            $b_row = $b_stmt->get_result()->fetch_assoc();

            if ($b_row) {
                $s_stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?");
                $s_stmt->bind_param("is", $user_id, $ym);
                $s_stmt->execute();
                $total = (float)$s_stmt->get_result()->fetch_assoc()["total"];

                if ($total > (float)$b_row["amount"]) {
                    $alert_msg = "Budget limit exceeded for $ym! Total spent: Rs. " . number_format($total, 2);
                    $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    $n_stmt->bind_param("is", $user_id, $alert_msg);
                    $n_stmt->execute();
                }
            }
            $msg = "Expense added successfully!";
        } else {
            $msg = "Failed to add expense: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Expense</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; margin: 0; }
        .wrap { max-width: 480px; margin: 40px auto; background: #fff; padding: 28px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h2 { color: #1e3a8a; margin-bottom: 10px; }
        label { display: block; margin-top: 14px; font-weight: 600; }
        input, select { width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; margin-top: 18px; padding: 11px; background: #10b981; color: #fff; border: none; border-radius: 5px; font-size: 1rem; font-weight: bold; cursor: pointer; }
        .msg { margin-top: 12px; color: #1d4ed8; font-weight: 600; }
        a { color: #1e3a8a; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="wrap">
    <h2>Add Expense</h2>
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
    <?php if ($msg): ?><div class="msg"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <form method="POST">
        <label>Category</label>
        <select name="category_id" required>
            <?php while ($c = $cats->fetch_assoc()): ?>
                <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
            <?php endwhile; ?>
        </select>

        <label>Amount (Rs.)</label>
        <input type="number" step="0.01" min="0.01" name="amount" required>

        <label>Expense Date</label>
        <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>

        <label>Description</label>
        <input type="text" name="description" placeholder="e.g., Bus fare or lunch">

        <button type="submit">Add Expense</button>
    </form>
</div>
</body>
</html>