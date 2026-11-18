<?php
require_once 'customer_auth_check.php';
require_once 'dbConnect.php';

$customerId = (int)$_SESSION['customer_id'];
$error = '';
$success = '';

// Time slots available
$timeSlots = [
    '09:00:00' => '09:00 AM',
    '10:00:00' => '10:00 AM',
    '11:00:00' => '11:00 AM',
    '13:00:00' => '01:00 PM',
    '14:00:00' => '02:00 PM',
    '15:00:00' => '03:00 PM',
    '16:00:00' => '04:00 PM',
];

$serviceOptions = [
    'Engine Oil Change & Filter',
    'Major Service',
    'Brake Pad Replacement',
    'Tire Alignment & Balancing',
    'Air Conditioner Servicing',
    'General Inspection & Diagnostics'
];

// 1. Add Vehicle for Logged-In Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $plateNumber = strtoupper(trim($_POST['plate_number'] ?? ''));
    $make = trim($_POST['make'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;

    if (!empty($plateNumber) && !empty($make) && !empty($model)) {
        $chk = $pdo->prepare("SELECT VehicleID FROM Vehicle WHERE PlateNumber = ?");
        $chk->execute([$plateNumber]);
        if ($chk->fetch()) {
            $error = "Plate number $plateNumber is already registered.";
        } else {
            $ins = $pdo->prepare("INSERT INTO Vehicle (CustomerID, PlateNumber, Make, Model, Year) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$customerId, $plateNumber, $make, $model, $year]);
            $success = "Vehicle $plateNumber added to your account!";
        }
    } else {
        $error = "Plate Number, Make, and Model are required.";
    }
}

// 2. Create Booking (With Slot Collision Check & Mandatory T&C)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
    $serviceType = trim($_POST['service_type'] ?? '');
    $bookingDate = $_POST['booking_date'] ?? '';
    $bookingTime = $_POST['booking_time'] ?? '';
    $termsAccepted = isset($_POST['terms_accepted']) ? 1 : 0;

    // Verify ownership of vehicle
    $ownStmt = $pdo->prepare("SELECT VehicleID FROM Vehicle WHERE VehicleID = ? AND CustomerID = ?");
    $ownStmt->execute([$vehicleId, $customerId]);

    if (!$ownStmt->fetch()) {
        $error = "Invalid vehicle selected.";
    } elseif ($termsAccepted !== 1) {
        $error = "You must read and accept the Terms & Conditions before booking.";
    } elseif (empty($serviceType) || empty($bookingDate) || empty($bookingTime)) {
        $error = "Please select service type, date, and time slot.";
    } elseif (strtotime($bookingDate . ' ' . $bookingTime) <= time()) {
        $error = "Booking date and time must be in the future.";
    } else {
        // FEATURE 1: Avoid Same Slot (Prevent double-booking active slots)
        $slotStmt = $pdo->prepare("SELECT BookingID FROM Booking WHERE BookingDate = ? AND BookingTime = ? AND Status != 'Cancelled'");
        $slotStmt->execute([$bookingDate, $bookingTime]);

        if ($slotStmt->fetch()) {
            $error = "Sorry, the slot on $bookingDate at " . ($timeSlots[$bookingTime] ?? $bookingTime) . " is already booked. Please select another time slot.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO Booking (VehicleID, ServiceType, BookingDate, BookingTime, TermsAccepted, Status) VALUES (?, ?, ?, ?, 1, 'Pending')");
            $stmt->execute([$vehicleId, $serviceType, $bookingDate, $bookingTime]);
            $success = "Booking submitted successfully! Please note cancellations require at least 24 hours advance notice.";
        }
    }
}

// 3. Cancel Booking (Enforce 24-Hour Advance Notice Policy)
if (isset($_GET['cancel'])) {
    $bookingId = (int)$_GET['cancel'];
    $bStmt = $pdo->prepare("SELECT b.BookingID, b.BookingDate, b.BookingTime, b.Status 
                            FROM Booking b
                            JOIN Vehicle v ON b.VehicleID = v.VehicleID
                            WHERE b.BookingID = ? AND v.CustomerID = ?");
    $bStmt->execute([$bookingId, $customerId]);
    $booking = $bStmt->fetch();

    if (!$booking) {
        $error = "Booking not found.";
    } elseif ($booking['Status'] === 'Cancelled') {
        $error = "This booking is already cancelled.";
    } else {
        $appointmentTimestamp = strtotime($booking['BookingDate'] . ' ' . $booking['BookingTime']);
        $hoursRemaining = ($appointmentTimestamp - time()) / 3600;

        // FEATURE 3: Cannot cancel immediately / within 24 hours
        if ($hoursRemaining < 24) {
            $error = "Cancellation Denied (T&C Policy): Bookings cannot be cancelled within 24 hours of the scheduled appointment (" . $booking['BookingDate'] . " " . date('h:i A', $appointmentTimestamp) . ").";
        } else {
            $upd = $pdo->prepare("UPDATE Booking SET Status = 'Cancelled' WHERE BookingID = ?");
            $upd->execute([$bookingId]);
            $success = "Booking #$bookingId has been cancelled.";
        }
    }
}

// Fetch Customer's Vehicles
$vListStmt = $pdo->prepare("SELECT * FROM Vehicle WHERE CustomerID = ? ORDER BY VehicleID DESC");
$vListStmt->execute([$customerId]);
$myVehicles = $vListStmt->fetchAll();

// Fetch Customer's Bookings
$bListStmt = $pdo->prepare("SELECT b.*, v.PlateNumber, v.Make, v.Model 
                            FROM Booking b
                            JOIN Vehicle v ON b.VehicleID = v.VehicleID
                            WHERE v.CustomerID = ?
                            ORDER BY b.BookingDate DESC, b.BookingTime DESC");
$bListStmt->execute([$customerId]);
$myBookings = $bListStmt->fetchAll();

require_once 'customer_portal_view.html';
?>