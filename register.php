<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"]);
    $student_id = trim($_POST["student_id"]);
    $email = trim($_POST["email"]);
    $raw_pass = $_POST["password"];

    if (strlen($raw_pass) < 6) {
        echo "<script>alert('Password must be at least 6 characters.'); window.location='register.html';</script>";
        exit;
    }

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Email already exists! Please login.'); window.location='index.html#login';</script>";
        exit;
    }

    $hashed = password_hash($raw_pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, student_id, email, password, role) VALUES (?, ?, ?, ?, 'student')");
    $stmt->bind_param("ssss", $full_name, $student_id, $email, $hashed);

    if ($stmt->execute()) {
        echo "<script>alert('Registered successfully! Please login.'); window.location='index.html#login';</script>";
    } else {
        echo "Registration error: " . htmlspecialchars($conn->error);
    }
}
?>