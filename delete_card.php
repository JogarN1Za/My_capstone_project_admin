<?php
// filepath: c:\xampp\htdocs\WebDev\admin\delete_card.php
session_start();
include('./conn/connect.php'); // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_id'])) {
    $card_id = (int)$_POST['card_id'];

    // Delete card
    $stmt = $conn->prepare("DELETE FROM cards WHERE id = ?");
    if ($stmt->execute([$card_id])) {
        $_SESSION['success'] = "Card deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete the card.";
    }
}

header("Location: messages.php");
exit();