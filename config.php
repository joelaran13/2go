<?php
$host = 'localhost';
$dbname = 'campus_eats';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (leave empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

session_start();
?>