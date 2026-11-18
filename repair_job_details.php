<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';
require_once 'service_helper.php';

$jobId = (int)($_GET['job_id'] ?? 0);
if ($jobId <= 0) {
    die("Invalid Repair Job ID.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job'])) {
    $newStatus   = $_POST['status'] ?? 'Pending';
    $staffId     = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $laborCost   = isset($_POST['labor_cost']) ? (float)$_POST['labor_cost'] : 80.00;

    $allowedStatuses = ['Pending', 'In Progress', 'Awaiting Parts', 'Completed'];
    if (in_array($newStatus, $allowedStatuses)) {
        $upd = $pdo->prepare("UPDATE RepairJob SET Status = ?, StaffID = ?, Description = ? WHERE JobID = ?");
        $upd->execute([$newStatus, $staffId, $description, $jobId]);

        $chkInv = $pdo->prepare("SELECT InvoiceID FROM Invoice WHERE JobID = ?");
        $chkInv->execute([$jobId]);
        if ($chkInv->fetch()) {
            $pdo->prepare("UPDATE Invoice SET LaborCost = ? WHERE JobID = ?")->execute([$laborCost, $jobId]);
        } else {
            $pdo->prepare("INSERT INTO Invoice (JobID, LaborCost, PaymentStatus) VALUES (?, ?, 'Unpaid')")->execute([$jobId, $laborCost]);
        }

        $success = "Repair job and invoice updated successfully!";
    } else {
        $error = "Invalid status selected.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_part'])) {
    $partName  = trim($_POST['part_name'] ?? '');
    $unitPrice = (float)($_POST['unit_price'] ?? 0);
    $qtyUsed   = max(1, (int)($_POST['quantity_used'] ?? 1));

    if (!empty($partName) && $unitPrice >= 0) {
        $pStmt = $pdo->prepare("SELECT PartID FROM Part WHERE PartName = ? AND UnitPrice = ?");
        $pStmt->execute([$partName, $unitPrice]);
        $partRow = $pStmt->fetch();

        if ($partRow) {
            $partId = (int)$partRow['PartID'];
        } else {
            $insPart = $pdo->prepare("INSERT INTO Part (PartName, UnitPrice, StockQty) VALUES (?, ?, 100)");
            $insPart->execute([$partName, $unitPrice]);
            $partId = (int)$pdo->lastInsertId();
        }

        $pdo->prepare("INSERT INTO JobPart (JobID, PartID, QuantityUsed) VALUES (?, ?, ?)")->execute([$jobId, $partId, $qtyUsed]);
        $success = "Added part '$partName' (x$qtyUsed) to job and invoice.";
    } else {
        $error = "Please enter a valid part name and unit price.";
    }
}

$sql = "SELECT r.JobID, r.Status, r.Description, r.StaffID,
               b.BookingID, b.ServiceType, b.ServicePackage, b.OilType, b.BookingDate, b.BookingTime,
               v.PlateNumber, v.Make, v.Model, v.VehicleType,
               c.FullName AS CustomerName, c.Phone AS CustomerPhone,
               s.Name AS TechnicianName,
               inv.InvoiceID, inv.LaborCost, inv.PaymentStatus
        FROM RepairJob r
        JOIN Booking b ON r.BookingID = b.BookingID
        JOIN Vehicle v ON b.VehicleID = v.VehicleID
        JOIN Customer c ON v.CustomerID = c.CustomerID
        LEFT JOIN Staff s ON r.StaffID = s.StaffID
        LEFT JOIN Invoice inv ON r.JobID = inv.JobID
        WHERE r.JobID = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    die("Repair job record not found.");
}

$technicians = $pdo->query("SELECT StaffID, Name, Role FROM Staff ORDER BY Name ASC")->fetchAll();

$stmtParts = $pdo->prepare("SELECT p.PartName, jp.QuantityUsed, p.UnitPrice,
                                   (jp.QuantityUsed * p.UnitPrice) AS SubTotal
                            FROM JobPart jp
                            JOIN Part p ON jp.PartID = p.PartID
                            WHERE jp.JobID = ?");
$stmtParts->execute([$jobId]);
$partsUsed = $stmtParts->fetchAll();

$laborCost = isset($job['LaborCost']) ? (float)$job['LaborCost'] : getServiceLaborRate($job['ServiceType'], $job['ServicePackage'], $job['OilType']);
$partsTotal = 0;
foreach ($partsUsed as $part) {
    $partsTotal += (float)$part['SubTotal'];
}
$grandTotal = $laborCost + $partsTotal;

require_once 'repair_job_details_view.html';
?>