<?php
include('conn/connect.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

try {
    // ✅ Fetch all online students
    $stmt = $conn->prepare("SELECT tbl_user_id, first_name, last_name, username FROM admin_tb WHERE is_online = 1");
    $stmt->execute();
    $online_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Fetch total students
    $stmt = $conn->prepare("SELECT COUNT(tbl_user_id) AS total_students FROM admin_tb");
    $stmt->execute();
    $total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

    // ✅ Fetch total coins
    $stmt = $conn->prepare("SELECT SUM(coins) AS total_coins FROM admin_tb");
    $stmt->execute();
    $total_coins = $stmt->fetch(PDO::FETCH_ASSOC)['total_coins'] ?? 0;

    // ✅ Fetch total score
    $stmt = $conn->prepare("SELECT SUM(score) AS total_score FROM admin_tb");
    $stmt->execute();
    $total_score = $stmt->fetch(PDO::FETCH_ASSOC)['total_score'] ?? 0;

    // ✅ Fetch user with highest coins
    $stmt = $conn->prepare("SELECT tbl_user_id, first_name, last_name, coins FROM admin_tb ORDER BY coins DESC LIMIT 1");
    $stmt->execute();
    $highest_coin_user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ✅ Fetch user with highest score
    $stmt = $conn->prepare("SELECT tbl_user_id, first_name, last_name, score FROM admin_tb ORDER BY score DESC LIMIT 1");
    $stmt->execute();
    $highest_score_user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ✅ Fetch all users for the user list table
    $stmt = $conn->prepare("SELECT tbl_user_id, first_name, last_name, username FROM admin_tb");
    $stmt->execute();
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Check if the `reports` table exists before querying
    $stmt = $conn->prepare("SHOW TABLES LIKE 'reports'");
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // ✅ Fetch pending reports using the `comment` column
        $stmt = $conn->prepare("SELECT id, comment FROM reports WHERE status = 'Pending'");
        $stmt->execute();
        $pending_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $pending_reports = []; // ✅ If table doesn't exist, return an empty array
    }
} catch (PDOException $e) {
    die("❌ Database Error: " . $e->getMessage());
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
            font-size: 14px;         /* Palakihin ang text */
            padding: 12px 20px;         /* Palakihin ang click area */
            font-weight: bold;         /* Gawing bold */
            color: #ffffff;             /* Pwede mo rin i-adjust color kung gusto mo */
            display: block;             /* Para mas maging buong linya ang clickable */
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

        nav.additional-nav .block {
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
        .right-icons i {
            font-size: 24px; /* Change to your desired size */
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

        /* Modern Dashboard Styling */
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #1e293b; /* Dark background */
    color: #f8f9fa; /* Light text */
    margin: 0;
    padding: 20px;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    color: #f8f9fa;
    padding-bottom: 15px;
    border-bottom: 1px solid #334155;
}

.dashboard-header h2 {
    margin: 0;
    font-size: 2rem;
    font-weight: bold;
}

.info-cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-card {
    background-color: #2c3e50; /* Darker card background */
    color: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    transition: transform 0.2s ease-in-out;
}

.info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.info-card i {
    font-size: 36px;
    margin-bottom: 10px;
}

.info-card h5 {
    margin-top: 0;
    margin-bottom: 5px;
    font-weight: bold;
    font-size: 1.1rem;
}

.info-card p {
    font-size: 1.2rem;
    margin-bottom: 0;
    font-weight: bold;
}

/* Specific colors for info cards based on your example */
.bg-info {
    background-color: #17a2b8;
}

.bg-warning {
    background-color: #ffc107;
}

.bg-primary {
    background-color: #007bff;
}

.bg-success {
    background-color: #28a745;
}

.bg-danger {
    background-color: #dc3545;
}

.leaderboard-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.leaderboard-card {
    background-color: #2c3e50; /* Darker card background */
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.leaderboard-header {
    background-color: #34495e; /* Darker header */
    color: #fff;
    padding: 15px;
    border-bottom: 1px solid #334155;
    font-weight: bold;
    display: flex;
    align-items: center;
}

.leaderboard-header i {
    margin-right: 10px;
    font-size: 1.2rem;
}

.leaderboard-list {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 300px;
    overflow-y: auto;
}

.leaderboard-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid #334155;
    color: #f8f9fa;
}

.leaderboard-item:last-child {
    border-bottom: none;
}

.leaderboard-rank {
    font-weight: bold;
    color: #f0ad4e; /* Gold color for rank */
    width: 30px;
    text-align: right;
    margin-right: 10px;
}

.leaderboard-name {
    flex-grow: 1;
}

.leaderboard-score {
    color: #ccc;
    font-size: 0.9rem;
}

.pending-reports-card {
    background-color: #2c3e50; /* Darker card background */
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 20px;
}

.pending-reports-header {
    background-color: #e74c3c; /* Red for pending */
    color: #fff;
    padding: 15px;
    border-bottom: 1px solid #c0392b;
    font-weight: bold;
    display: flex;
    align-items: center;
}

.pending-reports-header i {
    margin-right: 10px;
    font-size: 1.2rem;
}

.pending-reports-list {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 200px;
    overflow-y: auto;
}

.pending-report-item {
    padding: 10px 15px;
    border-bottom: 1px solid #334155;
    color: #f8f9fa;
}

.pending-report-item:last-child {
    border-bottom: none;
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

.user-list-card {
    background-color: #2c3e50; /* Darker card background */
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 20px;
}

.user-list-header {
    background-color: #38ada9; /* Teal for online users */
    color: #fff;
    padding: 15px;
    border-bottom: 1px solid #2e8b86;
    font-weight: bold;
    display: flex;
    align-items: center;
}

.user-list-header i {
    margin-right: 10px;
    font-size: 1.2rem;
}

.table-responsive {
    overflow-x: auto;
}

.user-list-table {
    width: 100%;
    border-collapse: collapse;
    color: #f8f9fa;
}

.user-list-table th, .user-list-table td {
    padding: 10px 15px;
    text-align: left;
    border-bottom: 1px solid #334155;
}

.user-list-table th {
    background-color: #34495e; /* Darker header */
    font-weight: bold;
}

.user-list-table tbody tr:last-child td {
    border-bottom: none;
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #34495e;
    min-width: 120px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 2;
    right: 0;
    border-radius: 4px;
    overflow: hidden;
}

.dropdown-content a {
    color: #f8f9fa;
    padding: 10px 15px;
    text-decoration: none;
    display: block;
    font-size: 0.9rem;
    transition: background-color 0.3s ease;
}

.dropdown-content a:hover {
    background-color: #2c3e50;
}

.dropdown:hover .dropdown-content {
    display: block;
}

.dropdown-toggle {
    cursor: pointer;
    font-size: 1.2rem;
    color: #ccc;
}

.user-details-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.7); /* Darker overlay */
}

.modal-content {
    background-color: #34495e;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #555;
    width: 80%;
    border-radius: 8px;
    position: relative;
    color: #f8f9fa;
}

.close-button {
    color: #aaa;
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-button:hover,
.close-button:focus {
    color: #fff;
    text-decoration: none;
}

#modal-title {
    color: #f8f9fa;
    margin-top: 0;
    margin-bottom: 15px;
    font-weight: bold;
}

#modal-body p {
    margin-bottom: 10px;
}

