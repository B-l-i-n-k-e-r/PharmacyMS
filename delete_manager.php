<?php
session_start();
include_once('connect_db.php');

// Check if admin is logged in
if(!isset($_SESSION['username'])){
    header("location:index.php");
    exit();
}

// Check if ID is provided in the URL
if(isset($_GET['manager_id']) && !empty($_GET['manager_id'])) {
    $manager_id = $_GET['manager_id'];
    
    try {
        // Secure PDO deletion
        $stmt = $con->prepare("DELETE FROM manager WHERE manager_id = :id");
        $stmt->execute([':id' => $manager_id]);
        
        // Redirect back to the manager list
        header("location:admin_manager.php?deleted=1");
        exit();
    } catch (PDOException $e) {
        die("Error deleting manager: " . htmlspecialchars($e->getMessage()));
    }
} else {
    // If no ID provided, just go back to the list
    header("location:admin_manager.php");
    exit();
}
?>