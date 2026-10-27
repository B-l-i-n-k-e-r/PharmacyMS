<?php
session_start();
include_once('connect_db.php');

// Check if admin is logged in
if(!isset($_SESSION['username'])){
    header("location:index.php");
    exit();
}

// Check if ID is provided in the URL
if(isset($_GET['cashier_id']) && !empty($_GET['cashier_id'])) {
    $cashier_id = $_GET['cashier_id'];
    
    try {
        // Secure PDO deletion
        $stmt = $con->prepare("DELETE FROM cashier WHERE cashier_id = :id");
        $stmt->execute([':id' => $cashier_id]);
        
        // Redirect back to the cashier list
        header("location:admin_cashier.php?deleted=1");
        exit();
    } catch (PDOException $e) {
        die("Error deleting cashier: " . htmlspecialchars($e->getMessage()));
    }
} else {
    // If no ID provided, just go back to the list
    header("location:admin_cashier.php");
    exit();
}
?>