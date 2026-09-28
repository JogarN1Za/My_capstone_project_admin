<?php
include('conn/connect.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$toast = '';

// Function to display toast message
function showToast($message) {
    echo '<div id="toast">' . htmlspecialchars($message) . '</div>';
}

// INSERT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['level_number']) && !isset($_POST['delete_level'])) {
    $level_number = filter_input(INPUT_POST, 'level_number', FILTER_VALIDATE_INT);
    $code_text = trim($_POST['code_text']);
    $time_limit = filter_input(INPUT_POST, 'time_limit', FILTER_VALIDATE_INT);
    $coin_reward = filter_input(INPUT_POST, 'coin_reward', FILTER_VALIDATE_FLOAT);

    if (!$level_number || $level_number <= 0 || !$time_limit || $time_limit <= 0 || !$coin_reward || $coin_reward < 0 || empty($code_text)) {
        $_SESSION['toast'] = 'Please provide valid input in all fields (Level #, Time > 0, Coins >= 0).';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO typing_levels (level_number, code_text, time_limit, coin_reward) VALUES (?, ?, ?, ?)");
            $stmt->execute([$level_number, $code_text, $time_limit, $coin_reward]);
            $_SESSION['toast'] = 'Level created successfully!';
        } catch (PDOException $e) {
            $_SESSION['toast'] = "Database error: " . $e->getMessage();
        }
    }
    header("Location: test.php"); // Redirect after processing
    exit();
}

// DELETE
if (isset($_POST['delete_level'])) {
    $deleteLevel = filter_input(INPUT_POST, 'delete_level', FILTER_VALIDATE_INT);
    if ($deleteLevel && $deleteLevel > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM typing_levels WHERE level_number = ?");
            $stmt->execute([$deleteLevel]);
            $_SESSION['toast'] = "Level #$deleteLevel deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['toast'] = "Delete error: " . $e->getMessage();
        }
    } else {
        $_SESSION['toast'] = "Invalid level number for deletion.";
    }
    header("Location: test.php"); // Redirect after processing
    exit();
}

// Fetch levels
$levels = [];
try {
    $stmt = $conn->query("SELECT * FROM typing_levels ORDER BY level_number ASC");
    $levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['toast'] = "Fetch error: " . $e->getMessage();
}

// Display toast from session (if any)
if (isset($_SESSION['toast']) && $_SESSION['toast'] !== '') {
    $toast = $_SESSION['toast'];
    unset($_SESSION['toast']); // Clear the session variable
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
    <title>Typing Game Levels - Admin</title>
    <link rel="stylesheet" href="css/test.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        #toast {
            display: none;
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: #fff;
            padding: 10px 20px;
            border-radius: 4px;
            z-index: 9999;
            font-size: 14px;
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

<?php showToast($toast); ?>

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
    <div class="container mt-5">
        <h2>Create New Typing Level</h2>
        <form action="test.php" method="POST">
            <div class="form-group">
                <label for="level_number">Level Number:</label>
                <input type="number" class="form-control" id="level_number" name="level_number" required>
            </div>
            <div class="form-group">
                <label for="code_text">Code Text:</label>
                <textarea class="form-control" id="code_text" name="code_text" rows="5" required></textarea>
            </div>
            <div class="form-group">
                <label for="time_limit">Time Limit (seconds):</label>
                <input type="number" class="form-control" id="time_limit" name="time_limit" value="60">
            </div>
            <div class="form-group">
                <label for="coin_reward">Coin Reward:</label>
                <input type="number" step="0.01" class="form-control" id="coin_reward" name="coin_reward" value="0.50">
            </div>
            <button type="submit" class="btn btn-primary">Create Level</button>
        </form>

        <div class="table-wrapper">
            <h4 class="mt-5">Existing Levels</h4>
            <table class="table table-bordered"  style="color: white" id="partsTable">
                <thead>
                    <tr>
                        <th>Level #</th>
                        <th>Code Text</th>
                        <th>Time Limit</th>
                        <th>Coin Reward</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($levels)): ?>
                        <?php foreach ($levels as $level): ?>
                            <tr>
                                <td><?= htmlspecialchars($level['level_number']) ?></td>
                                <td><pre style="color: #fff;"><?= htmlspecialchars($level['code_text']) ?></pre></td>
                                <td><?= htmlspecialchars($level['time_limit']) ?>s</td>
                                <td><?= htmlspecialchars(number_format($level['coin_reward'], 2)) ?></td>
                                <td>
                                    <form action="test.php" method="POST" onsubmit="return confirm('Delete level #<?= $level['level_number'] ?>?');">
                                        <input type="hidden" name="delete_level" value="<?= $level['level_number'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No levels found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const menuIcon = document.querySelector('.menu-icon');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const dropdownToggle = document.querySelector('.dropdown-toggle-icon');
    const mobileDropdown = document.querySelector('.right-dropdown');

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

    dropdownToggle.addEventListener('click', () => {
        mobileDropdown.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
        if (!dropdownToggle.contains(e.target) && !mobileDropdown.contains(e.target)) {
            mobileDropdown.classList.remove('show');
        }
    });

    // Toast
    document.addEventListener("DOMContentLoaded", () => {
        const toast = document.getElementById("toast");
        if (toast && toast.textContent.trim() !== '') {
            toast.style.display = "block";
            setTimeout(() => {
                toast.style.display = "none";
            }, 3000);
        }
    });
</script>
</body>
</html>

<?php $conn = null; ?>
