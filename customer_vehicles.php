<?php
require_once 'customer_auth_check.php';
require_once 'dbConnect.php';
require_once 'vehicle_options.php';

$customerId = (int)$_SESSION['customer_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $plateNumber = strtoupper(trim($_POST['plate_number'] ?? ''));
    $make        = trim($_POST['make'] ?? '');
    $model       = trim($_POST['model'] ?? '');
    $vehicleType = trim($_POST['vehicle_type'] ?? '');
    $year        = !empty($_POST['year']) ? (int)$_POST['year'] : null;

    if (!empty($plateNumber) && !empty($make) && !empty($model) && !empty($vehicleType)) {
        $chk = $pdo->prepare("SELECT VehicleID FROM Vehicle WHERE PlateNumber = ?");
        $chk->execute([$plateNumber]);
        if ($chk->fetch()) {
            $error = "Plate number $plateNumber is already registered in the system.";
        } else {
            $ins = $pdo->prepare("INSERT INTO Vehicle (CustomerID, PlateNumber, Make, Model, VehicleType, Year) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute([$customerId, $plateNumber, $make, $model, $vehicleType, $year]);
            $success = "Vehicle $plateNumber ($make $model - $vehicleType) registered successfully!";
        }
    } else {
        $error = "Plate Number, Brand, Model, and Vehicle Body Type are required.";
    }
}

if (isset($_GET['delete'])) {
    $vehicleId = (int)$_GET['delete'];
    $chkB = $pdo->prepare("SELECT BookingID FROM Booking WHERE VehicleID = ? AND Status != 'Cancelled'");
    $chkB->execute([$vehicleId]);
    if ($chkB->fetch()) {
        $error = "Cannot remove this vehicle because it has active service bookings.";
    } else {
        $del = $pdo->prepare("DELETE FROM Vehicle WHERE VehicleID = ? AND CustomerID = ?");
        $del->execute([$vehicleId, $customerId]);
        $success = "Vehicle removed from your account.";
    }
}

$vListStmt = $pdo->prepare("SELECT * FROM Vehicle WHERE CustomerID = ? ORDER BY VehicleID DESC");
$vListStmt->execute([$customerId]);
$myVehicles = $vListStmt->fetchAll();

require_once 'customer_vehicles_view.html';
?>