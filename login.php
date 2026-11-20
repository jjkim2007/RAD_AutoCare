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
        $stmt = $pdo->prepare("SELECT StaffID, Name, Password, Role FROM Staff WHERE Email = ?");
        $stmt->execute([$email]);
        $staff = $stmt->fetch();

if ($staff && $password === $staff['Password']) {
        $_SESSION['staff_id'] = $staff['StaffID'];
            $_SESSION['staff_name'] = $staff['Name'];
            $_SESSION['role'] = $staff['Role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

require_once 'login_view.html';
?>