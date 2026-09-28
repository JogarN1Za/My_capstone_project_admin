<?php
include('conn/connect.php'); // Include your database connection

// Ensure the user is an admin (you'll need to implement proper admin authentication)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_id'])) {
    $purchaseId = filter_input(INPUT_POST, 'purchase_id', FILTER_SANITIZE_NUMBER_INT);

    if ($purchaseId) {
        try {
            $stmt = $conn->prepare("UPDATE purchases SET cleared = 1 WHERE id = :id");
            $stmt->execute([':id' => $purchaseId]);

            // Redirect back to the notifications page with a success message
            header("Location: notifications.php?purchase_cleared=1");
            exit();

        } catch (PDOException $e) {
            // Handle database error (log it, show an error message)
            error_log("Error clearing purchase: " . $e->getMessage());
            header("Location: notifications.php?purchase_cleared=0&error=" . urlencode("Error clearing purchase."));
            exit();
        }
    } else {
        // Invalid purchase ID
        header("Location: notifications.php?purchase_cleared=0&error=" . urlencode("Invalid purchase ID."));
        exit();
    }
} else {
    // Invalid request
    header("Location: notifications.php"); // Redirect back to notifications page
    exit();
}
?>