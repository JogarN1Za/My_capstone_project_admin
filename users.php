<?php
include('conn/connect.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// ✅ Handle User Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registerUser'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $password = trim($_POST['password']);
    $year_id = trim($_POST['year_id']);
    $sec_id = trim($_POST['sec_id']);

    // Handling file upload
    $profile_picture = $_FILES['profile_picture'];

    // Check if the file was uploaded without errors
    if ($profile_picture['error'] === UPLOAD_ERR_OK) {
        $profile_picture_name = time() . '_' . basename($profile_picture['name']);
        $target_dir = "uploads/";
        $target_file = $target_dir . $profile_picture_name;

        // Check if uploads directory exists, if not create it
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Validate file type (optional)
        $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>alert('❌ Invalid file type! Please upload an image.');</script>";
            echo "<script>window.location.href='users.php';</script>";
            exit();
        }

        // Move the uploaded file to the target directory
        if (!move_uploaded_file($profile_picture['tmp_name'], $target_file)) {
            echo "<script>alert('❌ Profile Picture Upload Failed!');</script>";
            echo "<script>window.location.href='users.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('❌ Profile Picture Upload Error!');</script>";
        echo "<script>window.location.href='users.php';</script>";
        exit();
    }

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($contact_number) || empty($password) || empty($year_id) || empty($sec_id)) {
        echo "<script>alert('⚠️ All fields are required!');</script>";
        echo "<script>window.location.href='users.php';</script>";
        exit();
    }

    try {
        // Check if sec_id exists in sections table
        $stmt = $conn->prepare("SELECT COUNT(*) FROM sections WHERE id = :sec_id");
        $stmt->execute([':sec_id' => $sec_id]);
        if ($stmt->fetchColumn() == 0) {
            echo "<script>alert('⚠️ Selected section does not exist!');</script>";
            echo "<script>window.location.href='users.php';</script>";
            exit();
        }

        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_tb WHERE username = :username OR email = :email");
        $stmt->execute([':username' => $username, ':email' => $email]);
        $userExists = $stmt->fetchColumn();

        if ($userExists) {
            echo "<script>alert('⚠️ Username or Email already exists!');</script>";
            echo "<script>window.location.href='users.php';</script>";
            exit();
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and execute the insert statement
        $stmt = $conn->prepare("INSERT INTO admin_tb (first_name, last_name, username, email, contact_number, password, year_id, sec_id, profile_picture, is_online)
                                    VALUES (:first_name, :last_name, :username, :email, :contact_number, :password, :year_id, :sec_id, :profile_picture, 0)");

        $stmt->execute([
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':username' => $username,
            ':email' => $email,
            ':contact_number' => $contact_number,
            ':password' => $hashed_password,
            ':year_id' => $year_id,
            ':sec_id' => $sec_id,
            ':profile_picture' => $profile_picture_name
        ]);

        echo "<script>alert('✅ User registered successfully!');</script>";
        echo "<script>window.location.href='users.php';</script>";
        exit();
    } catch (PDOException $e) {
        die("<b>❌ Database Error:</b> " . $e->getMessage());
    }
}

// ✅ Handle User Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updateUser'])) {
    $user_id = $_POST['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $year_id = (int) trim($_POST['year_id']); // Explicitly cast to integer
    $sec_id = (int) trim($_POST['sec_id']);   // Explicitly cast to integer
    $new_password = trim($_POST['new_password']);

    try {
        // Check if sec_id exists in sections table
        $stmt = $conn->prepare("SELECT COUNT(*) FROM sections WHERE id = :sec_id");
        $stmt->execute([':sec_id' => $sec_id]);
        if ($stmt->fetchColumn() == 0) {
            echo "<script>alert('⚠️ Selected section does not exist!');</script>";
            echo "<script>window.location.href='users.php';</script>";
            exit();
        }

        // Fetch existing user data
        $stmt = $conn->prepare("SELECT * FROM admin_tb WHERE tbl_user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if any data has changed
        $dataChanged = (
            $first_name !== $existingUser['first_name'] ||
            $last_name !== $existingUser['last_name'] ||
            $username !== $existingUser['username'] ||
            $email !== $existingUser['email'] ||
            $contact_number !== $existingUser['contact_number'] ||
            $sec_id !== (int)$existingUser['sec_id'] || // Compare as integers
            $year_id !== (int)$existingUser['year_id'] || // Compare as integers
            (!empty($new_password))
        );

        if (!$dataChanged) {
            echo "<script>alert('⚠️ No changes were made.');</script>";
            header('Location: users.php');
            exit();
        }

        // Update user details
        if (!empty($new_password)) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                UPDATE admin_tb
                SET first_name = :first_name,
                    last_name = :last_name,
                    username = :username,
                    email = :email,
                    contact_number = :contact_number,
                    password = :password,
                    year_id = :year_id,
                    sec_id = :sec_id
                WHERE tbl_user_id = :user_id
            ");
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':username' => $username,
                ':email' => $email,
                ':contact_number' => $contact_number,
                ':password' => $hashed_password,
                ':year_id' => $year_id,
                ':sec_id' => $sec_id,
                ':user_id' => $user_id
            ]);
        } else {
            $stmt = $conn->prepare("
                UPDATE admin_tb
                SET first_name = :first_name,
                    last_name = :last_name,
                    username = :username,
                    email = :email,
                    contact_number = :contact_number,
                    year_id = :year_id,
                    sec_id = :sec_id
                WHERE tbl_user_id = :user_id
            ");
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':username' => $username,
                ':email' => $email,
                ':contact_number' => $contact_number,
                ':year_id' => $year_id,
                ':sec_id' => $sec_id,
                ':user_id' => $user_id
            ]);
        }

        // Check if the query affected any rows
        if ($stmt->rowCount() > 0) {
            echo "<script>alert('✅ User updated successfully!');</script>";
        } else {
            echo "<script>alert('⚠️ No changes were made.');</script>";
        }

        echo "<script>window.location.href='users.php';</script>";
        exit();
    } catch (PDOException $e) {
        die("<b>❌ Database Error:</b> " . $e->getMessage());
    }
}


