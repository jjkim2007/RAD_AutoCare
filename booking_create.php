<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';
require_once 'service_helper.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId      = (int)($_POST['vehicle_id'] ?? 0);
    $serviceType    = trim($_POST['service_type'] ?? '');
    $servicePackage = ($serviceType === 'Major Service') ? trim($_POST['service_package'] ?? '') : null;
    $oilType        = trim($_POST['oil_type'] ?? '');
    $oilType        = ($oilType !== '') ? $oilType : null;
    $bookingDate    = $_POST['booking_date'] ?? '';
    $bookingTime    = $_POST['booking_time'] ?? '';
    $termsAccepted  = isset($_POST['terms_accepted']) ? 1 : 0;

    if ($termsAccepted !== 1) {
        $error = "Please acknowledge and accept the Booking Terms & Conditions.";
    } elseif ($serviceType === 'Major Service' && empty($servicePackage)) {
        $error = "Please select a Major Service Package (10k km or 100k km).";
    } elseif ($vehicleId > 0 && !empty($serviceType) && !empty($bookingDate) && !empty($bookingTime)) {
        $slotStmt = $pdo->prepare("SELECT BookingID FROM Booking WHERE BookingDate = ? AND BookingTime = ? AND Status != 'Cancelled'");
        $slotStmt->execute([$bookingDate, $bookingTime]);

        if ($slotStmt->fetch()) {
            $error = "Slot unavailable: Another vehicle is already booked on $bookingDate at " . ($timeSlots[$bookingTime] ?? $bookingTime) . ".";
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO Booking (VehicleID, ServiceType, ServicePackage, OilType, BookingDate, BookingTime, TermsAccepted, Status) VALUES (?, ?, ?, ?, ?, ?, 1, 'Pending')");
                $stmt->execute([$vehicleId, $serviceType, $servicePackage, $oilType, $bookingDate, $bookingTime]);
                $newBookingId = (int)$pdo->lastInsertId();

                ensureRepairJobAndInvoice($pdo, $newBookingId, $serviceType, $servicePackage, $oilType);

                $pdo->commit();
                header("Location: bookings.php?msg=booked");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error creating booking: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please fill in all required fields including date and time slot.";
    }
}

$sql = "SELECT v.VehicleID, v.PlateNumber, v.Make, v.Model, v.VehicleType, c.FullName 
        FROM Vehicle v
        JOIN Customer c ON v.CustomerID = c.CustomerID
        WHERE c.Status = 'Active'
        ORDER BY v.PlateNumber ASC";
$vehicles = $pdo->query($sql)->fetchAll();

require_once 'create_booking_view.html';
?>