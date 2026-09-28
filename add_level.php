<?php
session_start();
require_once './conn/connect.php';

// Check if POST values exist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Optional: Add CSRF token check here if you implement it in the form

    // Basic validation
    $level_number = filter_input(INPUT_POST, 'level_number', FILTER_VALIDATE_INT);
    $code_text = trim($_POST['code_text']);
    $time_limit = filter_input(INPUT_POST, 'time_limit', FILTER_VALIDATE_INT);
    $coin_reward = filter_input(INPUT_POST, 'coin_reward', FILTER_VALIDATE_FLOAT);

    if (!$level_number || !$time_limit || !$coin_reward || empty($code_text)) {
        die('Invalid input');
    }

    // Prepare insert
    $stmt = $conn->prepare("INSERT INTO typing_levels (level_number, code_text, time_limit, coin_reward) VALUES (?, ?, ?, ?)");
    $stmt->execute([$level_number, $code_text, $time_limit, $coin_reward]);

    // Optional: redirect back with success message
    header("Location: test.php?success=1");
    exit();
} else {
    header("Location: test.php");
    exit();
}
?>
