<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

$totalCustomers = $pdo->query("SELECT COUNT(*) FROM Customer")->fetchColumn();
$pendingBookings = $pdo->query("SELECT COUNT(*) FROM Booking WHERE Status = 'Pending'")->fetchColumn();
$activeJobs = $pdo->query("SELECT COUNT(*) FROM RepairJob WHERE Status IN ('In Progress', 'Awaiting Parts')")->fetchColumn();
$unpaidInvoices = $pdo->query("SELECT COUNT(*) FROM Invoice WHERE PaymentStatus = 'Unpaid'")->fetchColumn();

require_once 'dashboard_view.html';
?>