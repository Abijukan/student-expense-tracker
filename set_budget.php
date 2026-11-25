<?php
include "db.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.html#login");
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $month_year = $_POST["month_year"];
    $amount = (float)$_POST["amount"];

    if ($amount <= 0) {
        $msg = "Please enter a valid budget greater than 0.";
    } else {
        $check = $conn->prepare("SELECT id FROM budgets WHERE user_id = ? AND month_year = ?");
        $check->bind_param("is", $user_id, $month_year);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $upd = $conn->prepare("UPDATE budgets SET amount = ? WHERE user_id = ? AND month_year = ?");
            $upd->bind_param("dis", $amount, $user_id, $month_year);
            $upd->execute();
            $msg = "Monthly budget updated successfully!";
        } else {
            $ins = $conn->prepare("INSERT INTO budgets (user_id, month_year, amount) VALUES (?, ?, ?)");
            $ins->bind_param("isd", $user_id, $month_year, $amount);
            $ins->execute();
            $msg = "Monthly budget saved successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set Monthly Budget</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; margin: 0; }
        .wrap { max-width: 460px; margin: 45px auto; background: #fff; padding: 28px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h2 { color: #1e3a8a; margin-bottom: 10px; }
        label { display: block; margin-top: 14px; font-weight: 600; }
        input { width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; margin-top: 18px; padding: 11px; background: #1e3a8a; color: #fff; border: none; border-radius: 5px; font-size: 1rem; font-weight: bold; cursor: pointer; }
        .msg { margin-top: 12px; color: #047857; font-weight: 600; }
        a { color: #1e3a8a; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="wrap">
    <h2>Set Monthly Budget</h2>
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
    <?php if ($msg): ?><div class="msg"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <form method="POST">
        <label>Select Month</label>
        <input type="month" name="month_year" value="<?php echo date('Y-m'); ?>" required>

        <label>Budget Amount (Rs.)</label>
        <input type="number" step="0.01" min="1" name="amount" placeholder="e.g., 25000" required>

        <button type="submit">Save Budget</button>
    </form>
</div>
</body>
</html>