<?php
include "db.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.html#login");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["category_name"])) {
    $cat_name = trim($_POST["category_name"]);
    if ($cat_name !== "") {
        $ins = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $ins->bind_param("s", $cat_name);
        $ins->execute();
    }
}

$cats = $conn->query("SELECT id, name FROM categories ORDER BY id ASC");
$students = $conn->query("SELECT id, full_name, student_id, email, created_at FROM users WHERE role = 'student' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; margin: 0; }
        header { background: #1e3a8a; color: #fff; padding: 14px 6%; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; font-weight: 600; }
        .wrap { max-width: 960px; margin: 28px auto; padding: 0 20px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); margin-bottom: 22px; }
        h3 { color: #1e3a8a; margin-bottom: 12px; }
        input { padding: 9px; border: 1px solid #cbd5e1; border-radius: 5px; }
        button { padding: 9px 15px; background: #1e3a8a; color: #fff; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; }
    </style>
</head>
<body>
<header>
    <div><strong>Admin Panel</strong></div>
    <a href="logout.php">Logout</a>
</header>

<div class="wrap">
    <div class="card">
        <h3>Manage Expense Categories</h3>
        <form method="POST">
            <input type="text" name="category_name" placeholder="New category name" required>
            <button type="submit">Add Category</button>
        </form>
        <ul style="margin-top:14px; padding-left:20px;">
            <?php while ($c = $cats->fetch_assoc()): ?>
                <li><?php echo htmlspecialchars($c["name"]); ?></li>
            <?php endwhile; ?>
        </ul>
    </div>

    <div class="card">
        <h3>Registered Students</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Student ID</th>
                    <th>Email</th>
                    <th>Registered At</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students->num_rows > 0): ?>
                    <?php while ($s = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo (int)$s["id"]; ?></td>
                            <td><?php echo htmlspecialchars($s["full_name"]); ?></td>
                            <td><?php echo htmlspecialchars($s["student_id"]); ?></td>
                            <td><?php echo htmlspecialchars($s["email"]); ?></td>
                            <td><?php echo htmlspecialchars($s["created_at"]); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#64748b;">No registered students yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>