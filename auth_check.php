<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect unauthenticated visitors to login
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}
?>