<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbConnect.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT CustomerID, FullName, Password, Status FROM Customer WHERE Email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();

        if ($customer && ($customer['Status'] ?? 'Active') === 'Inactive') {
            $error = "This customer account is marked as Inactive. Please contact the workshop.";
} elseif ($customer && !empty($customer['Password']) && $password === $customer['Password']) {
            $_SESSION['customer_id'] = $customer['CustomerID'];
            $_SESSION['customer_name'] = $customer['FullName'];
            header("Location: customer_booking.php");
            exit();
        } else {
            $error = "Invalid customer email or password!";
        }
    } else {
        $error = "Please enter both email and password.";
    }
}

require_once 'customer_login_view.html';
?>