<?php
require_once 'customer_auth_check.php';
require_once 'dbConnect.php';
require_once 'service_helper.php';

$customerId = (int)$_SESSION['customer_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    $vehicleId      = (int)($_POST['vehicle_id'] ?? 0);
    $serviceType    = trim($_POST['service_type'] ?? '');
    $servicePackage = ($serviceType === 'Major Service') ? trim($_POST['service_package'] ?? '') : null;
    $oilType        = trim($_POST['oil_type'] ?? '');
    $oilType        = ($oilType !== '') ? $oilType : null;
    $bookingDate    = $_POST['booking_date'] ?? '';
    $bookingTime    = $_POST['booking_time'] ?? '';
    $termsAccepted  = isset($_POST['terms_accepted']) ? 1 : 0;

    $ownStmt = $pdo->prepare("SELECT VehicleID FROM Vehicle WHERE VehicleID = ? AND CustomerID = ?");
    $ownStmt->execute([$vehicleId, $customerId]);

    if (!$ownStmt->fetch()) {
        $error = "Invalid vehicle selected.";
    } elseif ($termsAccepted !== 1) {
        $error = "You must read and accept the Terms & Conditions before booking.";
    } elseif ($serviceType === 'Major Service' && empty($servicePackage)) {
        $error = "Please select a Major Service Package (10k km or 100k km).";
    } elseif (empty($serviceType) || empty($bookingDate) || empty($bookingTime)) {
        $error = "Please select service type, date, and time slot.";
    } elseif (strtotime($bookingDate . ' ' . $bookingTime) <= time()) {
        $error = "Booking date and time must be in the future.";
    } else {
        $slotStmt = $pdo->prepare("SELECT BookingID FROM Booking WHERE BookingDate = ? AND BookingTime = ? AND Status != 'Cancelled'");
        $slotStmt->execute([$bookingDate, $bookingTime]);

        if ($slotStmt->fetch()) {
            $error = "Sorry, the slot on $bookingDate at " . ($timeSlots[$bookingTime] ?? $bookingTime) . " is already booked. Please choose another slot.";
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO Booking (VehicleID, ServiceType, ServicePackage, OilType, BookingDate, BookingTime, TermsAccepted, Status) VALUES (?, ?, ?, ?, ?, ?, 1, 'Pending')");
                $stmt->execute([$vehicleId, $serviceType, $servicePackage, $oilType, $bookingDate, $bookingTime]);
                $newBookingId = (int)$pdo->lastInsertId();

                $jobId = ensureRepairJobAndInvoice($pdo, $newBookingId, $serviceType, $servicePackage, $oilType);

                $pdo->commit();
                $success = "Booking #$newBookingId confirmed! Repair Job #$jobId and Invoice have been automatically generated.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error creating booking: " . $e->getMessage();
            }
        }
    }
}

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

        if ($hoursRemaining < 24) {
            $error = "Cancellation Denied (T&C Policy): Bookings cannot be cancelled within 24 hours of the scheduled appointment (" . $booking['BookingDate'] . " " . date('h:i A', $appointmentTimestamp) . ").";
        } else {
            $upd = $pdo->prepare("UPDATE Booking SET Status = 'Cancelled' WHERE BookingID = ?");
            $upd->execute([$bookingId]);
            $success = "Booking #$bookingId has been cancelled.";
        }
    }
}

$vListStmt = $pdo->prepare("SELECT * FROM Vehicle WHERE CustomerID = ? ORDER BY VehicleID DESC");
$vListStmt->execute([$customerId]);
$myVehicles = $vListStmt->fetchAll();

$bListStmt = $pdo->prepare("SELECT b.*, v.PlateNumber, v.Make, v.Model, v.VehicleType, r.JobID, inv.InvoiceID 
                            FROM Booking b
                            JOIN Vehicle v ON b.VehicleID = v.VehicleID
                            LEFT JOIN RepairJob r ON b.BookingID = r.BookingID
                            LEFT JOIN Invoice inv ON r.JobID = inv.JobID
                            WHERE v.CustomerID = ?
                            ORDER BY b.BookingDate DESC, b.BookingTime DESC");
$bListStmt->execute([$customerId]);
$myBookings = $bListStmt->fetchAll();

require_once 'customer_booking_view.html';
?>