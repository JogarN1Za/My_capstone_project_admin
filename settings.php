<?php
include('conn/connect.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// --- SECURE PASSWORD HASHING EXAMPLE ---
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT); // Use bcrypt
}

// --- FETCH USERS ---
try {
    $stmt = $conn->prepare("SELECT * FROM tbl_user");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger mt-3'>Error fetching users: " . $e->getMessage() . "</div>";
    $users = [];
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
   .line-form-container {
    margin-top: 2rem;
}

.line-form-card {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e9ecef;
    background-color: #f8f9fa;
    padding: 20px;
}

.line-form-title {
    text-align: center;
    margin-bottom: 1.5rem;
    color: #343a40;
    font-weight: 500;
}

.form-group-line {
    margin-bottom: 1.5rem;
}

.form-label-line {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control-line {
    border: none;
    border-bottom: 1px solid #ced4da;
    padding: 0.5rem 0;
    width: 100%;
    background-color: transparent;
    border-radius: 0;
    transition: border-bottom-color 0.3s ease-in-out;
    font-size: 1rem;
}

.form-control-line:focus {
    border-bottom-color: #007bff;
    outline: none;
    box-shadow: none;
}

.btn-register-line {
    background-color: #007bff;
    color: white;
    padding: 0.7rem 1.2rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 400;
    transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    width: 100%;
}

.btn-register-line:hover {
    background-color: #0056b3;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.12);
}

.form-row-line {
    display: flex;
    flex-wrap: wrap;
    margin-right: -5px;
    margin-left: -5px;
}

.form-group-col-md-6-line {
    padding-right: 5px;
    padding-left: 5px;
    width: 50%;
}

.form-group-col-md-5-line {
    padding-right: 5px;
    padding-left: 5px;
    width: calc(5 / 12 * 100%); /* Roughly 41.67% */
}

.form-group-col-md-7-line {
    padding-right: 5px;
    padding-left: 5px;
    width: calc(7 / 12 * 100%); /* Roughly 58.33% */
}

/* Adjust for smaller screens if needed */
@media (max-width: 768px) {
    .form-group-col-md-6-line,
    .form-group-col-md-5-line,
    .form-group-col-md-7-line {
        width: 100%;
    }
}

.container {
            background-color: #111827;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .table-responsive-y {
            max-height: 400px; /* Adjust this value as needed */
            overflow-y: auto;
        }
        .table th, .table td {
            background-color:#111827;
            color: #fff;
        }
        .modal-content {
            background-color: #111827 !important; /* Example: Light gray */
            border: 1px solid rgba(0, 0, 0, 0.2); /* Optional: Add a subtle border */
            border-radius: 0.3rem; /* Optional: Add rounded corners */
            }
        .modal-header {
            background-color: #727883;
            color: white;
        }
        .modal-title {
            color: white;
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

 <br>
    <hr>
    <div class="container mt-4">
    <h2 class="mb-3">Admin Registration Form</h2>
    <form action="./endpoint/add-user.php" method="POST">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="firstName" class="form-label">First Name:</label>
                <input type="text" class="form-control form-control-sm" id="firstName" name="first_name">
            </div>
            <div class="col-md-6">
                <label for="lastName" class="form-label">Last Name:</label>
                <input type="text" class="form-control form-control-sm" id="lastName" name="last_name">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-5">
                <label for="contactNumber" class="form-label">Contact Number:</label>
                <input type="number" class="form-control form-control-sm" id="contactNumber" name="contact_number" maxlength="11">
            </div>
            <div class="col-md-7">
                <label for="email" class="form-label">Email:</label>
                <input type="text" class="form-control form-control-sm" id="email" name="email">
            </div>
        </div>
        <div class="mb-3">
            <label for="registerUsername" class="form-label">Username:</label>
            <input type="text" class="form-control form-control-sm" id="registerUsername" name="username">
        </div>
        <div class="mb-3">
            <label for="registerPassword" class="form-label">Password:</label>
            <input type="password" class="form-control form-control-sm" id="registerPassword" name="password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Register</button>
    </form>
</div>

                    <!-- Modal for Updating User -->
                    <div id="modal-<?= $userID ?>" class="modal">
                        <div class="modal-content">
                            <a href="#" class="close">&times;</a>
                            <h3>Update User</h3>
                            <form action="./endpoint/update-user.php" method="POST">
                                <input type="hidden" name="tbl_user_id" value="<?= $userID ?>">
                                <label>First Name:</label>
                                <input type="text" name="first_name" value="<?= $firstName ?>">
                                <label>Last Name:</label>
                                <input type="text" name="last_name" value="<?= $lastName ?>">
                                <label>Contact Number:</label>
                                <input type="text" name="contact_number" value="<?= $contactNumber ?>">
                                <label>Email:</label>
                                <input type="email" name="email" value="<?= $email ?>">
                                <label>Username:</label>
                                <input type="text" name="username" value="<?= $username ?>">
                                <label>Password:</label>
                                <input type="password" name="password" value="<?= $password ?>">
                                <button type="submit">Update</button>
                            </form>
                        </div>
            </div>
            <hr>


            <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>List of Users</h2>
            <div class="form-group mb-0">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search users...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered" id="userTable">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                <?php $counter = 1; foreach ($users as $user): ?>
                        <tr id="user-<?= $user['tbl_user_id'] ?>">
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($user['first_name']) ?></td>
                            <td><?= htmlspecialchars($user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['contact_number']) ?></td>
                            <td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary editBtn"
                                    data-toggle="modal"
                                    data-target="#updateUserModal"
                                    data-id="<?= htmlspecialchars($user['tbl_user_id']) ?>"
                                    data-firstname="<?= htmlspecialchars($user['first_name']) ?>"
                                    data-lastname="<?= htmlspecialchars($user['last_name']) ?>"
                                    data-contact="<?= htmlspecialchars($user['contact_number']) ?>"
                                    data-email="<?= htmlspecialchars($user['email']) ?>"
                                    data-username="<?= htmlspecialchars($user['username']) ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="./endpoint/delete-user.php?user=<?= htmlspecialchars($user['tbl_user_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">

                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
 </main>



 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css">

    <!-- Bootstrap Modal for Updating User -->
    <div class="modal fade" id="updateUserModal" tabindex="-1" aria-labelledby="updateUserLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="./endpoint/update-user.php" method="POST">
                    <input type="hidden" name="tbl_user_id" id="updateUserID">

                    <div class="form-group">
                        <label>First Name:</label>
                        <input type="text" class="form-control" id="updateFirstName" name="first_name">
                    </div>
                    <div class="form-group">
                        <label>Last Name:</label>
                        <input type="text" class="form-control" id="updateLastName" name="last_name">
                    </div>
                    <div class="form-group">
                        <label>Contact Number:</label>
                        <input type="text" class="form-control" id="updateContactNumber" name="contact_number">
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" class="form-control" id="updateEmail" name="email">
                    </div>
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" class="form-control" id="updateUsername" name="username">
                    </div>
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" class="form-control" id="updatePassword" name="password">
                    </div>
                    <button type="submit" class="btn btn-dark form-control">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap JS and jQuery -->
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

    $(document).ready(function () {
      // When an Edit button is clicked, populate the modal with the selected user’s data
      $(".editBtn").on("click", function () {
        let userID = $(this).data("id");
        let firstName = $(this).data("firstname");
        let lastName = $(this).data("lastname");
        let contact = $(this).data("contact");
        let email = $(this).data("email");
        let username = $(this).data("username");

        // Populate the modal fields with the user's data
        $("#updateUserID").val(userID);
        $("#updateFirstName").val(firstName);
        $("#updateLastName").val(lastName);
        $("#updateContactNumber").val(contact);
        $("#updateEmail").val(email);
        $("#updateUsername").val(username);
      });

      // Delete user function
      window.delete_user = function(id) {
        if (confirm("Are you sure you want to delete this user?")) {
          window.location = "./endpoint/delete-user.php?user=" + id;
        }
      };

      // Search functionality
      $("#searchInput").on("keyup", function () {
        var value = $(this).val().toLowerCase();
        $("#tableBody tr").filter(function () {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
      });
    });
  </script>

 </body>
 </html>

 <?php $conn = null; ?>
