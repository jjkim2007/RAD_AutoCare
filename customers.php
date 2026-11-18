<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

$msg = '';

if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $pdo->beginTransaction();
        $vStmt = $pdo->prepare("SELECT VehicleID FROM Vehicle WHERE CustomerID = ?");
        $vStmt->execute([$deleteId]);
        $vehicleIds = $vStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($vehicleIds)) {
            $inVehicles = implode(',', array_fill(0, count($vehicleIds), '?'));
            $bStmt = $pdo->prepare("SELECT BookingID FROM Booking WHERE VehicleID IN ($inVehicles)");
            $bStmt->execute($vehicleIds);
            $bookingIds = $bStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($bookingIds)) {
                $inBookings = implode(',', array_fill(0, count($bookingIds), '?'));
                $jStmt = $pdo->prepare("SELECT JobID FROM RepairJob WHERE BookingID IN ($inBookings)");
                $jStmt->execute($bookingIds);
                $jobIds = $jStmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($jobIds)) {
                    $inJobs = implode(',', array_fill(0, count($jobIds), '?'));
                    $pdo->prepare("DELETE FROM JobPart WHERE JobID IN ($inJobs)")->execute($jobIds);
                    $pdo->prepare("DELETE FROM Invoice WHERE JobID IN ($inJobs)")->execute($jobIds);
                    $pdo->prepare("DELETE FROM RepairJob WHERE JobID IN ($inJobs)")->execute($jobIds);
                }
                $pdo->prepare("DELETE FROM Booking WHERE BookingID IN ($inBookings)")->execute($bookingIds);
            }
            $pdo->prepare("DELETE FROM Vehicle WHERE CustomerID = ?")->execute([$deleteId]);
        }
        
        $pdo->prepare("DELETE FROM Customer WHERE CustomerID = ?")->execute([$deleteId]);
        $pdo->commit();
        header("Location: customers.php?msg=deleted");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Error deleting customer: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = trim($_POST['fullName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name !== '' && $phone !== '') {
        $stmt = $pdo->prepare("INSERT INTO Customer (FullName, Phone, Email, Address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email !== '' ? $email : null, $address]);
        header("Location: customers.php?msg=added");
        exit();
    } else {
        $msg = "Customer name and phone number are required.";
    }
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM Customer WHERE FullName LIKE ? OR Phone LIKE ? ORDER BY CustomerID DESC");
    $stmt->execute(["%$search%", "%$search%"]);
    $customers = $stmt->fetchAll();
} else {
    $customers = $pdo->query("SELECT * FROM Customer ORDER BY CustomerID DESC")->fetchAll();
}

require_once 'customer_view.html';
?>