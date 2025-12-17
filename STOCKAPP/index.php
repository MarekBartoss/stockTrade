<?php
// index.php - Strict Router
session_start();

// If authenticated, go to market
if (isset($_SESSION['user_id'])) {
    header("Location: market.php");
    exit;
}

// Otherwise, go to auth
header("Location: auth.php");
exit;
?>