// ✅ Handle User Deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    try {
        // Delete related rows in the reports table (if applicable)
        $stmt = $conn->prepare("DELETE FROM reports WHERE user_id = :delete_id");
        $stmt->execute([':delete_id' => $delete_id]);

        // Delete the user from the admin_tb table
        $stmt = $conn->prepare("DELETE FROM admin_tb WHERE tbl_user_id = :delete_id");
        $stmt->execute([':delete_id' => $delete_id]);

        echo "<script>alert('✅ User deleted successfully!');</script>";
        echo "<script>window.location.href='users.php';</script>";
        exit();
    } catch (PDOException $e) {
        die("<b>❌ Database Error:</b> " . $e->getMessage());
    }
}

// Fetch all distinct year levels from the grades table
try {
    $stmt_year_levels = $conn->query("SELECT id, year_level FROM grades ORDER BY id");
    $year_levels = $stmt_year_levels->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<b>❌ Database Error (Fetching Year Levels):</b> " . $e->getMessage());
}

// Fetch users for each year level
$users_by_year = [];
foreach ($year_levels as $year) {
    try {
        $stmt_users_by_year = $conn->prepare("
            SELECT at.*
            FROM admin_tb at
            WHERE at.year_id = :year_id
            ORDER BY at.tbl_user_id DESC
        ");
        $stmt_users_by_year->execute([':year_id' => $year['id']]);
        $users_by_year[$year['year_level']] = $stmt_users_by_year->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("<b>❌ Database Error (Fetching Users for {$year['year_level']}):</b> " . $e->getMessage());
    }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin New Design - Users</title>
    <link rel="stylesheet" href="#" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
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
        /* Styles for the category tiles */
        .scrollable-table-container {
            max-height: 400px; /* Adjust the maximum height as needed */
            overflow-y: auto;
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
             /* Custom styles for the edit modal */
             #editModal .modal-header {
            background-color:#727883; /* Dark background color for header */
            color: white; /* White text color for header */
            border-bottom: 1px solid #555; /* Optional: Add a border */
        }

        #editModal .modal-content {
            background-color:#111827; /* Light background color for content */
            color: #333; /* Dark text color for content */
            border: 1px solid rgba(0, 0, 0, 0.2); /* Optional: Border for the modal */
        }

        #editModal .modal-body label {
            color: #555; /* Darker color for labels */
        }

        #editModal .modal-footer {
            background-color: #f8f9fa; /* Match body background */
            border-top: 1px solid #ddd; /* Optional: Add a border */
        }

        #editModal .modal-footer .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        #editModal .modal-footer .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
    </style>
