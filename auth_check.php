<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect unauthenticated users to the login page
    header("Location: sign.php");
    exit();
}
?>