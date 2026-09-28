<?php
include('./conn/connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['level_id'];
    $level = $_POST['level_number'];
    $code = $_POST['code_text'];
    $time = $_POST['time_limit'];
    $reward = $_POST['coin_reward'];

    $stmt = $conn->prepare("UPDATE typing_levels SET level_number = ?, code_text = ?, time_limit = ?, coin_reward = ? WHERE id = ?");
    $stmt->execute([$level, $code, $time, $reward, $id]);

    header("Location: test.php");
    exit();
}
?>
