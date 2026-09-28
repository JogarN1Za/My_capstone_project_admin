<?php
// Database connection
$servername = "localhost"; // Usually 'localhost' for XAMPP
$username = "root"; // Default username for XAMPP
$password = ""; // Default password is usually empty for XAMPP
$dbname = "admin"; // Replace with your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$title = $_POST['title'];
$description = $_POST['description'];

// SQL to insert data into the table
$sql = "INSERT INTO certificates (title, description) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $title, $description);

if ($stmt->execute()) {
    echo "Card generated successfully!";
    // Optionally display the card
    echo "<h2>$title</h2>";
    echo "<p>$description</p>";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
