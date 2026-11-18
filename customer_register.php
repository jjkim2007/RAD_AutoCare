<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbConnect.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    if (!empty($fullName) && !empty($phone) && !empty($email) && !empty($password)) {
        $check = $pdo->prepare("SELECT CustomerID FROM Customer WHERE Email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "This email is already registered. Please sign in instead.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO Customer (FullName, Phone, Email, Password, Address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$fullName, $phone, $email, $hashed, $address]);
            header("Location: customer_login.php?msg=registered");
            exit();
        }
    } else {
        $error = "Full Name, Phone, Email, and Password are required.";
    }
}

require_once 'customer_register_view.html';
?>