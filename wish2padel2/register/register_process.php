<?php
session_start();
require_once '../config.php';

function randomUserId($min = 100000, $max = 99999999) {
    return mt_rand($min, $max);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    if (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Username or email already registered.";
    }
    $stmt->close();

    if (count($errors) > 0) {
        $_SESSION['error_messages'] = $errors;
        header("Location: register.php");
        exit();
    }

    do {
        $userId = randomUserId();
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $checkStmt->bind_param("i", $userId);
        $checkStmt->execute();
        $checkStmt->store_result();
        $exists = $checkStmt->num_rows > 0;
        $checkStmt->close();
    } while ($exists);

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Set timezone Riyadh
    date_default_timezone_set('Asia/Riyadh');
    $created_at = date('Y-m-d H:i:s');

    $insertStmt = $conn->prepare("INSERT INTO users (id, username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $role = 'admin';
    $insertStmt->bind_param("isssss", $userId, $username, $email, $passwordHash, $role, $created_at);

    if ($insertStmt->execute()) {
        $_SESSION['success_message'] = "Registration successful! Please login.";
        header("Location: ../login/login.php");
        exit();
    } else {
        $_SESSION['error_messages'] = ["Registration failed. Please try again later."];
        header("Location: register.php");
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}
