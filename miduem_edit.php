<?php
include('conn/connect.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// ✅ Handle Tutorial Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitTutorial'])) {
    $tu_cat_id = $_POST['tu_cat_id'] ?? '';
    $tu_title = trim($_POST['tu_title']);
    $tut_par = trim($_POST['tut_par']);
    $code_edit = trim($_POST['code_edit']);
    $part_id = $_POST['part_id'] ?? '';

    if (!empty($tu_cat_id) && !empty($part_id)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO tutorial (tu_cat_id, tu_title, tut_par, code_edit, part_id)
                VALUES (:tu_cat_id, :tu_title, :tut_par, :code_edit, :part_id)
            ");
            $stmt->execute([
                ':tu_cat_id' => $tu_cat_id,
                ':tu_title' => $tu_title,
                ':tut_par' => $tut_par,
                ':code_edit' => $code_edit,
                ':part_id' => $part_id
            ]);

            echo "<script>alert('✅ Tutorial added successfully!');</script>";
            echo "<script>window.location.href='miduem_edit.php';</script>";
        } catch (PDOException $e) {
            die("<b>❌ Database Error:</b> " . $e->getMessage());
        }
    } else {
        echo "<script>alert('⚠️ Category and Part are required!');</script>";
    }
}

// ✅ Handle Tutorial Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updateTutorial'])) {
    $id = $_POST['id'];
    $tu_cat_id = $_POST['tu_cat_id'];
    $tu_title = trim($_POST['tu_title']);
    $tut_par = trim($_POST['tut_par']);
    $code_edit = trim($_POST['code_edit']);
    $part_id = $_POST['part_id'];

    if (!empty($id) && !empty($tu_cat_id) && !empty($part_id)) {
        try {
            $stmt = $conn->prepare("
                UPDATE tutorial
                SET tu_cat_id = :tu_cat_id, tu_title = :tu_title, tut_par = :tut_par, code_edit = :code_edit, part_id = :part_id
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':tu_cat_id' => $tu_cat_id,
                ':tu_title' => $tu_title,
                ':tut_par' => $tut_par,
                ':code_edit' => $code_edit,
                ':part_id' => $part_id
            ]);

            echo "<script>alert('✅ Tutorial updated successfully!'); window.location.href='miduem_edit.php';</script>";
        } catch (PDOException $e) {
            die("<b>❌ Database Error:</b> " . $e->getMessage());
        }
    } else {
        echo "<script>alert('⚠️ Category and Part are required for updating!');</script>";
    }
}

// ✅ Handle Tutorial Deletion
if (isset($_POST['deleteTutorial'])) {
    $id = $_POST['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM tutorial WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo "<script>alert('✅ Tutorial deleted successfully!'); window.location.href='miduem_edit.php';</script>";
    } catch (PDOException $e) {
        die("<b>❌ Error deleting tutorial:</b> " . $e->getMessage());
    }
}

