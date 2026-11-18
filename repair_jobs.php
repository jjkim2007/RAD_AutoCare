<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

$filter = trim($_GET['status'] ?? 'All');

$sql = "SELECT r.JobID, r.Status, r.Description,
               v.Make, v.Model, v.PlateNumber,
               s.Name AS MechanicName,
               inv.InvoiceID
        FROM RepairJob r
        JOIN Booking b ON r.BookingID = b.BookingID
        JOIN Vehicle v ON b.VehicleID = v.VehicleID
        LEFT JOIN Staff s ON r.StaffID = s.StaffID
        LEFT JOIN Invoice inv ON r.JobID = inv.JobID";

$params = [];
if ($filter === 'Pending') {
    $sql .= " WHERE r.Status = 'Pending'";
} elseif ($filter === 'In Progress') {
    $sql .= " WHERE r.Status = 'In Progress'";
} elseif ($filter === 'Completed') {
    $sql .= " WHERE r.Status = 'Completed'";
}

$sql .= " ORDER BY r.JobID DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

require_once 'repair_job_view.html';
?>