#modal-body p strong {
    font-weight: bold;
    color: #ddd;
    margin-right: 5px;
}

hr {
    border-color: #334155;
    margin: 20px 0;
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
            <a href="year_lvl.php">Add Sections</a>
        </div>

        <i class="bx bx-dots-vertical-rounded dropdown-toggle-icon"></i>

        <div class="right-dropdown">
            <a href="messages.php">Create Cards</a>
            <a href="users.php">Add User</a>
            <a href="test.php">Create Game Levels</a>
            <a href="year_lvl.php">Add Sections</a>
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
     <ul class="lists">
         <li class="list-group-heading">Create Code Exercises</li>
         <li class="list-item"><a href="Sections.php" class="nav-link">Easy</a></li>
         <li class="list-item"><a href="medium_ass.php" class="nav-link">Medium</a></li>
         <li class="list-item"><a href="hard_ass.php" class="nav-link">Hard</a></li>
     </ul>
    </div>
</div>

<main class="main">
    <div class="dashboard-header">
        <h2>Dashboard Overview</h2>
    </div>

    <div class="info-cards-container">
        <div class="info-card bg-info text-white">
            <i class="bx bx-coin"></i>
            <h5>Total Coins</h5>
            <p><?= number_format($total_coins, 2) ?></p>
        </div>
        <div class="info-card bg-warning text-white">
            <i class="bx bx-trophy"></i>
            <h5>Total Score</h5>
            <p><?= number_format($total_score) ?></p>
        </div>
        <div class="info-card bg-primary text-white">
            <i class="bx bx-user"></i>
            <h5>Total Students</h5>
            <p><?= $total_students ?></p>
        </div>
        <div class="info-card bg-success text-white">
            <i class="bx bx-user-check"></i>
            <h5> Online Students</h5>
            <p><?= count($online_users) ?></p>
        </div>
        <div class="info-card bg-danger text-white">
            <i class="bx bx-error"></i>
            <h5>Pending Reports</h5>
            <p><?= count($pending_reports) ?></p>
        </div>
    </div>

    <div class="leaderboard-section">
        <div class="leaderboard-card">
            <div class="leaderboard-header">
                <i class="bx bx-coin"></i> Coin Leaderboard
            </div>
            <ul class="leaderboard-list" style="max-height: 300px; overflow-y: auto;">
                <?php
                $stmt = $conn->prepare("SELECT first_name, last_name, year_id, coins FROM admin_tb ORDER BY coins DESC");
                $stmt->execute();
                $coin_leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($coin_leaderboard):
                    foreach ($coin_leaderboard as $rank => $user): ?>
                        <li class="leaderboard-item">
                            <span class="leaderboard-rank"><?= $rank + 1 ?></span>
                            <span class="leaderboard-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (<?= htmlspecialchars($user['year_id']) ?>)</span>
                            <span class="leaderboard-score"><?= number_format($user['coins'], 2) ?></span>
                        </li>
                    <?php endforeach;
                else: ?>
                    <li class="leaderboard-item">No users with coins yet.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="leaderboard-card">
            <div class="leaderboard-header">
                <i class="bx bx-trophy"></i> Score Leaderboard
            </div>
            <ul class="leaderboard-list" style="max-height: 300px; overflow-y: auto;">
                <?php
                $stmt = $conn->prepare("SELECT first_name, last_name, year_id, score FROM admin_tb ORDER BY score DESC");
                $stmt->execute();
                $score_leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($score_leaderboard):
                    foreach ($score_leaderboard as $rank => $user): ?>
                        <li class="leaderboard-item">
                            <span class="leaderboard-rank"><?= $rank + 1 ?></span>
                            <span class="leaderboard-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (<?= htmlspecialchars($user['year_id']) ?>)</span>
                            <span class="leaderboard-score"><?= number_format($user['score']) ?></span>
                        </li>
                    <?php endforeach;
                else: ?>
                    <li class="leaderboard-item">No users with a score yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <hr>

    <div class="pending-reports-card">
        <div class="pending-reports-header">
            <i class="bx bx-error-alt"></i> Pending Reports
        </div>
        <ul class="pending-reports-list">
            <?php if (count($pending_reports) > 0): ?>
                <?php foreach ($pending_reports as $report): ?>
                    <li class="pending-report-item"><?= htmlspecialchars($report['comment'] ?? 'No Comment Available') ?></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="pending-report-item">No pending reports found.</li>
            <?php endif; ?>
        </ul>

    </div>
    <hr>
    <div class="user-list-card">
        <div class="user-list-header bg-success text-white">
            <i class="bx bx-user-check"></i> Online Students
        </div>
        <div class="table-responsive">
            <table class="user-list-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Username</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; foreach ($online_users as $user): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><?= htmlspecialchars($user['first_name'] . " " . $user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
    <hr>
    <div class="user-list-card">
        <div class="user-list-header">
            <i class="bx bx-group"></i> All Users
        </div>
        <div class="table-responsive">
            <table class="user-list-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; foreach ($all_users as $user): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><?= htmlspecialchars($user['first_name'] . " " . $user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <div class="dropdown">
                                    <span class="dropdown-toggle">&#8942;</span>
                                    <div class="dropdown-content">
                                        <a href="#" onclick="openModal(<?= $user['tbl_user_id'] ?>)">View Details</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <div id="userDetailsModal" class="user-details-modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <h4 id="modal-title">User Details</h4>
            <div id="modal-body">
            </div>
        </div>
    </div>

</main>

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

    // JavaScript for the View User Modal
    const modal = document.getElementById("userDetailsModal");
    const modalBody = document.getElementById("modal-body");
    const modalTitle = document.getElementById("modal-title");

    function openModal(userId) {
        if (!modal || !modalBody || !modalTitle) {
            console.error("Modal elements not found!");
            return;
        }

        fetch(`get_user_details.php?user_id=${userId}`)
            .then(response => {
                console.log("Response:", response); // Log the raw response
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Data:", data); // Log the parsed JSON data
                if (data && data.tbl_user_id) {
                    modalTitle.textContent = `Details for User ID: ${data.tbl_user_id}`;
                    modalBody.innerHTML = `
                        <p><strong>First Name:</strong> ${data.first_name}</p>
                        <p><strong>Last Name:</strong> ${data.last_name}</p>
                        <p><strong>Username:</strong> ${data.username}</p>
                        <p><strong>Email:</strong> ${data.email || 'N/A'}</p>
                        <p><strong>Contact Number:</strong> ${data.contact_number || 'N/A'}</p>
                        <p><strong>Score:</strong> ${data.score !== null ? data.score : 'N/A'}</p>
                        <p><strong>Coins:</strong> ${data.coins !== null ? parseFloat(data.coins).toFixed(2) : 'N/A'}</p>
                        <p><strong>Profile Picture:</strong> ${data.profile_picture ? `<img src="uploads/${data.profile_picture}" alt="Profile Picture" style="max-width: 100px;">` : 'No picture available.'}</p>`;
                    modal.style.display = "block";
                } else if (data && data.error) {
                    modalTitle.textContent = "Error";
                    modalBody.innerHTML = `<p>${data.error}</p>`;
                    modal.style.display = "block";
                } else {
                    console.error("Invalid data received:", data);
                    modalTitle.textContent = "Error";
                    modalBody.innerHTML = "<p>Error loading user details: Invalid data.</p>";
                    modal.style.display = "block";
                }
            })
            .catch(error => {
                console.error("Error fetching user details:", error);
                modalTitle.textContent = "Error";
                modalBody.innerHTML = `<p>Error loading user details: ${error.message || error}</p>`;
                modal.style.display = "block";
            });
    }

    function closeModal() {
        if (modal) {
            modal.style.display = "none";
        }
        if (modalBody) {
            modalBody.innerHTML = ""; // Clear previous data
        }
    }

    // Close modal if user clicks outside
    window.addEventListener('click', function(event) {
        if (modal && event.target == modal) {
            closeModal();
        }
    });
</script>

</body>
</html>

<?php $conn = null; ?>