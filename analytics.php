<?php
include('conn/connect.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Function to fetch and sort grade levels
function getSortedGradeLevels($conn) {
    $sql = "SELECT year_level FROM grades GROUP BY year_level ORDER BY FIELD(year_level, '1st', '2nd', '3rd', '4th')";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $results;
}

// Function to fetch and sort section names
function getSortedSectionNames($conn) {
    $sql = "SELECT section_name FROM sections GROUP BY section_name ORDER BY FIELD(section_name, 'A', 'B', 'C', 'D')";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $results;
}

// --- Quiz Performance Trends Over Time ---
function getAverageQuizScoreOverTime($conn, $period = 'WEEK') {
    $sql = "SELECT
                    DATE_FORMAT(attempt_date, :date_format) AS time_period,
                    AVG(score) AS average_score
                FROM
                    quiz_attempts
                GROUP BY
                    time_period
                ORDER BY
                    time_period";
    $stmt = $conn->prepare($sql);
    $dateFormat = ($period === 'WEEK') ? '%Y-%u' : (($period === 'MONTH') ? '%Y-%m' : '%Y-%m-%d');
    $stmt->bindParam(':date_format', $dateFormat, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get sorted grade levels and section names
$gradeLevels = getSortedGradeLevels($conn);
$sectionNames = getSortedSectionNames($conn);

$gradeScoreData = [];
foreach ($gradeLevels as $grade) {
    $sql = "SELECT AVG(a.score) AS average_score
                    FROM admin_tb a
                    INNER JOIN grades g ON a.year_id = g.id
                    WHERE g.year_level = :grade
                    GROUP BY g.year_level";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':grade', $grade);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $gradeScoreData[$grade] = $result ? round($result['average_score'], 2) : null;
}
$gradeLabelsScoreJSON = json_encode(array_keys($gradeScoreData));
$gradeDataScoreJSON = json_encode(array_values($gradeScoreData));

$gradeCoinsData = [];
foreach ($gradeLevels as $grade) {
    $sql = "SELECT AVG(a.coins) AS average_coins
                    FROM admin_tb a
                    INNER JOIN grades g ON a.year_id = g.id
                    WHERE g.year_level = :grade
                    GROUP BY g.year_level";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':grade', $grade);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $gradeCoinsData[$grade] = $result ? round($result['average_coins'], 2) : null;
}
$gradeLabelsCoinsJSON = json_encode(array_keys($gradeCoinsData));
$gradeDataCoinsJSON = json_encode(array_values($gradeCoinsData));

$sectionScoreData = [];
foreach ($sectionNames as $section) {
    $sql = "SELECT AVG(a.score) AS average_score
                    FROM admin_tb a
                    INNER JOIN sections s ON a.sec_id = s.id
                    WHERE s.section_name = :section
                    GROUP BY s.section_name";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':section', $section);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $sectionScoreData[$section] = $result ? round($result['average_score'], 2) : null;
}
$sectionLabelsScoreJSON = json_encode(array_keys($sectionScoreData));
$sectionDataScoreJSON = json_encode(array_values($sectionScoreData));

$sectionCoinsData = [];
foreach ($sectionNames as $section) {
    $sql = "SELECT AVG(a.coins) AS average_coins
                    FROM admin_tb a
                    INNER JOIN sections s ON a.sec_id = s.id
                    WHERE s.section_name = :section
                    GROUP BY s.section_name";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':section', $section);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $sectionCoinsData[$section] = $result ? round($result['average_coins'], 2) : null;
}
$sectionLabelsCoinsJSON = json_encode(array_keys($sectionCoinsData));
$sectionDataCoinsJSON = json_encode(array_values($sectionCoinsData));

// Fetching weekly and monthly quiz scores
$weeklyQuizScores = getAverageQuizScoreOverTime($conn, 'WEEK');
$weeklyQuizScoreLabels = array_map(function($item) {
    $date = new DateTime(date('Y-m-d', strtotime("{$item['time_period']} Sunday")));
    return 'Week of ' . $date->format('Y-m-d');
}, $weeklyQuizScores);
$weeklyQuizScoreData = array_column($weeklyQuizScores, 'average_score');

$monthlyQuizScores = getAverageQuizScoreOverTime($conn, 'MONTH');
$monthlyQuizScoreLabels = array_map(function($item) {
    return $item['time_period'] . '-01';
}, $monthlyQuizScores);
$monthlyQuizScoreData = array_column($monthlyQuizScores, 'average_score');
// Fetch data for score and coins by year level
$stmtCombined = $conn->prepare("
    SELECT g.year_level, SUM(a.score) AS total_score, SUM(a.coins) AS total_coins
    FROM admin_tb a
    JOIN grades g ON a.year_id = g.id
    GROUP BY g.year_level
    ORDER BY g.year_level
");
$stmtCombined->execute();
$combinedData = $stmtCombined->fetchAll(PDO::FETCH_ASSOC);

// Calculate overall maximum score and coins
$maxScoreOverall = 0;
$maxCoinsOverall = 0;
foreach ($combinedData as $row) {
    $maxScoreOverall = max($maxScoreOverall, $row['total_score']);
    $maxCoinsOverall = max($maxCoinsOverall, $row['total_coins']);
}
// Fetch data grouped by year level and section
$stmt = $conn->prepare("
    SELECT
        g.year_level,
        s.section_name,
        SUM(a.score) AS total_score,
        SUM(a.coins) AS total_coins
    FROM admin_tb a
    JOIN grades g ON a.year_id = g.id
    JOIN sections s ON a.sec_id = s.id
    GROUP BY g.year_level, s.section_name
    ORDER BY g.year_level, s.section_name
");
$stmt->execute();
$dataByYearSection = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Structure the data for JavaScript
$chartData = [];
foreach ($dataByYearSection as $row) {
    $yearLevel = $row['year_level'];
    $sectionName = $row['section_name'];
    $totalScore = $row['total_score'];
    $totalCoins = $row['total_coins'];

    if (!isset($chartData[$yearLevel])) {
        $chartData[$yearLevel] = ['sections' => [], 'maxScore' => 0, 'maxCoins' => 0];
    }
    $chartData[$yearLevel]['sections'][$sectionName] = ['score' => $totalScore, 'coins' => $totalCoins];
    $chartData[$yearLevel]['maxScore'] = max($chartData[$yearLevel]['maxScore'], $totalScore);
    $chartData[$yearLevel]['maxCoins'] = max($chartData[$yearLevel]['maxCoins'], $totalCoins);
}
// Prepare data for the combined pie chart
$combinedLabels = [];
$combinedPercentages = [];
$backgroundColors = [];
$borderColors = [];
$colorPalette = [
    'rgba(255, 99, 132, 0.7)',
    'rgba(54, 162, 235, 0.7)',
    'rgba(255, 206, 86, 0.7)',
    'rgba(75, 192, 192, 0.7)',
    'rgba(153, 102, 255, 0.7)',
    'rgba(255, 159, 64, 0.7)',
    'rgba(128, 0, 128, 0.7)', // Purple
    'rgba(0, 128, 0, 0.7)',   // Green
    'rgba(0, 0, 128, 0.7)',   // Navy
    'rgba(255, 215, 0, 0.7)'   // Gold
];
$borderColorPalette = [
    'rgba(255, 99, 132, 1)',
    'rgba(54, 162, 235, 1)',
    'rgba(255, 206, 86, 1)',
    'rgba(75, 192, 192, 1)',
    'rgba(153, 102, 255, 1)',
    'rgba(255, 159, 64, 1)',
    'rgba(128, 0, 128, 1)',
    'rgba(0, 128, 0, 1)',
    'rgba(0, 0, 128, 1)',
    'rgba(255, 215, 0, 1)'
];
$colorIndex = 0;

foreach ($combinedData as $row) {
    // Normalize score and coins to a 0-1 range
    $normalizedScore = ($maxScoreOverall > 0) ? ($row['total_score'] / $maxScoreOverall) : 0;
    $normalizedCoins = ($maxCoinsOverall > 0) ? ($row['total_coins'] / $maxCoinsOverall) : 0;

    // You can decide how to combine them. Here's a simple average:
    $combinedValue = ($normalizedScore + $normalizedCoins) / 2;

    $combinedLabels[] = $row['year_level'] . " (Norm. Score: " . round($normalizedScore, 2) . ", Norm. Coins: " . round($normalizedCoins, 2) . ")";
    $combinedPercentages[] = $combinedValue;
    $backgroundColors[] = $colorPalette[$colorIndex % count($colorPalette)];
    $borderColors[] = $borderColorPalette[$colorIndex % count($borderColorPalette)];
    $colorIndex++;
}

// Calculate the sum of combined values to get the total "whole"
$totalCombinedValue = array_sum($combinedPercentages);

// Convert combined values to percentages of the total combined value
$finalPercentages = [];
foreach ($combinedPercentages as $value) {
    $finalPercentages[] = ($totalCombinedValue > 0) ? round(($value / $totalCombinedValue) * 100, 2) : 0;
}

// Fetch the count of pending notifications
$stmt = $conn->prepare("SELECT COUNT(*) FROM reports WHERE status = 'Pending'");
$stmt->execute();
$pendingTotalCount = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin New Design</title>
    <link rel="stylesheet" href="#" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            background-color: #0a0b0c;
            color: #e0e0e0;
        }

        nav.top-nav {
            background-color:#1f2937;
            height: 55px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            position: fixed;
            top: 20px;
            left: 0;
            width: 100%;
            z-index: 100;
            border-bottom: 1px solid #334155;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .menu-icon {
            color: #64748b;
            font-size: 1.3em;
            cursor: pointer;
            display: none;
        }

        .logo-name {
            font-size: 1.1em;
            font-weight: bold;
            color: #f8fafc;
            margin-left: 10px;
        }

        .sidebar {
            position: fixed;
            top: 75px;
            left: 0;
            width: 240px;
            height: calc(100vh - 75px);
            background-color: #1e293b;
            color: #e0e0e0;
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
            z-index: 300;
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-content {
            padding: 15px;
            overflow-y: auto;
            height: 100%;
        }

        .tutorials-heading {
            font-size: 0.9em;
            font-weight: bold;
            margin-bottom: 15px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            padding-left: 5px;
        }

        .lists {
            list-style: none;
            padding: 0;
            margin: 0 0 15px 0;
        }

        .list-group-heading {
            font-weight: bold;
            color: #cbd5e1;
            padding: 8px 15px;
            margin-bottom: 3px;
            font-size: 0.9em;
        }
        .list-group-heading .nav-link {
            font-size: 14px;
            padding: 12px 20px;
            font-weight: bold;
            color: #ffffff;
            display: block;
        }
        .list-item {
            margin-bottom: 3px;
        }

        .nav-link {
            display: block;
            color: #a3a3a3;
            text-decoration: none;
            padding: 7px 20px;
            border-radius: 6px;
            transition: background-color 0.2s ease;
            font-size: 0.85em;
        }

        .nav-link:hover {
            background-color: #334155;
            color: #f0f0f0;
        }

        .main {
            padding: 40px 20px;
            padding-top: 75px;
            background-color: #121827;
            color:#727883;
            min-height: 100vh;
            transition: margin-left 0.3s ease-in-out;
        }

        .main > div {
            padding: 20px;
            background-color: #121827;
            border-radius: 4px;
        }

        nav.additional-nav {
            background-color: #374151;
            height: 20px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 101;
        }

        nav.
        additional-nav .block {
            display: none;
        }

        .sidebar-backdrop {
            position: fixed;
            top: 75px;
            left: 0;
            width: 100%;
            height: calc(100vh - 75px);
            background: #111827;
            z-index: 250;
            display: none;
        }

        .sidebar-backdrop.show {
            display: block;
        }

        /* Right Nav Section */
        .right-icons {
            display: flex;
            align-items: center;
            position: relative;
        }

        .right-links-desktop {
            display: flex;
            gap: 10px;
        }

        .right-icons a {
            color: #64748b;
            text-decoration: none;
            margin-left: 10px;
            font-size: 0.9em;
            transition: color 0.2s ease;
        }

        .right-icons a:hover {
            color: #f8fafc;
        }

        .dropdown-toggle-icon {
            color: #f0f0f0;
            font-size: 1.5em;
            cursor: pointer;
            display: none;
            margin-left: 10px;
        }
        .right-icons i {
            font-size: 24px; /* Change to your desired size */
        }

        .right-dropdown {
            position: absolute;
            right: 40px;
            top: 45px;
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 4px;
            display: none;
            flex-direction: column;
            width: 180px;
            z-index: 999;
        }

        .right-dropdown a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 10px;
            border-bottom: 1px solid #334155;
            display: block;
            font-size: 0.9em;
        }

        .right-dropdown a:hover {
            background-color: #334155;
        }

        .right-dropdown.show {
            display: flex;
        }

        /* Responsive Adjustments */
        @media (min-width: 769px) {
            .right-links-desktop {
                display: flex;
            }

            .dropdown-toggle-icon,
            .right-dropdown {
                display: none !important;
            }

            .sidebar {
                transform: translateX(0) !important;
            }

            .main {
                margin-left: 240px;
            }

            .menu-icon {
                display: none !important;
            }

            .sidebar-backdrop {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .right-links-desktop {
                display: none;
            }

            .dropdown-toggle-icon {
                display: block;
            }

            .menu-icon {
                display: block;
                margin-right: 10px;
            }

            .main {
                margin-left: 0;
                width: 100%;
            }
        }

        /* Styles for the category tiles */
        .container-fluid {
            padding: 20px;
        }

        .card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #334155;
            color: #f8fafc;
            padding: 15px;
            border-bottom: 1px solid #475569;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.1em;
            font-weight: bold;
        }

        .card-body {
            padding: 20px;
        }

        canvas {
            width: 100% !important;
            max-height: 400px;
        }

        h2 {
            color: #cbd5e1;
            margin-bottom: 20px;
            border-bottom: 2px solid #475569;
            padding-bottom: 10px;
        }

        h3 {
            color:rgb(255, 255, 255);
            margin-top: 25px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-weight: bold;
            color: #e2e8f0;
            display: block;
            margin-bottom: 5px;
        }

        .form-control-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
            border: 1px solid #475569;
            background-color: #1e293b;
            color: #e0e0e0;
        }

        .table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #334155;
        }

        .table th, .table td {
            padding: 10px 12px;
            border: 1px solid #475569;
            text-align: left;
        }

        .table thead th {
            background-color: #475569;
            color: #f8fafc;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        .table tbody tr:nth-child(even) {
            background-color: #27374d;
        }

        .mt-4 {
            margin-top: 1.5rem !important;
        }

        .mt-5 {
            margin-top: 2rem !important;
        }

        .mb-4 {
            margin-bottom: 1.5rem !important;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        
.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: red;
    color: white;
    border-radius: 50%;
    padding: 4px 6px;
    font-size: 0.7em;
}
    </style>
</head>
<body>

<nav class="additional-nav"><div class="block"></div></nav>

<nav class="top-nav">
    <div class="logo">
        <i class="bx bx-menu menu-icon"></i>
        <span class="logo-name">Web.Dev.</span>
    </div>

    <div class="right-icons">
        <div class="right-links-desktop">
            <a href="messages.php">Create Cards</a>
            <a href="users.php">Add User</a>
            <a href="test.php">Create Game Levels</a>
        </div>

        <i class="bx bx-dots-vertical-rounded dropdown-toggle-icon"></i>

        <div class="right-dropdown">
            <a href="messages.php">Create Cards</a>
            <a href="users.php">Add User</a>
            <a href="test.php">Create Game Levels</a>
        </div>

        <a href="logout.php"><i class='bx bx-log-out'></i></a>
        <a href="notifications.php" style="position: relative;">
            <i class='bx bx-bell'></i>
            <?php if ($pendingTotalCount > 0): ?>
                <span class="notification-badge"><?= $pendingTotalCount ?></span>
            <?php endif; ?>
        </a>
        <a href="settings.php"><i class='bx bx-user-circle'></i></a>
    </div>
</nav>

<div class="sidebar-backdrop"></div>

<div class="sidebar">
    <div class="sidebar-content">
    <ul class="lists">
    <div class="tutorials-heading">Admin_Name</div>
    <li class="list-group-heading">Dashboard</li>
         <li class="list-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
         <li class="list-item"><a href="analytics.php" class="nav-link">Analytics</a></li>
     </ul>
     <ul class="lists">
         <li class="list-group-heading">Categories</li>
         <li class="list-item"><a href="category.php" class="nav-link">Create Category</a></li>
         <li class="list-item"><a href="part.php" class="nav-link">Create Part</a></li>
         <li class="list-item"><a href="dificulty.php" class="nav-link">Create Difficulty</a></li>
     </ul>
     <ul class="lists">
         <li class="list-group-heading">Create Tutorials</li>
         <li class="list-item"><a href="code_edit.php" class="nav-link">Easy</a></li>
         <li class="list-item"><a href="miduem_edit.php" class="nav-link">Medium</a></li>
         <li class="list-item"><a href="hard_edit.php" class="nav-link">Hard</a></li>
     </ul>
     <ul class="lists">
         <li class="list-group-heading">Create Assessment</li>
         <li class="list-item"><a href="ass_tut.php" class="nav-link">Easy</a></li>
         <li class="list-item"><a href="medium_ass.php" class="nav-link">Medium</a></li>
         <li class="list-item"><a href="hard_ass.php" class="nav-link">Hard</a></li>
     </ul>
    </div>
</div>

<main class="main">
    <div>
        <h2>Analytics Dashboard</h2>
        <p>Here you can find insights and statistics about the platform.</p>
        <hr class="mb-4">

        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h3>Combined Normalized Score and Coins by Year Level (Percentage)</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="combinedPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <?php foreach ($chartData as $yearLevel => $yearData): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h3>Combined Score and Coins by Section - Year Level: <?php echo htmlspecialchars($yearLevel); ?></h3>
                            </div>
                            <div class="card-body">
                                <canvas id="combinedPieChart_<?php echo htmlspecialchars(str_replace([' ', '.'], '_', $yearLevel)); ?>"></canvas>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="container mt-4">
            <h2>General Analytics</h2>

            <div class="mt-5">
                <h3>Average Quiz Score Trend Over Time</h3>
                <div class="form-group">
                    <label for="quizScoreTimePeriod">Select Time Period:</label>
                    <select id="quizScoreTimePeriod" class="form-control form-control-sm">
                        <option value="weekly" selected>Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <div>
                    <canvas id="quizScoreTrendChart" style="height: 350px;"></canvas>
                </div>
            </div>
        </div>

        <div class="container mt-4">
            <h2>Grade Level Analytics</h2>

            <div class="mt-4">
                <h3>Average Progress Score by Grade Level</h3>
                <div>
                    <canvas id="gradeLevelChartScore"></canvas>
                </div>
                <div class="mt-3">
                    <table class='table table-bordered'>
                        <thead class='thead-light'>
                            <tr><th>Grade Level</th><th>Average Score</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gradeLevels as $grade): ?>
                                <tr>
                                    <td style="color: white;"><?php echo $grade; ?></td>
                                    <td style="color: white;"><?php echo $gradeScoreData[$grade] ?? 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5">
                <h3>Average Coins by Grade Level</h3>
                <div>
                    <canvas id="gradeLevelChartCoins"></canvas>
                </div>
                <div class="mt-3">
                    <table class='table table-bordered'>
                        <thead class='thead-light'>
                            <tr><th>Grade Level</th><th>Average Coins</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($gradeLevels as $grade): ?>
                                <tr>
                                    <td style="color: white;"><?php echo $grade; ?></td>
                                    <td style="color: white;"><?php echo $gradeCoinsData[$grade] ?? 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="container mt-4">
            <h2>Section Analytics</h2>

            <div class="mt-5">
                <h3>Average Progress Score by Section</h3>
                <div>
                    <canvas id="sectionChartScore"></canvas>
                </div>
                <div class="mt-3">
                    <table class='table table-bordered'>
                        <thead class='thead-light'>
                            <tr><th>Section</th><th>Average Score</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sectionNames as $section): ?>
                                <tr>
                                    <td style="color: white;"><?php echo $section; ?></td>
                                    <td style="color: white;"><?php echo $sectionScoreData[$section] ?? 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5">
                <h3>Average Coins by Section</h3>
                <div>
                    <canvas id="sectionChartCoins"></canvas>
                </div>
                <div class="mt-3">
                    <table class='table table-bordered'>
                        <thead class='thead-light'>
                            <tr><th>Section</th><th>Average Coins</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sectionNames as $section): ?>
                                <tr>
                                    <td style="color: white;"><?php echo $section; ?></td>
                                    <td style="color: white;"><?php echo $sectionCoinsData[$section] ?? 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Sidebar toggle
    const menuIcon = document.querySelector('.menu-icon');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');

    menuIcon.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : 'auto';
    });

    backdrop.addEventListener('click', () => {
        sidebar.classList.remove('open');
        backdrop.classList.remove('show');
        document.body.style.overflow = 'auto';
    });

    // Right nav dropdown
    const dropdownToggle = document.querySelector('.dropdown-toggle-icon');
    const mobileDropdown = document.querySelector('.right-dropdown');

    dropdownToggle.addEventListener('click', () => {
        mobileDropdown.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
        if (!dropdownToggle.contains(e.target) && !mobileDropdown.contains(e.target)) {
            mobileDropdown.classList.remove('show');
        }
    });

    // Function to create a chart
    function createChart(canvasId, chartType, labels, data, backgroundColor, borderColor, label) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        return new Chart(ctx, {
            type: chartType,
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    backgroundColor: backgroundColor,
                    borderColor: borderColor,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: label.includes('Score') ? 'Average Score' : 'Average Coins'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: labels.includes('Grade') ? 'Grade Level' : 'Section'
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // Chart for Grade Level - Score
    createChart('gradeLevelChartScore', 'bar', <?php echo $gradeLabelsScoreJSON; ?>, <?php echo $gradeDataScoreJSON; ?>, 'rgba(54, 162, 235, 0.8)', 'rgba(54, 162, 235, 1)', 'Average Progress Score');

    // Chart for Grade Level - Coins
    createChart('gradeLevelChartCoins', 'bar', <?php echo $gradeLabelsCoinsJSON; ?>, <?php echo $gradeDataCoinsJSON; ?>, 'rgba(255, 206, 86, 0.8)', 'rgba(255, 206, 86, 1)', 'Average Coins');

    // Chart for Section - Score
    createChart('sectionChartScore', 'bar', <?php echo $sectionLabelsScoreJSON; ?>, <?php echo $sectionDataScoreJSON; ?>, 'rgba(255, 99, 132, 0.8)', 'rgba(255, 99, 132, 1)', 'Average Progress Score');

    // Chart for Section - Coins
    createChart('sectionChartCoins', 'bar', <?php echo $sectionLabelsCoinsJSON; ?>, <?php echo $sectionDataCoinsJSON; ?>, 'rgba(75, 192, 192, 0.8)', 'rgba(75, 192, 192, 1)', 'Average Coins');

    // Chart for Quiz Score Trend Over Time
    const quizScoreTrendCanvas = document.getElementById('quizScoreTrendChart');
    let quizScoreTrendChart;

    function updateQuizScoreTrendChart(labels, data) {
        if (quizScoreTrendChart) {
            quizScoreTrendChart.destroy();
        }
        quizScoreTrendChart = new Chart(quizScoreTrendCanvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average Quiz Score',
                    data: data,
                    borderColor: 'rgba(153, 102, 255, 1)', // Purple color
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Average Score'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Time Period'
                        }
                    }
                }
            }
        });
    }

    const weeklyQuizScoreLabels = <?php echo json_encode($weeklyQuizScoreLabels); ?>;
    const weeklyQuizScoreData = <?php echo json_encode($weeklyQuizScoreData); ?>;
    const monthlyQuizScoreLabels = <?php echo json_encode($monthlyQuizScoreLabels); ?>;
    const monthlyQuizScoreData = <?php echo json_encode($monthlyQuizScoreData); ?>;

    const quizScoreTimePeriodSelect = document.getElementById('quizScoreTimePeriod');
    quizScoreTimePeriodSelect.addEventListener('change', function() {
        if (this.value === 'weekly') {
            updateQuizScoreTrendChart(weeklyQuizScoreLabels, weeklyQuizScoreData);
        } else {
            updateQuizScoreTrendChart(monthlyQuizScoreLabels, monthlyQuizScoreData);
        }
    });

    // Initial chart setup
    updateQuizScoreTrendChart(weeklyQuizScoreLabels, weeklyQuizScoreData);

    // Combined Pie Chart for Year Levels
    const combinedCtx = document.getElementById('combinedPieChart').getContext('2d');
    new Chart(combinedCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($combinedLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($finalPercentages); ?>,
                backgroundColor: <?php echo json_encode($backgroundColors); ?>,
                borderColor: <?php echo json_encode($borderColors); ?>,
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (context.parsed !== null) {
                                label += ': ' + context.parsed + '%';
                            }
                            return label;
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Percentage of Combined Normalized Score and Coins by Year Level'
                }
            }
        }
    });

    const chartDataPHP = <?php echo json_encode($chartData); ?>;

    for (const yearLevel in chartDataPHP) {
        const canvasId = `combinedPieChart_${yearLevel.replace(/[\s.]/g, '_')}`;
        const yearData = chartDataPHP[yearLevel].sections;
        const maxScore = chartDataPHP[yearLevel].maxScore;
        const maxCoins = chartDataPHP[yearLevel].maxCoins;

        const labels = Object.keys(yearData);
        const combinedPercentages = Object.values(yearData).map(section => {
            const normalizedScore = maxScore > 0 ? section.score / maxScore : 0;
            const normalizedCoins = maxCoins > 0 ? section.coins / maxCoins : 0;
            return (normalizedScore + normalizedCoins) / 2;
        });

        const backgroundColors = [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(128, 0, 128, 0.7)',
            'rgba(0, 128, 0, 0.7)',
            'rgba(0, 0, 128, 0.7)',
            'rgba(255, 215, 0, 0.7)'
        ].slice(0, labels.length);
        const borderColors = [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)',
            'rgba(128, 0, 128, 1)',
            'rgba(0, 128, 0, 1)',
            'rgba(0, 0, 128, 1)',
            'rgba(255, 215, 0, 1)'
        ].slice(0, labels.length);

        const ctx = document.getElementById(canvasId)?.getContext('2d');
        if (ctx) {
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: combinedPercentages,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 1,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: `Combined Normalized Score and Coins by Section - ${yearLevel}`
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (context.parsed !== null) {
                                        label += ': ' + (context.parsed * 100).toFixed(2) + '%';
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
    }
</script>

</body>
</html>

<?php $conn = null; ?>