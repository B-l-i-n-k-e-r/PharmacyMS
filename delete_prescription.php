<?php
session_start();
include_once('connect_db.php');

if(!isset($_SESSION['username'])){
    header("location:index.php");
    exit();
}

if(isset($_GET['prescription_id']) && is_numeric($_GET['prescription_id'])) {
    $id = $_GET['prescription_id'];
    try {
        $stmt = $con->prepare("DELETE FROM prescription WHERE prescription_id = :id");
        $stmt->execute([':id' => $id]);
        
        header("Location: prescription.php");
        exit();
    } catch (PDOException $e) {
        die("Error deleting prescription: " . htmlspecialchars($e->getMessage()));
    }
} else {
    header("location:index.php");
    exit();
}
?>