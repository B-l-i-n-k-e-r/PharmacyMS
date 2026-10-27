<?php

// Suppress notices and warnings to match your original error reporting configuration
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

$host    = 'localhost';
$db      = 'pharmacyms';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Establishing a persistent connection via PDO
    $options[PDO::ATTR_PERSISTENT] = true;
    $con = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Fallback error messaging matching your original logic
    die("cannot connect to server or database: " . $e->getMessage());
}

?>