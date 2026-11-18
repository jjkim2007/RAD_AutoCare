<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';
require_once 'service_helper.php';

$invoiceId = (int)($_GET['invoice_id'] ?? 0);
$jobId = (int)($_GET['job_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_payment'])) {
    $invId = (int)$_POST['invoice_id'];
    $newPayStatus = $_POST['payment_status'] === 'Paid' ? 'Paid' : 'Unpaid';
    $pdo->prepare("UPDATE Invoice SET PaymentStatus = ? WHERE InvoiceID = ?")->execute([$newPayStatus, $invId]);
    header("Location: invoice_view.php?invoice_id=$invId");
    exit();
}

if ($invoiceId > 0) {
    $stmt = $pdo->prepare("SELECT inv.*, b.ServiceType, b.ServicePackage, b.OilType, c.FullName AS CustomerName, v.PlateNumber, v.Make, v.Model, v.VehicleType
                           FROM Invoice inv
                           JOIN RepairJob r ON inv.JobID = r.JobID
                           JOIN Booking b ON r.BookingID = b.BookingID
                           JOIN Vehicle v ON b.VehicleID = v.VehicleID
                           JOIN Customer c ON v.CustomerID = c.CustomerID
                           WHERE inv.InvoiceID = ?");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT inv.*, b.ServiceType, b.ServicePackage, b.OilType, c.FullName AS CustomerName, v.PlateNumber, v.Make, v.Model, v.VehicleType, r.JobID
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

$currentJobId = (int)$invoice['JobID'];

if (empty($invoice['InvoiceID'])) {
    $defaultLabor = getServiceLaborRate($invoice['ServiceType'], $invoice['ServicePackage'], $invoice['OilType']);
    $pdo->prepare("INSERT INTO Invoice (JobID, LaborCost, PaymentStatus) VALUES (?, ?, 'Unpaid')")->execute([$currentJobId, $defaultLabor]);
    $invoice['InvoiceID'] = $pdo->lastInsertId();
    $invoice['LaborCost'] = $defaultLabor;
    $invoice['PaymentStatus'] = 'Unpaid';
}

$stmtParts = $pdo->prepare("SELECT p.PartName, jp.QuantityUsed, p.UnitPrice, 
                                   (jp.QuantityUsed * p.UnitPrice) AS SubTotal
                            FROM JobPart jp
                            JOIN Part p ON jp.PartID = p.PartID
                            WHERE jp.JobID = ?");
$stmtParts->execute([$currentJobId]);
$partsUsed = $stmtParts->fetchAll();

$laborCost = isset($invoice['LaborCost']) ? (float)$invoice['LaborCost'] : getServiceLaborRate($invoice['ServiceType'], $invoice['ServicePackage'], $invoice['OilType']);
$totalAmount = $laborCost;

foreach ($partsUsed as $p) {
    $totalAmount += (float)$p['SubTotal'];
}

require_once 'invoice.html';
?>