<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

/** @var PDO $pdo */
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM Customer WHERE CustomerID = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    die("Customer record not found.");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['fullName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name !== '' && $phone !== '') {
        $updateStmt = $pdo->prepare("UPDATE Customer SET FullName = ?, Phone = ?, Email = ?, Address = ? WHERE CustomerID = ?");
        $updateStmt->execute([$name, $phone, $email, $address, $id]);
        header("Location: customers.php");
        exit();
    } else {
        $error = "Name and phone are required.";
    }
}

require_once 'customer_edit.html';
?>