<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

$error = '';

$timeSlots = [
    '09:00:00' => '09:00 AM',
    '10:00:00' => '10:00 AM',
    '11:00:00' => '11:00 AM',
    '13:00:00' => '01:00 PM',
    '14:00:00' => '02:00 PM',
    '15:00:00' => '03:00 PM',
    '16:00:00' => '04:00 PM',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
    $serviceType = trim($_POST['service_type'] ?? '');
    $bookingDate = $_POST['booking_date'] ?? '';
    $bookingTime = $_POST['booking_time'] ?? '';
    $termsAccepted = isset($_POST['terms_accepted']) ? 1 : 0;

    if ($termsAccepted !== 1) {
        $error = "Please acknowledge and accept the Booking Terms & Conditions.";
    } elseif ($vehicleId > 0 && !empty($serviceType) && !empty($bookingDate) && !empty($bookingTime)) {
        $slotStmt = $pdo->prepare("SELECT BookingID FROM Booking WHERE BookingDate = ? AND BookingTime = ? AND Status != 'Cancelled'");
        $slotStmt->execute([$bookingDate, $bookingTime]);

        if ($slotStmt->fetch()) {
            $error = "Slot unavailable: Another vehicle is already booked on $bookingDate at " . ($timeSlots[$bookingTime] ?? $bookingTime) . ".";
        } else {
            $stmt = $pdo->prepare("INSERT INTO Booking (VehicleID, ServiceType, BookingDate, BookingTime, TermsAccepted, Status) VALUES (?, ?, ?, ?, 1, 'Pending')");
            $stmt->execute([$vehicleId, $serviceType, $bookingDate, $bookingTime]);
            
            header("Location: bookings.php?msg=booked");
            exit();
        }
    } else {
        $error = "Please fill in all required fields including date and time slot.";
    }
}

$sql = "SELECT v.VehicleID, v.PlateNumber, v.Make, v.Model, v.VehicleType, c.FullName 
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