// ✅ Fetch Tutorials Data (Medium Difficulty Only)
try {
    $stmt = $conn->prepare("
        SELECT t.id, c.cat_title, t.tu_cat_id, t.tu_title, t.tut_par, t.code_edit, p.part_title, p.id AS part_id, d.select_diff AS difficulty
        FROM tutorial t
        JOIN category c ON t.tu_cat_id = c.id
        JOIN parts p ON t.part_id = p.id
        JOIN diff d ON c.diff_id = d.id
        WHERE d.select_diff = 'Medium'
        ORDER BY t.id DESC
    ");
    $stmt->execute();
    $tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<b>❌ Error fetching tutorials:</b> " . $e->getMessage());
}

// ✅ Fetch Categories Data (Medium Difficulty Only)
try {
    $stmt = $conn->prepare("
        SELECT c.id, c.cat_title, d.select_diff AS difficulty
        FROM category c
        JOIN diff d ON c.diff_id = d.id
        WHERE d.select_diff = 'Medium'
        ORDER BY c.id ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<b>❌ Error fetching categories:</b> " . $e->getMessage());
}

// ✅ Fetch Parts Data with Difficulty (Medium Difficulty Only)
try {
    $stmt = $conn->prepare("
        SELECT p.id, p.part_title, p.part_dis, d.select_diff AS difficulty
        FROM parts p
        LEFT JOIN diff d ON p.diff_id = d.id
        WHERE d.select_diff = 'Medium'
        ORDER BY p.id ASC
    ");
    $stmt->execute();
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<b>❌ Error fetching parts:</b> " . $e->getMessage());
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
            font-weight: bold;           /* Gawing bold */
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
        .table {
            width: 100%;
            max-width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: middle;
            border-top: 1px solid #dee2e6;
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

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }

        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }

        .badge-primary {
            color: #fff;
            background-color: #007bff;
        }
        .badge-info {
            color: #fff;
            background-color: #17a2b8;
        }

        .badge-danger {
            color: #fff;
            background-color: #dc3545;
        }

        .badge-success {
            color: #fff;
            background-color: #28a745;
        }

        /* Search bar styles */
        #tutorialSearch {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            box-sizing: border-box;
            border: 1px solid #4a5568;
            background-color: #1a202c;
            color: #f0f0f0;
            border-radius: 0.25rem;
        }

        .table-wrapper {
            overflow-x: auto; /* Enable horizontal scroll if needed */
            max-height: 500px; /* Set a maximum height for vertical scroll */
            overflow-y: auto;
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

    <div class="container mt-4">
        <h2>Create a Tutorial Medium</h2>
        <form action="" method="POST" class="innovative-form tabbed-form">
            <ul class="nav nav-tabs" id="tutorialTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab" aria-controls="basic" aria-selected="true">Basic Info</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="content-tab" data-toggle="tab" href="#content" role="tab" aria-controls="content" aria-selected="false">Content</a>
                </li>
            </ul>
            <div class="tab-content mt-3" id="tutorialTabsContent">
                <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                    <div class="form-group">
                        <label for="tu_cat_id">Category:</label>
                        <select name="tu_cat_id" class="form-control" id="tu_cat_id" required>
                            <option value="" disabled selected>Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>">
                                    <?= $category['cat_title'] ?> (<?= $category['difficulty'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="part_id">Part:</label>
                        <select name="part_id" class="form-control" id="part_id" required>
                            <option value="" disabled selected>Select Part</option>
                            <?php foreach ($parts as $part): ?>
                                <option value="<?= $part['id'] ?>">
                                    <?= $part['part_title'] ?> - <?= $part['part_dis'] ?>
                                    <?php if ($part['difficulty']): ?>
                                        (<?= $part['difficulty'] ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tu_title">Tutorial Title:</label>
                        <input type="text" class="form-control" name="tu_title" id="tu_title">
                    </div>
                </div>
                <div class="tab-pane fade" id="content" role="tabpanel" aria-labelledby="content-tab">
                    <div class="form-group">
                        <label for="tut_par">Paragraph:</label>
                        <textarea class="form-control" name="tut_par" id="tut_par" rows="6"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="code_edit">Code Editor:</label>
                        <textarea class="form-control" name="code_edit" id="code_edit" rows="10"></textarea>
                    </div>
                    <button type="submit" name="submitTutorial" class="btn btn-primary">Submit Tutorial</button>
                </div>
            </div>
        </form>
    </div>

    <div class="container mt-4">
        <h2>List of Tutorials</h2>
        <input type="text" id="tutorialSearch" onkeyup="filterTutorialsTable()" placeholder="Search Tutorials...">
        <div class="table-responsive">
            <div class="table-wrapper">
                <table class="table table-bordered" style="color: white" id="categoryTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Title</th>
                            <th>Part</th>
                            <th>Difficulty</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($tutorials): ?>
                            <?php $rowNumber = 1; ?> <?php foreach ($tutorials as $tutorial): ?>
                                <tr>
                                    <td><?= $rowNumber; ?></td>
                                    <td><?= $tutorial['cat_title'] ?></td>
                                    <td><?= $tutorial['tu_title'] ?></td>
                                    <td><?= $tutorial['part_title'] ?></td>
                                    <td><?= $tutorial['difficulty'] ?></td>
                                    <td>
                                    <div>
                                        <button class="btn btn-sm btn-primary editBtn"
                                        data-id="<?= $tutorial['id'] ?>"
                                        data-category="<?= $tutorial['tu_cat_id'] ?>"
                                        data-title="<?= htmlspecialchars($tutorial['tu_title']) ?>"
                                        data-paragraph="<?= htmlspecialchars($tutorial['tut_par']) ?>"
                                        data-code="<?= htmlspecialchars($tutorial['code_edit']) ?>"
                                        data-part="<?= $tutorial['part_id'] ?>">
                                        Edit
                                        </button>
                                        <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $tutorial['id'] ?>">
                                        <button type="submit" name="deleteTutorial" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this tutorial?');">
                                        Delete
                                        </button>
                                        </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php $rowNumber++; ?> <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No Tutorials Found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

HTML

<div class="modal fade" id="editModal" style="background-color: rgba(0, 0, 0, 0.5);">
    <div class="modal-dialog">
        <div class="modal-content" style="background-color: #1a202c; color: #f0f0f0; border: 1px solid #4a5568;">
            <div class="modal-header" style="background-color: #2d3748; border-bottom: 1px solid #4a5568;">
                <h5 class="modal-title">Edit Tutorial</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #f0f0f0;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="form-group">
                        <label for="edit_category">Category:</label>
                        <select name="tu_cat_id" class="form-control" id="edit_category" required style="background-color: #1a202c; color: #f0f0f0; border: 1px solid #4a5568;">
                            <option value="" disabled selected>Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>">
                                    <?= $category['cat_title'] ?> (<?= $category['difficulty'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_part">Part:</label>
                        <select name="part_id" class="form-control" id="edit_part" required style="background-color: #1a202c; color: #f0f0f0; border: 1px solid #4a5568;">
                            <option value="" disabled selected>Select Part</option>
                            <?php foreach ($parts as $part): ?>
                                <option value="<?= $part['id'] ?>">
                                    <?= $part['part_title'] ?> - <?= $part['part_dis'] ?>
                                    <?php if ($part['difficulty']): ?>
                                        (<?= $part['difficulty'] ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_title">Title:</label>
                        <input type="text" class="form-control" name="tu_title" id="edit_title" style="background-color: #1a202c; color: #f0f0f0; border: 1px solid #4a5568;">
                    </div>

                    <div class="form-group">
                        <label for="edit_paragraph">Paragraph:</label>
                        <textarea class="form-control" name="tut_par" id="edit_paragraph" rows="4" style="background-color: #1a202c; color: #f0f0f0; border: 1px solid #4a5568;"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="edit_code">Code Editor:</label>
                        <textarea class="form-control" name="code_edit" id="edit_code" rows="6" style="background-color: #1a202c; color: #f0f0f0; border: 1px solid #4a5568;"></textarea>
                    </div>

                    <button type="submit" name="updateTutorial" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
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
    $(document).ready(function() {
        $('.editBtn').on('click', function() {
            $('#edit_id').val($(this).data('id'));
            $('#edit_category').val($(this).data('category'));
            $('#edit_title').val($(this).data('title'));
            $('#edit_paragraph').val($(this).data('paragraph'));
            $('#edit_code').val($(this).data('code'));
            $('#edit_part').val($(this).data('part'));
            $('#editModal').modal('show');
        });
    });

    function filterTutorialsTable() {
        let input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("tutorialSearch");
        filter = input.value.toUpperCase();
        table = document.getElementById("categoryTable");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
            let shouldShow = false;
            // Loop through all table data cells (excluding the actions column)
            for (let j = 0; j < tr[i].cells.length - 1; j++) {
                td = tr[i].cells[j];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        shouldShow = true;
                        break;
                    }
                }
            }
            tr[i].style.display = shouldShow ? "" : "none";
        }
    }
</script>
</body>
</html>

<?php $conn = null; ?>