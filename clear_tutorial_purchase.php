<?php
include('conn/connect.php'); // Include your database connection

// Ensure the user is an admin (you'll need to implement proper admin authentication)
// For example, check if $_SESSION['admin_logged_in'] is set to true

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_id'])) {
    $purchaseId = filter_input(INPUT_POST, 'purchase_id', FILTER_SANITIZE_NUMBER_INT);

    if ($purchaseId) {
        try {
            // Assuming you want to add a 'cleared' column to your 'tbl_purchase' table
            // ALTER TABLE tbl_purchase ADD COLUMN cleared TINYINT(1) DEFAULT 0;

            $stmt = $conn->prepare("UPDATE tbl_purchase SET cleared = 1 WHERE purchase_id = :id");
            $stmt->execute([':id' => $purchaseId]);

            // Redirect back to the notifications page with a success message
            header("Location: notifications.php?tutorial_purchase_cleared=1");
            exit();

        } catch (PDOException $e) {
            // Handle database error (log it, show an error message)
            error_log("Error clearing tutorial purchase: " . $e->getMessage());
            header("Location: notifications.php?tutorial_purchase_cleared=0&error=" . urlencode("Error clearing tutorial purchase."));
            exit();
        }
    } else {
        // Invalid purchase ID
        header("Location: notifications.php?tutorial_purchase_cleared=0&error=" . urlencode("Invalid tutorial purchase ID."));
        exit();
    }
} else {
    // Invalid request
    header("Location: notifications.php"); // Redirect back to notifications page
    exit();
}
?>