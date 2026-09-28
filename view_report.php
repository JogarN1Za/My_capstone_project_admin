<?php
include ('./conn/connect.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id'])) {
    header("Location: notifications.php");
    exit();
}

$reportId = $_GET['id'];

// Mark the report as "Seen"
$stmt = $conn->prepare("UPDATE reports SET status = 'Seen' WHERE id = :id");
$stmt->execute([':id' => $reportId]);

// Fetch report details
$stmt = $conn->prepare("SELECT * FROM reports WHERE id = :id");
$stmt->execute([':id' => $reportId]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    header("Location: notifications.php");
    exit();
}

$imagePaths = json_decode($report['image_paths'], true);
if (!is_array($imagePaths)) {
    $imagePaths = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Report Details</h2>
        <p><strong>User ID:</strong> <?= htmlspecialchars($report['user_id']); ?></p>
        <p><strong>Comment:</strong> <?= htmlspecialchars($report['comment']); ?></p>
        <p><strong>Submitted At:</strong> <?= htmlspecialchars($report['created_at']); ?></p>

        <?php if (!empty($imagePaths)): ?>
            <div class="image-preview mt-4">
                <h4>Uploaded Images:</h4>
                <?php foreach ($imagePaths as $imagePath): ?>
                    <?php 
                    $fullPath = 'uploads/' . htmlspecialchars(trim($imagePath)); 

                    ?>
                    <?php if (file_exists($fullPath)): ?>
                        <img src="<?= $fullPath; ?>" alt="Uploaded Image" class="img-fluid mb-3" style="max-width: 100%; height: auto;">
                    <?php else: ?>
                        <p style="color: red;">Image not found: <?= htmlspecialchars($fullPath); ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p><strong>No images uploaded.</strong></p>
        <?php endif; ?>

        <a href="notifications.php" class="btn btn-secondary mt-3">Back to Notifications</a>
    </div>
</body>
</html>
