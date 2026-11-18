<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

if (($_SESSION['role'] ?? '') !== 'Administrator') {
    die("Access denied. Only Administrators can register new staff.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? '';

    $allowedRoles = ['Administrator', 'Service Agent', 'Technician'];

    if (!empty($name) && !empty($email) && !empty($password) && in_array($role, $allowedRoles)) {
        $checkStmt = $pdo->prepare("SELECT StaffID FROM Staff WHERE Email = ?");
        $checkStmt->execute([$email]);

        if ($checkStmt->fetch()) {
            $error = "This email is already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO Staff (Name, Email, Password, Role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword, $role]);

            $success = "Staff account for {$name} ({$role}) registered successfully!";
        }
    } else {
        $error = "Please fill in all fields correctly.";
    }
}

require_once 'staffRegister.html';
?>