<?php
session_start();
include('./conn/connect.php'); // Include database connection


$user_id = $_SESSION['user_id'];

// ✅ Count unread notifications from the notifications table
$stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = :user_id AND status = 'unread' AND deleted = 0");
$stmt->execute([':user_id' => $user_id]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);
$unreadCount = $notification['unread_count'] ?? 0;

// ✅ Get selected category from the URL
$category_id = $_GET['category'] ?? null;
if (!$category_id || !is_numeric($category_id)) {
    die("<b>❌ Error:</b> Invalid or missing category.");
}

// ✅ Fetch category details
$stmt = $conn->prepare("SELECT id, cat_title, cat_dis FROM category WHERE id = :category_id");
$stmt->execute([':category_id' => $category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    die("<b>❌ Error:</b> Category not found.");
}

// ✅ Function to check if a quiz has been completed and get the score
function getQuizScore($conn, $user_id, $quiz_id) {
    $stmt = $conn->prepare("
        SELECT score
        FROM quiz_attempts
        WHERE user_id = :user_id AND quiz_id = :quiz_id
    ");
    $stmt->execute([':user_id' => $user_id, ':quiz_id' => $quiz_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['score'] ?? null;
}

// ✅ Fetch Tutorials
$stmt = $conn->prepare("
    SELECT id, part_title, part_dis
    FROM parts
    WHERE id IN (SELECT part_id FROM tutorial WHERE tu_cat_id = :category_id)
    ORDER BY id ASC
");
$stmt->execute([':category_id' => $category_id]);
$tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Fetch Quizzes with Part Name
$stmt = $conn->prepare("
    SELECT q.id, q.quiz_title, q.countdown, q.part_id,
           COALESCE(p.part_title, 'Unknown Part') AS part_title
    FROM quizzes q
    LEFT JOIN parts p ON q.part_id = p.id
    WHERE q.category_id = :category_id
    ORDER BY q.part_id ASC
");
$stmt->execute([':category_id' => $category_id]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Group Quizzes by Part Name
$quizzes_by_part = [];
foreach ($quizzes as $quiz) {
    $quizzes_by_part[$quiz['part_title']][] = $quiz;
}

// ✅ Calculate Overall Category Progress
// Fetch total tutorials and completed tutorials
$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT t.id) AS total_tutorials,
        SUM(CASE WHEN p.progress_percentage = 100 THEN 1 ELSE 0 END) AS completed_tutorials
    FROM tutorial t
    LEFT JOIN progress p ON p.part_id = t.id AND p.user_id = :user_id
    WHERE t.tu_cat_id = :category_id
");
$stmt->execute([':user_id' => $user_id, ':category_id' => $category_id]);
$tutorial_progress = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch total quizzes and completed quizzes
$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT q.id) AS total_quizzes,
        SUM(CASE WHEN p.progress_percentage = 100 THEN 1 ELSE 0 END) AS completed_quizzes
    FROM quizzes q
    LEFT JOIN progress p ON p.part_id = q.id AND p.user_id = :user_id
    WHERE q.category_id = :category_id
");
$stmt->execute([':user_id' => $user_id, ':category_id' => $category_id]);
$quiz_progress = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate overall progress
$total_items = $tutorial_progress['total_tutorials'] + $quiz_progress['total_quizzes'];
$completed_items = $tutorial_progress['completed_tutorials'] + $quiz_progress['completed_quizzes'];
$category_progress = $total_items > 0 ? round(($completed_items / $total_items) * 100, 1) : 0;

// ✅ Check if quiz is reset and can be retaken
function canRetakeQuiz($conn, $user_id, $quiz_id) {
    $stmt = $conn->prepare("SELECT progress_percentage FROM progress WHERE user_id = :user_id AND part_id = :quiz_id");
    $stmt->execute([':user_id' => $user_id, ':part_id' => $quiz_id]);
    $progress = $stmt->fetchColumn();

    // ✅ Allow retake if no record exists or progress is 0%
    return $progress === false || $progress == 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= htmlspecialchars($category['cat_title']) ?> - Web.Dev.</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>

/* Dark Theme Styles (consistent with other pages) */
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
        /* tut_part.php Specific Styles */
        .btn-gradient {
            background: linear-gradient(to right, #007bff, #6610f2);
            color: white;
            border: none;
        }

        .btn-gradient:hover {
            background: linear-gradient(to right, #0056b3, #4c08b3);
        }

        .list-group-item {
            border-left: 3px solid transparent; /* Default border */
            transition: border-left 0.3s ease-in-out;
            background-color: #1e293b; /* Dark background */
            color: #f8f9fa; /* Light text */
            border-color: #334155; /* Dark border */
        }

        .list-group-item.completed {
            border-left-color: #28a745; /* Highlight completed items */
            background-color: #27374d; /* Slightly darker completed background */
        }

        .tutorial-item:hover, .quiz-item:hover {
            background-color: #334155;
        }

        .alert-info, .alert-warning, .modal-content, .modal-header, .modal-body, .modal-footer {
            background-color: #1e293b;
            color: #f8f9fa;
            border-color: #334155;
        }

        .modal-header .close {
            color: #f8f9fa;
            opacity: 0.7;
        }

        .modal-header .close:hover {
            opacity: 1;
        }

        .form-label {
            color: #cbd5e1;
        }

        .form-control {
            background-color: #27374d;
            border-color: #334155;
            color: #e0e0e0;
        }

        .btn-outline-primary {
            color: #007bff;
            border-color: #007bff;
        }

        .btn-outline-primary:hover {
            background-color: #007bff;
            color: #fff;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            color: #fff;
        }

        .btn-success:hover {
            background-color: #1e7e34;
            border-color: #1e7e34;
        }

        .btn-outline-success {
            color: #28a745;
            border-color: #28a745;
        }

        .btn-outline-success:hover {
            background-color: #28a745;
            color: #fff;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }

        .text-primary {
            color: #007bff !important;
        }

        .text-success {
            color: #28a745 !important;
        }

        .text-info {
            color: #17a2b8 !important;
        }

        .text-muted {
            color: #6c757d !important;
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
            <a href="about.php">Cards</a>
            <a href="setting.php">Leader board</a>
            <a href="report.php">Reports</a>
        </div>

        <i class="bx bx-dots-vertical-rounded dropdown-toggle-icon"></i>

        <div class="right-dropdown">
            <a href="about.php">Cards</a>
            <a href="setting.php">Leader board</a>
            <a href="report.php">Reports</a>
        </div>

        <a href="logout.php"><i class='bx bx-log-out'></i></a>
        <a href="achievement.php" class="position-relative">
            <i class='bx bx-bell'></i>
            <?php if ($unreadCount > 0): ?>
                <span class="badge badge-danger position-absolute" style="top: -5px; right: -10px; font-size: 0.7em; border-radius: 50%;">
                    <?= $unreadCount ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="home.php"><i class='bx bx-user-circle'></i></a>
    </div>
</nav>

<div class="sidebar-backdrop"></div>

<div class="sidebar">
    <div class="sidebar-content">
        <div class="tutorials-heading">TUTORIALS</div>
        <ul class="lists">
            <li class="list-group-heading">
                <a href="easy.php" class="nav-link">Easy Tutorials</a>
            </li>
            <?php
            // Fetch easy categories for the sidebar
            try {
                $stmtEasySidebar = $conn->prepare("
                    SELECT
                        c.id AS category_id,
                        c.cat_title
                    FROM category c
                    WHERE c.diff_id = (SELECT id FROM diff WHERE select_diff = 'easy')
                    ORDER BY c.id;
                ");
                $stmtEasySidebar->execute();
                $easyCategoriesSidebar = $stmtEasySidebar->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $easyCategoriesSidebar = [];
                echo "<li class='list-item'><span class='nav-link'>Error fetching Easy categories</span></li>";
            }
            ?>
            <?php if (!empty($easyCategoriesSidebar)): ?>
                <?php foreach ($easyCategoriesSidebar as $easyCategory): ?>
                    <li class="list-item"><a href="tut_part.php?category=<?= urlencode($easyCategory['category_id']); ?>" class="nav-link"><?= htmlspecialchars($easyCategory['cat_title']); ?></a></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="list-item"><span class="nav-link">No Easy Categories</span></li>
            <?php endif; ?>
        </ul>
        <ul class="lists">
            <li class="list-group-heading">
                <a href="meduim.php" class="nav-link">Medium Tutorials</a>
            </li>
            <?php
            // Fetch medium categories for the sidebar
            try {
                $stmtMediumSidebar = $conn->prepare("
                    SELECT
                        c.id AS category_id,
                        c.cat_title
                    FROM category c
                    WHERE c.diff_id = (SELECT id FROM diff WHERE select_diff = 'medium')
                    ORDER BY c.id;
                ");
                $stmtMediumSidebar->execute();
                $mediumCategoriesSidebar = $stmtMediumSidebar->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $mediumCategoriesSidebar = [];
                echo "<li class='list-item'><span class='nav-link'>Error fetching Medium categories</span></li>";
            }
            ?>
            <?php if (!empty($mediumCategoriesSidebar)): ?>
                <?php foreach ($mediumCategoriesSidebar as $mediumCategory): ?>
                    <li class="list-item"><a href="tut_part_medium.php?category=<?= urlencode($mediumCategory['category_id']); ?>" class="nav-link"><?= htmlspecialchars($mediumCategory['cat_title']); ?></a></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="list-item"><span class='nav-link'>No Medium Categories</span></li>
            <?php endif; ?>
        </ul>
        <ul class="lists">
            <li class="list-group-heading">
                <a href="hard.php" class="nav-link">Hard Tutorials</a>
            </li>
            <?php
            // Fetch hard categories for the sidebar
            try {
                $stmtHardSidebar = $conn->prepare("
                    SELECT
                        c.id AS category_id,
                        c.cat_title
                    FROM category c
                    WHERE c.diff_id = (SELECT id FROM diff WHERE select_diff = 'hard')
                    ORDER BY c.id;
                ");
                $stmtHardSidebar->execute();
                $hardCategoriesSidebar = $stmtHardSidebar->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $hardCategoriesSidebar = [];
                echo "<li class='list-item'><span class='nav-link'>Error fetching Hard categories</span></li>";
            }
            ?>
            <?php if (!empty($hardCategoriesSidebar)): ?>
                <?php foreach ($hardCategoriesSidebar as $hardCategory): ?>
                    <li class="list-item"><a href="tut_part_hard.php?category=<?= urlencode($hardCategory['category_id']); ?>" class="nav-link"><?= htmlspecialchars($hardCategory['cat_title']); ?></a></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="list-item"><span class="nav-link">No Hard Categories</span></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<main class="main">
    <div class="container">

        <a href="easy.php"
           class="btn btn-gradient mb-3 d-inline-flex align-items-center shadow-sm">
            <i class='bx bx-arrow-back icon me-2'></i> Back to Category
        </a>

        <section class="mb-5">
            <h2 class="h4 text-primary mb-3"><i class="bx bx-book-content mr-2"></i> Tutorials Easy</h2>
            <ul class="list-group">
                <?php if (!empty($tutorials)): ?>
                    <?php foreach ($tutorials as $tutorial): ?>
                        <?php
                        // ✅ Fetch progress for each tutorial
                        $stmt = $conn->prepare("
                            SELECT progress_percentage FROM progress
                            WHERE user_id = :user_id AND part_id = :part_id
                        ");
                        $stmt->execute([':user_id' => $user_id, ':part_id' => $tutorial['id']]);
                        $progress = $stmt->fetchColumn();
                        $progress = $progress !== false ? (int)$progress : 0;

                        // ✅ Check if completed
                        $isCompleted = $progress === 100;
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center tutorial-item <?= $isCompleted ? 'completed' : '' ?>">
                            <div>
                                <h6 class="mb-1"><i class="bx bx-file-text mr-2"></i> <?= htmlspecialchars($tutorial['part_title']) ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($tutorial['part_dis']) ?></small>
                            </div>
                            <div>
                                <a href="tutorial.php?category=<?= urlencode($category_id) ?>&part=<?= urlencode($tutorial['id']) ?>"
                                   class="btn btn-sm <?= $isCompleted ? 'btn-success' : 'btn-outline-primary' ?>">
                                    <?= $isCompleted ? '<i class="bx bx-check mr-1"></i> View' : '<i class="bx bx-play mr-1"></i> View' ?>
                                </a>
                                <?php if ($isCompleted): ?>
                                    <i class="bx bx-check-circle text-success ml-2" style="font-size: 1.2rem;"></i>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="list-group-item">
                        <div class="alert alert-info text-center" role="alert">
                            <i class="bx bx-info-circle mr-2"></i> No tutorials available for this category yet.
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </section>

        <hr class="my-5">

        <section class="mt-5">
            <h2 class="h4 text-success mb-3"><i class="bx bx-pencil mr-2"></i> Quizzes Easy</h2>
            <?php if (!empty($quizzes_by_part)): ?>
                <?php foreach ($quizzes_by_part as $part_name => $quizzes): ?>
                    <div class="mb-4">
                        <h6 class="text-muted mb-2"><i class="bx bx-folder-open mr-2"></i> <?= htmlspecialchars($part_name) ?></h6>
                        <ul class="list-group">
                            <?php foreach ($quizzes as $quiz): ?>
                                <?php
                                // ✅ Fetch the user's score for this quiz
                                $score = getQuizScore($conn, $user_id, $quiz['id']);
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center quiz-item">
                                    <div>
                                        <h6 class="mb-1"><i class="bx bx-question-mark mr-2"></i> <?= htmlspecialchars($quiz['quiz_title']) ?></h6>
                                        <small class="text-muted"><i class="bx bx-timer mr-1"></i> <?= htmlspecialchars($quiz['countdown']) ?> seconds</small>
                                        <?php if ($score !== null): ?>
                                            <br><small class="text-info"><i class="bx bx-trophy mr-1"></i> Score: <strong><?= htmlspecialchars($score) ?></strong></small>
                                        <?php else: ?>
                                            <br><small class="text-muted"><i class="bx bx-flag mr-1"></i> Not taken</small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <button class="btn btn-sm btn-outline-success mr-2 start-quiz-btn"
                                                data-id="<?= $quiz['id'] ?>"
                                                data-title="<?= htmlspecialchars($quiz['quiz_title']) ?>"
                                                data-countdown="<?= $quiz['countdown'] ?>">
                                            <i class="bx bx-play mr-1"></i> Start
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm border-0" type="button" id="quizActions<?= $quiz['id'] ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class='bx bx-dots-horizontal-rounded'></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="quizActions<?= $quiz['id'] ?>">
                                                <a class="dropdown-item report-btn" href="#" data-toggle="modal" data-target="#reportModal" data-quiz-id="<?= $quiz['id'] ?>"><i class="bx bx-flag mr-1 text-danger"></i> Report</a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-warning text-center" role="alert">
                    <i class="bx bx-error mr-2"></i> No quizzes available for this category yet.
                </div>
            <?php endif; ?>
        </section>

        <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="reportModalLabel"><i class="bx bx-flag mr-2 text-danger"></i> Report Quiz</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="reportForm">
                            <input type="hidden" name="quiz_id" id="report_quiz_id">
                            <div class="form-group">
                                <label for="report_comment" class="form-label"><i class="bx bx-message-square-dots mr-1"></i> Comment:</label>
                                <textarea name="comment" id="report_comment" class="form-control" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger"><i class="bx bx-send mr-1"></i> Submit Report</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="quizModal" tabindex="-1" aria-labelledby="quizModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="quizModalLabel"><i class="bx bx-play-circle mr-2 text-success"></i> Start Quiz</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning text-center" role="alert">
                            <i class="bx bx-hourglass-alt mr-2"></i> Time Left: <span id="quizTimer">--:--</span>
                        </div>
                        <form id="quizForm" method="POST" action="submit_quiz.php">
                            <input type="hidden" name="quiz_id" id="quiz_id" value="1">
                            <input type="hidden" name="score" id="score" value="0">
                            <input type="hidden" name="coins" id="coins" value="0.00">
                            <input type="hidden" name="total_questions" id="total_questions" value="0">
                            <input type="hidden" name="correct_answers" id="correct_answers" value="0">
                            <div id="quizContent"></div>
                            <button type="submit" class="btn btn-success mt-3 w-100"><i class="bx bx-check mr-1"></i> Submit Answers</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function () {
        let timerInterval;

        $(".start-quiz-btn").click(function () {
            let quizId = $(this).data("id");
            let countdown = $(this).data("countdown");

            $("#quiz_id").val(quizId);
            $("#quizContent").html("<p>Loading questions...</p>");
            $("#quizTimer").text(formatTime(countdown));

            $.ajax({
                url: `fetch_quiz.php?quiz_id=${quizId}`,
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    if (data.questions) {
                        let quizContent = '';
                        let totalQuestions = data.questions.length;

                        data.questions.forEach((question, index) => {
                            quizContent += `<div>
                                                <p>${index + 1}. ${question.question_text}</p>
                                                <div>
                                                    <input type="radio" name="question_${question.id}" value="A" data-correct="${question.correct_option === 'A' ? 1 : 0}"> ${question.options.A}<br>
                                                    <input type="radio" name="question_${question.id}" value="B" data-correct="${question.correct_option === 'B' ? 1 : 0}"> ${question.options.B}<br>
                                                    <input type="radio" name="question_${question.id}" value="C" data-correct="${question.correct_option === 'C' ? 1 : 0}"> ${question.options.C}<br>
                                                    <input type="radio" name="question_${question.id}" value="D" data-correct="${question.correct_option === 'D' ? 1 : 0}"> ${question.options.D}<br>
                                                </div>
                                                <hr>
                                            </div>`;
                        });

                        $("#quizContent").html(quizContent);
                        $("#total_questions").val(totalQuestions);
                        console.log("✅ Questions loaded:", data.questions); // Check if questions are loaded
                    } else {
                        $("#quizContent").html(`<p class='text-danger'>${data.error}</p>`);
                        console.error("❌ Error fetching questions:", data.error); // Log error
                    }
                },
                error: function (xhr, status, error) {
                    $("#quizContent").html("<p class='text-danger'>❌ Error loading quiz!</p>");
                    console.error("❌ AJAX error loading quiz:", status, error); // Log AJAX error
                }
            });

            $("#quizModal").modal("show");
            clearInterval(timerInterval);
            startTimer(countdown);
        });

        function startTimer(duration) {
            let timeLeft = duration;
            timerInterval = setInterval(function () {
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    alert("⏳ Time's up! Submitting your quiz.");
                    $("#quizForm").submit();
                } else {
                    $("#quizTimer").text(formatTime(timeLeft));
                    timeLeft--;
                }
            }, 1000);
        }

        function formatTime(seconds) {
            let minutes = Math.floor(seconds / 60);
            let secs = seconds % 60;
            return `${minutes}:${secs < 10 ? "0" : ""}${secs}`;
        }

        $("#quizModal").on("hidden.bs.modal", function () {
            clearInterval(timerInterval);
        });

        $("#quizForm").submit(function (event) {
            event.preventDefault();

            let score = 0;
            let correctAnswers = 0;
            let coins = 0;
            const totalQuestions = parseInt($("#total_questions").val());

            const answers = $('input[type="radio"]:checked');
            answers.each(function () {
                console.log("👉 Checked answer value:", $(this).val(), "Correct:", $(this).data('correct')); // Inspect each checked answer
                if ($(this).data('correct') === 1) { // Changed to strict equality with integer
                    correctAnswers++;
                    score += 10;
                    coins += 0.5;
                }
            });

            $("#score").val(score);
            $("#coins").val(coins.toFixed(2));
            $("#correct_answers").val(correctAnswers);

            console.log("📊 Calculated Score:", score); // Check calculated score
            console.log("✅ Correct Answers:", correctAnswers); // Check correct answers
            console.log("💰 Calculated Coins:", coins); // Check calculated coins
            console.log("❓ Total Questions:", totalQuestions); // Check total questions
            console.log("➡️ Form Data being sent:", $(this).serialize()); // Check serialized form data

            let formData = $(this).serialize();

            $.post("submit_quiz.php", formData, function (response) {
                alert(response);
                $("#quizModal").modal("hide");
                const progress = Math.floor((correctAnswers / totalQuestions) * 100);
                const partId = $("#quiz_id").val();
                saveProgress(partId, progress);
            }).fail(function (xhr, status, error) {
                alert("❌ Error submitting quiz!");
                console.error("❌ AJAX error submitting quiz:", status, error, xhr.responseText); // Log submission error
            });
        });

        function saveProgress(partId, progress) {
            if (progress > 100) progress = 100;
            $.post("progress_update.php", { part_id: partId, progress: progress }, function(response) {
                console.log("💾 Progress saved:", response); // Log progress save response
            }).fail(function(xhr, status, error) {
                console.error("❌ Error saving progress:", status, error, xhr.responseText); // Log progress save error
            });
        }
    });

    $(document).on("click", ".report-btn", function () {
        let quizId = $(this).data("quiz-id");
        $("#report_quiz_id").val(quizId);
    });

    $("#reportForm").submit(function (event) {
        event.preventDefault();
        $.ajax({
            url: "submit_report.php",
            method: "POST",
            data: $(this).serialize(),
            success: function (response) {
                alert(response);
                $("#reportModal").modal("hide");
                $("#reportForm")[0].reset();
            },
            error: function (xhr, status, error) {
                alert("❌ Error submitting report!");
                console.error("❌ AJAX error submitting report:", status, error, xhr.responseText); // Log report error
            }
        });
    });

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
</script>

</body>
</html>

<?php $conn = null; ?>
