<?php
include "db.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: index.html#login");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

if (isset($_GET["export"]) && $_GET["export"] === "csv") {
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=student_expense_report.csv");
    $out = fopen("php://output", "w");
    fputcsv($out, ["Date", "Category", "Description", "Amount (Rs.)"]);

    $q = $conn->prepare("SELECT e.expense_date, c.name AS category, e.description, e.amount
                        FROM expenses e
                        JOIN categories c ON e.category_id = c.id
                        WHERE e.user_id = ?
                        ORDER BY e.expense_date DESC");
    $q->bind_param("i", $user_id);
    $q->execute();
    $res = $q->get_result();
    while ($r = $res->fetch_assoc()) {
        fputcsv($out, [$r["expense_date"], $r["category"], $r["description"], $r["amount"]]);
    }
    fclose($out);
    exit;
}

$sum_stmt = $conn->prepare("SELECT c.name, COALESCE(SUM(e.amount), 0) AS total
                          FROM categories c
                          LEFT JOIN expenses e ON c.id = e.category_id AND e.user_id = ?
                          GROUP BY c.id, c.name
                          ORDER BY c.name ASC");
$sum_stmt->bind_param("i", $user_id);
$sum_stmt->execute();
$summary = $sum_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Reports</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; margin: 0; }
        .wrap { max-width: 720px; margin: 40px auto; background: #fff; padding: 28px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h2 { color: #1e3a8a; margin-bottom: 12px; }
        .top-links { margin-bottom: 18px; }
        .top-links a { margin-right: 16px; text-decoration: none; font-weight: 600; color: #1e3a8a; }
        .export-btn { background: #10b981; color: #fff !important; padding: 7px 14px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px; border: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; }
    </style>
</head>
<body>
<div class="wrap">
    <h2>Expense Reports & Statistics</h2>
    <div class="top-links">
        <a href="dashboard.php">← Back to Dashboard</a>
        <a href="reports.php?export=csv" class="export-btn">⬇ Export CSV Report</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Total Spent (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $summary->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["name"]); ?></td>
                    <td><?php echo number_format((float)$row["total"], 2); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>