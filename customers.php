<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

$msg = '';

// Soft-Delete: Mark Customer as Inactive (Retains records for 5-10 years compliance)
if (isset($_GET['deactivate'])) {
    $custId = (int)$_GET['deactivate'];
    $stmt = $pdo->prepare("UPDATE Customer SET Status = 'Inactive' WHERE CustomerID = ?");
    $stmt->execute([$custId]);
    header("Location: customers.php?msg=deactivated");
    exit();
}

// Reactivate Customer
if (isset($_GET['activate'])) {
    $custId = (int)$_GET['activate'];
    $stmt = $pdo->prepare("UPDATE Customer SET Status = 'Active' WHERE CustomerID = ?");
    $stmt->execute([$custId]);
    header("Location: customers.php?msg=activated");
    exit();
}

// Add Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = trim($_POST['fullName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name !== '' && $phone !== '') {
        $stmt = $pdo->prepare("INSERT INTO Customer (FullName, Phone, Email, Address, Status) VALUES (?, ?, ?, ?, 'Active')");
        $stmt->execute([$name, $phone, $email !== '' ? $email : null, $address]);
        header("Location: customers.php?msg=added");
        exit();
    } else {
        $msg = "Customer name and phone number are required.";
    }
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status_filter'] ?? 'All');

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = "(FullName LIKE ? OR Phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter === 'Active' || $statusFilter === 'Inactive') {
    $conditions[] = "Status = ?";
    $params[] = $statusFilter;
}

$sql = "SELECT * FROM Customer";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY CustomerID DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

require_once 'customer_view.html';
?>