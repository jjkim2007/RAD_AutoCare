<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

/** @var PDO $pdo */
// Delete Booking safely
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
        die("Error deleting booking: " . htmlspecialchars($e->getMessage()));
    }
}

// Quick Status Change
if (isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE Booking SET Status = ? WHERE BookingID = ?");
    $stmt->execute([$_POST['status'], (int)$_POST['booking_id']]);
    header("Location: bookings.php");
    exit();
}

// Query Bookings joined with Vehicle and Customer records
$sql = "SELECT b.BookingID, b.ServiceType, b.BookingDate, b.Status, v.PlateNumber, c.FullName 
        FROM Booking b
        JOIN Vehicle v ON b.VehicleID = v.VehicleID
        JOIN Customer c ON v.CustomerID = c.CustomerID
        ORDER BY b.BookingDate DESC";
$bookings = $pdo->query($sql)->fetchAll();

require_once 'booking_view.html';
?>