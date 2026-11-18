<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbConnect.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!empty($email) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT CustomerID, FullName, Password FROM Customer WHERE Email = ?");
            $stmt->execute([$email]);
            $customer = $stmt->fetch();

            if ($customer && !empty($customer['Password']) && password_verify($password, $customer['Password'])) {
                $_SESSION['customer_id'] = $customer['CustomerID'];
                $_SESSION['customer_name'] = $customer['FullName'];
                header("Location: customer_portal.php");
                exit();
            } else {
                $error = "Invalid customer email or password!";
            }
        } else {
            $error = "Please enter both email and password.";
        }
    } elseif ($action === 'register') {
        $fullName = trim($_POST['fullName'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (!empty($fullName) && !empty($phone) && !empty($email) && !empty($password)) {
            $check = $pdo->prepare("SELECT CustomerID FROM Customer WHERE Email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = "This email is already registered. Please sign in.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO Customer (FullName, Phone, Email, Password, Address) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$fullName, $phone, $email, $hashed, $address]);
                $success = "Registration successful! You can now log in below.";
            }
        } else {
            $error = "Name, Phone, Email, and Password are required to register.";
        }
    }
}

require_once 'customer_login_view.html';
?>