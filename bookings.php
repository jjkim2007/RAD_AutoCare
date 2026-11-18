<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

$error = '';

if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $pdo->beginTransaction();
        $jStmt = $pdo->prepare("SELECT JobID FROM RepairJob WHERE BookingID = ?");
        $jStmt->execute([$deleteId]);
        $jobIds = $jStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($jobIds)) {
            $inJobs = implode(',', array_fill(0, count($jobIds), '?'));
            $pdo->prepare("DELETE FROM JobPart WHERE JobID IN ($inJobs)")->execute($jobIds);
            $pdo->prepare("DELETE FROM Invoice WHERE JobID IN ($inJobs)")->execute($jobIds);
            $pdo->prepare("DELETE FROM RepairJob WHERE JobID IN ($inJobs)")->execute($jobIds);
        }

        $stmt = $pdo->prepare("DELETE FROM Booking WHERE BookingID = ?");
        $stmt->execute([$deleteId]);
        $pdo->commit();
        header("Location: bookings.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error deleting booking: " . $e->getMessage();
    }
}

// Status Change with 24-Hour Cancellation Enforcement
if (isset($_POST['update_status'])) {
    $bookingId = (int)$_POST['booking_id'];
    $newStatus = $_POST['status'] ?? '';

    if ($newStatus === 'Cancelled') {
        $bStmt = $pdo->prepare("SELECT BookingDate, BookingTime FROM Booking WHERE BookingID = ?");
        $bStmt->execute([$bookingId]);
        $bk = $bStmt->fetch();

        if ($bk) {
            $apptTs = strtotime($bk['BookingDate'] . ' ' . $bk['BookingTime']);
            $hoursDiff = ($apptTs - time()) / 3600;
            if ($hoursDiff < 24) {
                header("Location: bookings.php?err=cancel_24h");
                exit();
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE Booking SET Status = ? WHERE BookingID = ?");
    $stmt->execute([$newStatus, $bookingId]);
    header("Location: bookings.php");
    exit();
}

$sql = "SELECT b.BookingID, b.ServiceType, b.BookingDate, b.BookingTime, b.Status, v.PlateNumber, c.FullName 
        FROM Booking b
        JOIN Vehicle v ON b.VehicleID = v.VehicleID
        JOIN Customer c ON v.CustomerID = c.CustomerID
        ORDER BY b.BookingDate DESC, b.BookingTime DESC";
$bookings = $pdo->query($sql)->fetchAll();

require_once 'booking_view.html';
?>