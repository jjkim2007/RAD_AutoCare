<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';
require_once 'service_helper.php';

$orphanStmt = $pdo->query("SELECT b.BookingID, b.ServiceType, b.ServicePackage, b.OilType 
                           FROM Booking b 
                           LEFT JOIN RepairJob r ON b.BookingID = r.BookingID 
                           WHERE r.JobID IS NULL AND b.Status != 'Cancelled'");
foreach ($orphanStmt->fetchAll() as $orphan) {
    ensureRepairJobAndInvoice($pdo, (int)$orphan['BookingID'], $orphan['ServiceType'], $orphan['ServicePackage'], $orphan['OilType']);
}

$filter = trim($_GET['status'] ?? 'All');

$sql = "SELECT r.JobID, r.Status, r.Description,
               b.ServiceType, b.ServicePackage, b.OilType, b.BookingDate,
               v.Make, v.Model, v.VehicleType, v.PlateNumber,
               c.FullName AS CustomerName,
               s.Name AS TechnicianName,
               inv.InvoiceID
        FROM RepairJob r
        JOIN Booking b ON r.BookingID = b.BookingID
        JOIN Vehicle v ON b.VehicleID = v.VehicleID
        JOIN Customer c ON v.CustomerID = c.CustomerID
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