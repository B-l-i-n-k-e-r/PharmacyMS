<?php
session_start();
include_once('connect_db.php');

if(!isset($_SESSION['username'])){
    header("location:index.php");
    exit();
}

if(isset($_GET['pharmacist_id']) && is_numeric($_GET['pharmacist_id'])) {
    $id = $_GET['pharmacist_id'];
    try {
        $stmt = $con->prepare("DELETE FROM pharmacist WHERE pharmacist_id = :id");
        $stmt->execute([':id' => $id]);
        header("Location: admin_pharmacist.php");
        exit();
    } catch (PDOException $e) {
        die("Error: " . htmlspecialchars($e->getMessage()));
    }
}
header("location:index.php");
?>