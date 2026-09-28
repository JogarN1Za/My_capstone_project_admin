<?php
include('conn/connect.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// ✅ Handle Difficulty Submission (Add New)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submitDifficulty'])) {
    $selected_diff = $_POST['selected_diff'];

    if (!empty($selected_diff)) {
        try {
            $stmt = $conn->prepare("INSERT INTO diff (select_diff) VALUES (:selected_diff)");
            $stmt->execute([':selected_diff' => $selected_diff]);

            echo "<script>alert('✅ Difficulty added successfully!');</script>";
            echo "<script>window.location.href='dificulty.php';</script>";
        } catch (PDOException $e) {
            die("<b>❌ Database Error:</b> " . $e->getMessage());
        }
    } else {
        echo "<script>alert('⚠️ Please select a difficulty!');</script>";
    }
}

// ✅ Handle Difficulty Update (Edit Existing)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updateDifficulty'])) {
    $id = $_POST['diff_id'];
    $updated_diff = $_POST['updated_diff'];

    if (!empty($id) && !empty($updated_diff)) {
        try {
            $stmt = $conn->prepare("UPDATE diff SET select_diff = :updated_diff WHERE id = :id");
            $stmt->execute([':updated_diff' => $updated_diff, ':id' => $id]);

            echo "<script>alert('✅ Difficulty updated successfully!');</script>";
            echo "<script>window.location.href='dificulty.php';</script>";
        } catch (PDOException $e) {
            die("<b>❌ Database Error:</b> " . $e->getMessage());
        }
    } else {
        echo "<script>alert('⚠️ Please select a difficulty!');</script>";
    }
}

// ✅ Handle Delete Difficulty
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM diff WHERE id = :id");
        $stmt->execute([':id' => $delete_id]);

        echo "<script>alert('🗑 Difficulty deleted successfully!');</script>";
        echo "<script>window.location.href='dificulty.php';</script>";
    } catch (PDOException $e) {
        die("<b>❌ Database Error:</b> " . $e->getMessage());
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
    color: #ffffff;           /* Pwede mo rin i-adjust color kung gusto mo */
    display: block;           /* Para mas maging buong linya ang clickable */
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
    
    <!-- Input Section -->
    <div class="container mt-3">
    <h4>Enter Difficulty</h4>

    <!-- ✅ Form to Submit Difficulty -->
    <form action="" method="POST">
    <div class="form-group">
        <label>Insert Difficulty:</label>
        <input type="text" name="selected_diff" class="form-control" required>
    </div>
    <button type="submit" name="submitDifficulty" class="btn btn-primary">Submit</button>
</form>

</div>


    <!-- ✅ Table to Display Saved Difficulties -->
    <div class="container mt-4">
    <h4>Data Table</h4>
    <table class="table table-bordered"  style="color: white">
        <thead>
            <tr>
                <th>No.</th>
                <th>Difficulties</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $conn->prepare("SELECT * FROM diff ORDER BY id DESC");
            $stmt->execute();
            $difficulties = $stmt->fetchAll();
            $i = 1;

            foreach ($difficulties as $row) {
                echo "<tr>
                    <td>{$i}</td>
                    <td>{$row['select_diff']}</td>
                    <td>
                        <button class='btn btn-success btn-sm edit-btn' 
                            data-id='{$row['id']}' 
                            data-diff='{$row['select_diff']}' 
                            data-toggle='modal' 
                            data-target='#updateDifficultyModal'>
                            ✏ Edit
                        </button>
                        <a href='dificulty.php?delete_id={$row['id']}' 
                            class='btn btn-danger btn-sm delete-btn'
                            onclick='return confirm(\"Are you sure you want to delete this difficulty?\")'>
                            🗑 Delete
                        </a>
                    </td>
                </tr>";
                $i++;
            }
            ?>
        </tbody>
    </table>
</div>
          </div>
</main>
<style>

    /* Style for the modal backdrop (the dark overlay) */
    .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.8); /* Adjust the RGBA values for your desired color and opacity */
    }

    /* Style for the modal content (the white box) */
    .modal-content {
        background-color:#111827; /* Change this to your desired background color */
        color: #212529; /* Adjust text color if needed for better contrast */
        border: 1px solid rgba(0, 0, 0, 0.2); /* Adjust border color if desired */
    }

    /* Style for the modal header */
    .modal-header {
        background-color:#727883; /* Change this to your desired header background color */
        color: #212529; /* Adjust header text color if needed */
        border-bottom: 1px solid #dee2e6; /* Adjust header border color if desired */
    }

    /* Style for the modal title */
    .modal-title {
        color: #212529; /* Adjust title text color if needed */
    }

    /* Style for the modal body */
    .modal-body {
        background-color:#111827; /* Ensure body background is consistent if modal-content is changed */
        color: #fff; /* Adjust body text color if needed */
    }

    /* Style for the modal footer */
    .modal-footer {
        background-color: #e9ecef; /* Change this to your desired footer background color */
        border-top: 1px solid #dee2e6; /* Adjust footer border color if desired */
    }

    /* Style for the close button */
    .modal-header .close {
        color: #000; /* Adjust close button color if needed */
        opacity: 0.7;
    }

    .modal-header .close:hover {
        opacity: 0.9;
    }
</style>
 <!-- ✅ Update Difficulty Modal -->
<div class="modal fade" id="updateDifficultyModal" tabindex="-1" role="dialog" aria-labelledby="updateDifficultyLabel" aria-hidden="true" style=background-color: rgba>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateDifficultyLabel">Update Difficulty</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <form action="" method="POST">
                <input type="hidden" name="diff_id" id="updateDiffId">
                <div class="form-group">
                    <label>Update Difficulty:</label>
                    <input type="text" name="updated_diff" id="updateDiffInput" class="form-control" required>
                </div>
                <button type="submit" name="updateDifficulty" class="btn btn-success">Update</button>
            </form>
            </div>
        </div>
    </div>
</div>

 <!-- ✅ Bootstrap & jQuery -->
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
    $(".edit-btn").click(function () {
        let diffId = $(this).data("id");
        let difficulty = $(this).data("diff");

        $("#updateDiffId").val(diffId);
        $("#updateDiffDropdown").val(difficulty); // Set dropdown to current value
    });
});
 </script>
 </body>
 </html>

 <?php $conn = null; ?>
