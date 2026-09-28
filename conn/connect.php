<?php 

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "admin1"; 

try {
    // Establish connection with UTF-8 encoding
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO error mode to exception for better debugging
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Show user-friendly error message (avoid exposing sensitive details)
    die("<b>❌ Database Connection Failed:</b> " . $e->getMessage());
}

?>
