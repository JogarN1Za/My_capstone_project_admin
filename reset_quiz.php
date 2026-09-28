<?php

include ('conn/connect.php');

session_start();

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $quiz_id = intval($_POST['quiz_id']);

    if ($user_id <= 0 || $quiz_id <= 0) {
        die("❌ Invalid user or quiz ID.");
    }

    try {
        // ✅ Insert notification for the user
        $stmt4 = $conn->prepare("INSERT INTO notifications (user_id, message, status) VALUES (:user_id, :message, 'unread')");
        $message = "Your reported quiz (ID: $quiz_id) has been reset. You can retake it now.";
        $stmt4->execute([
            ':user_id' => $user_id,
            ':message' => $message
        ]);

        // ✅ Delete progress record for this user and quiz
        $stmt = $conn->prepare("DELETE FROM progress WHERE user_id = :user_id AND part_id = :quiz_id");
        $stmt->execute([
            ':user_id' => $user_id,
            ':quiz_id' => $quiz_id
        ]);

        // ✅ Clear the record in quiz_progress for this user and quiz
        $stmt2 = $conn->prepare("DELETE FROM quiz_progress WHERE user_id = :user_id AND quiz_id = :quiz_id");
        $stmt2->execute([
            ':user_id' => $user_id,
            ':quiz_id' => $quiz_id
        ]);

        // ✅ Clear the record in quiz_attempts for this user and quiz (IMPORTANT!)
        $stmt3 = $conn->prepare("DELETE FROM quiz_attempts WHERE user_id = :user_id AND quiz_id = :quiz_id");
        $stmt3->execute([
            ':user_id' => $user_id,
            ':quiz_id' => $quiz_id
        ]);

        if ($stmt->rowCount() > 0 || $stmt2->rowCount() > 0 || $stmt3->rowCount() > 0) {
            header("Location: notifications.php?reset_success=1");
        } else {
            header("Location: notifications.php?reset_success=0&error=No matching progress found.");
        }
        exit();
    } catch (PDOException $e) {
        die("❌ Error: " . $e->getMessage());
    }
}