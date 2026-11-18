<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['staff_id'], $_SESSION['staff_name'], $_SESSION['role']);
header("Location: login.php");
exit();
?>