<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

/** @var PDO $pdo */
$msg = '';
$error = '';

// Safe cascading delete vehicle
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $pdo->beginTransaction();
        
        // Find booking IDs
        $bStmt = $pdo->prepare("SELECT BookingID FROM Booking WHERE VehicleID = ?");
        $bStmt->execute([$deleteId]);
        $bookingIds = $bStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($bookingIds)) {
            $inBookings = implode(',', array_fill(0, count($bookingIds), '?'));
            
            // Find Job IDs
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

        $stmt = $pdo->prepare("DELETE FROM Vehicle WHERE VehicleID = ?");
        $stmt->execute([$deleteId]);
        $pdo->commit();
        header("Location: vehicles.php?msg=deleted");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Unable to delete vehicle: " . $e->getMessage();
    }
}

// Add New Vehicle 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $customerId  = (int)($_POST['customer_id'] ?? 0);
    $plateNumber = strtoupper(trim($_POST['plate_number'] ?? ''));
    $make        = trim($_POST['make'] ?? '');
    $model       = trim($_POST['model'] ?? '');
    $year        = !empty($_POST['year']) ? (int)$_POST['year'] : null;

    if ($customerId > 0 && !empty($plateNumber) && !empty($make) && !empty($model)) {
        $checkStmt = $pdo->prepare("SELECT VehicleID FROM Vehicle WHERE PlateNumber = ?");
        $checkStmt->execute([$plateNumber]);

        if ($checkStmt->fetch()) {
            $error = "This plate number ($plateNumber) is already registered in the system.";
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO Vehicle (CustomerID, PlateNumber, Make, Model, Year) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->execute([$customerId, $plateNumber, $make, $model, $year]);
            header("Location: vehicles.php?msg=added");
            exit();
        }
    } else {
        $error = "Customer, Plate Number, Make, and Model are required.";
    }
}

// Search and Read Vehicles
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $sql = "SELECT v.VehicleID, v.PlateNumber, v.Make, v.Model, v.Year, c.FullName, c.Phone
            FROM Vehicle v
            JOIN Customer c ON v.CustomerID = c.CustomerID         
            WHERE v.PlateNumber LIKE ? OR v.Make LIKE ? OR v.Model LIKE ? OR c.FullName LIKE ?
            ORDER BY v.VehicleID DESC";
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $vehicles = $stmt->fetchAll();
} else {
    $sql = "SELECT v.VehicleID, v.PlateNumber, v.Make, v.Model, v.Year, c.FullName, c.Phone
            FROM Vehicle v
            JOIN Customer c ON v.CustomerID = c.CustomerID
            ORDER BY v.VehicleID DESC";
    $vehicles = $pdo->query($sql)->fetchAll();
}

$customersList = $pdo->query("SELECT CustomerID, FullName, Phone FROM Customer ORDER BY FullName ASC")->fetchAll();

require_once 'vehicle_view.html';
?>