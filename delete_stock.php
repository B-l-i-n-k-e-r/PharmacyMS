<?php
session_start();
include_once('connect_db.php');

if(!isset($_SESSION['username'])){
    header("location:index.php");
    exit();
}

if(isset($_GET['stock_id']) && is_numeric($_GET['stock_id'])) {
    $id = $_GET['stock_id'];
    try {
        $stmt = $con->prepare("DELETE FROM stock WHERE stock_id = :id");
        $stmt->execute([':id' => $id]);
        
        // Redirect back to where they came from
        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header("Location: " . $referer);
        exit();
    } catch (PDOException $e) {
        die("Error deleting stock: " . htmlspecialchars($e->getMessage()));
    }
} else {
    header("location:index.php");
    exit();
}
?>