<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password =$_POST["password"];

    $stmt =$conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);$stmt->execute();
    $res =$stmt->get_result();

    if ($user =$res->fetch_assoc()) {
        if (password_verify($password,$user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["role"] = $user["role"];

            if ($user["role"] === "admin") {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        }
    }

    echo "<script>alert('Invalid email or password!'); window.location='index.html#login';</script>";
}
?>