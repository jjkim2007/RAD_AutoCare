<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

/** @var PDO $pdo */
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
    $serviceType = trim($_POST['service_type'] ?? '');
    $bookingDate = $_POST['booking_date'] ?? '';

    if ($vehicleId > 0 && !empty($serviceType) && !empty($bookingDate)) {
        $stmt = $pdo->prepare("INSERT INTO Booking (VehicleID, ServiceType, BookingDate, Status) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$vehicleId, $serviceType, $bookingDate]);
        
        header("Location: bookings.php?msg=booked");
        exit();
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Populate Vehicle Dropdown
$sql = "SELECT v.VehicleID, v.PlateNumber, v.Make, v.Model, c.FullName 
        FROM Vehicle v
        JOIN Customer c ON v.CustomerID = c.CustomerID
        ORDER BY v.PlateNumber ASC";
$vehicles = $pdo->query($sql)->fetchAll();

$serviceOptions = [
    'Engine Oil Change & Filter',
    'Major Service',
    'Brake Pad Replacement',
    'Tire Alignment & Balancing',
    'Air Conditioner Servicing',
    'General Inspection & Diagnostics'
];

require_once 'create_booking_view.html';
?>