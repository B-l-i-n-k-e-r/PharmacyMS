<?php
session_start();
include_once('connect_db.php');

// Check if admin is logged in
if(!isset($_SESSION['username'])){
    header("location:index.php");
    exit();
}

// Check if ID is provided in the URL
if(isset($_GET['pharmacist_id']) && !empty($_GET['pharmacist_id'])) {
    $pharmacist_id = $_GET['pharmacist_id'];
    
    try {
        // Secure PDO deletion
        $stmt = $con->prepare("DELETE FROM pharmacist WHERE pharmacist_id = :id");
        $stmt->execute([':id' => $pharmacist_id]);
        
        // Redirect back to the pharmacist list
        header("location:admin_pharmacist.php?deleted=1");
        exit();
    } catch (PDOException $e) {
        die("Error deleting pharmacist: " . htmlspecialchars($e->getMessage()));
    }
} else {
    // If no ID provided, just go back to the list
    header("location:admin_pharmacist.php");
    exit();
}
?>