<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

$invoiceId = (int)($_GET['invoice_id'] ?? 0);
$jobId = (int)($_GET['job_id'] ?? 0);

if ($invoiceId > 0) {
    $stmt = $pdo->prepare("SELECT inv.*, c.FullName AS CustomerName, v.PlateNumber, v.Make, v.Model, v.VehicleType
                           FROM Invoice inv
                           JOIN RepairJob r ON inv.JobID = r.JobID
                           JOIN Booking b ON r.BookingID = b.BookingID
                           JOIN Vehicle v ON b.VehicleID = v.VehicleID
                           JOIN Customer c ON v.CustomerID = c.CustomerID
                           WHERE inv.InvoiceID = ?");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT inv.*, c.FullName AS CustomerName, v.PlateNumber, v.Make, v.Model, v.VehicleType, r.JobID
                           FROM RepairJob r
                           JOIN Booking b ON r.BookingID = b.BookingID
                           JOIN Vehicle v ON b.VehicleID = v.VehicleID
                           JOIN Customer c ON v.CustomerID = c.CustomerID
                           LEFT JOIN Invoice inv ON r.JobID = inv.JobID
                           WHERE r.JobID = ?");
    $stmt->execute([$jobId]);
    $invoice = $stmt->fetch();
}

if (!$invoice) {
    die("Invoice or Job record not found.");
}

$currentJobId = $invoice['JobID'];

$stmtParts = $pdo->prepare("SELECT p.PartName, jp.QuantityUsed, p.UnitPrice, 
                                   (jp.QuantityUsed * p.UnitPrice) AS SubTotal
                            FROM JobPart jp
                            JOIN Part p ON jp.PartID = p.PartID
                            WHERE jp.JobID = ?");
$stmtParts->execute([$currentJobId]);
$partsUsed = $stmtParts->fetchAll();

$laborCost = !empty($invoice['LaborCost']) ? (float)$invoice['LaborCost'] : 80.00;
$totalAmount = $laborCost;

foreach ($partsUsed as $p) {
    $totalAmount += (float)$p['SubTotal'];
}

require_once 'invoice.html';
?>