</head>

<body>

    <nav class="additional-nav">
        <div class="block"></div>
    </nav>

    <nav class="top-nav">
        <div class="logo">
            <i class="bx bx-menu menu-icon"></i>
            <span class="logo-name">Web.Dev.</span>
        </div>

        <div class="right-icons">
            <div class="right-links-desktop">
                <a href="messages.php">Create Cards</a>
                <a href="users.php">Add Users</a>
                <a href="test.php">Create Game Levels</a>
                <a href="year_lvl.php">Add Sections</a>
            </div>

            <i class="bx bx-dots-vertical-rounded dropdown-toggle-icon"></i>

            <div class="right-dropdown">
                <a href="messages.php">Create Cards</a>
                <a href="users.php">Add Users</a>
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
        </div>
    </div>

    <main class="main">

        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title mb-4">Register New User</h3>
                <form action="users.php" method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="text" name="email" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                        <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Year</label>
                            <select name="year_id" class="form-control" required>
                                <?php
                                // Fetching years
                                $stmt_years = $conn->query("SELECT id, year_level FROM grades");
                                while ($row_year = $stmt_years->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$row_year['id']}'>{$row_year['year_level']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Section</label>
                            <select name="sec_id" class="form-control" required>
                                <?php
                                // Fetching sections
                                $stmt_sections = $conn->query("SELECT id, section_name FROM sections");
                                while ($row_section = $stmt_sections->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$row_section['id']}'>{$row_section['section_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" name="profile_picture" class="form-control" accept="image/*" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="registerUser" class="btn btn-primary">Register</button>
                    </div>
                </form>
            </div>
        </div>

        <?php foreach ($users_by_year as $year_level => $users) : ?>
            <div class="container mt-5">
                <h2 class="text-center">List of Users - <?= htmlspecialchars($year_level) ?></h2>
                <div class="mb-3">
                    <input type="text" class="form-control" id="searchInput_<?= str_replace(' ', '_', $year_level) ?>" onkeyup="searchTable('<?= str_replace(' ', '_', $year_level) ?>')" placeholder="Search users...">
                </div>
                <div class="scrollable-table-container">
                    <table class="table table-bordered" style="color: white" id="userTable_<?= str_replace(' ', '_', $year_level) ?>">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Contact Number</th>
                                <th>Section</th>
                                <th>Password</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)) : ?>
                                <?php foreach ($users as $user) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['tbl_user_id']) ?></td>
                                        <td><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['contact_number']) ?></td>
                                        <?php
                                        // Fetch section name for the current user
                                        try {
                                            $stmt_section = $conn->prepare("SELECT section_name FROM sections WHERE id = :sec_id");
                                            $stmt_section->execute([':sec_id' => $user['sec_id']]);
                                            $section = $stmt_section->fetchColumn();
                                            echo "<td>" . htmlspecialchars($section) . "</td>";
                                        } catch (PDOException $e) {
                                            echo "<td>Error fetching section</td>";
                                        }
                                        ?>
                                        <td>********</td>
                                        <td>
                                            <button class='btn btn-warning btn-sm editBtn'
                                                data-user-id='<?= htmlspecialchars($user['tbl_user_id']) ?>'
                                                data-first-name='<?= htmlspecialchars($user['first_name']) ?>'
                                                data-last-name='<?= htmlspecialchars($user['last_name']) ?>'
                                                data-username='<?= htmlspecialchars($user['username']) ?>'
                                                data-email='<?= htmlspecialchars($user['email']) ?>'
                                                data-contact-number='<?= htmlspecialchars($user['contact_number']) ?>'
                                                data-year-id='<?= htmlspecialchars($user['year_id']) ?>'
                                                data-sec-id='<?= htmlspecialchars($user['sec_id']) ?>'
                                                data-toggle='modal'
                                                data-target='#editModal'>
                                                <i class='bx bxs-edit-alt'></i>
                                            </button>
                                            <br>
                                            <a href='users.php?delete_id=<?= htmlspecialchars($user['tbl_user_id']) ?>' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\");'><i class='bx bxs-trash'></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="8" class="text-center">No users found for <?= htmlspecialchars($year_level) ?>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="users.php" method="POST">
                            <input type="hidden" name="user_id" id="edit_user_id">
                            <label>First Name:</label>
                            <input type="text" class="form-control mb-2" name="first_name" id="edit_first_name" required>
                            <label>Last Name:</label>
                            <input type="text" class="form-control mb-2" name="last_name" id="edit_last_name" required>
                            <label>Username:</label>
                            <input type="text" class="form-control mb-2" name="username" id="edit_username" required>
                            <label>Email:</label>
                            <input type="text" class="form-control mb-2" name="email" id="edit_email" required>
                            <label>Contact Number:</label>
                            <input type="text" class="form-control mb-2" name="contact_number" id="edit_contact" required>

                            <label>Year Level:</label>
                            <select name="year_id" class="form-control mb-2" id="edit_year_id" required>
                                <?php
                                // Fetching year levels for the edit form
                                $stmt_edit_years = $conn->query("SELECT id, year_level FROM grades");
                                while ($row_edit_year = $stmt_edit_years->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$row_edit_year['id']}'>{$row_edit_year['year_level']}</option>";
                                }
                                ?>
                            </select>

                            <label>Section:</label>
                            <select name="sec_id" class="form-control mb-2" id="edit_sec_id" required>
                                <?php
                                // Fetching sections for the edit form
                                $stmt_edit_sections = $conn->query("SELECT id, section_name FROM sections");
                                while ($row_edit_section = $stmt_edit_sections->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$row_edit_section['id']}'>{$row_edit_section['section_name']}</option>";
                                }
                                ?>
                            </select>

                            <label>Password:</label>
                            <input type="password" class="form-control mb-2" name="new_password" placeholder="New Password (Leave blank to keep current password)">
                            <br>
                            <button type="submit" name="updateUser" class="btn btn-primary btn-block">Update</button>
                        </form>
                    </div>
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

        function searchTable(yearLevel) {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput_" + yearLevel.replace(' ', '_'));
            filter = input.value.toUpperCase();
            table = document.getElementById("userTable_" + yearLevel.replace(' ', '_'));
            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td");
                var shouldShow = false;
                for (var j = 0; j < td.length - 1; j++) { // Exclude the Actions column
                    if (td[j]) {
                        txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            shouldShow = true;
                            break;
                        }
                    }
                }
                if (shouldShow) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $('#editModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var userId = button.data('user-id');
            var firstName = button.data('first-name');
            var lastName = button.data('last-name');
            var username = button.data('username');
            var email = button.data('email');
            var contactNumber = button.data('contact-number');
            var yearId = button.data('year-id');
            var secId = button.data('sec-id');

            var modal = $(this);
            modal.find('#edit_user_id').val(userId);
            modal.find('#edit_first_name').val(firstName);
            modal.find('#edit_last_name').val(lastName);
            modal.find('#edit_username').val(username);
            modal.find('#edit_email').val(email);
            modal.find('#edit_contact').val(contactNumber);
            modal.find('#edit_year_id').val(yearId);
            modal.find('#edit_sec_id').val(secId);
        });
    </script>

</body>

</html>
