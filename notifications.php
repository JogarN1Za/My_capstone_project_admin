<?php
include('conn/connect.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$reset_success_message = '';
if (isset($_GET['reset_success'])) {
    if ($_GET['reset_success'] == '1') {
        $reset_success_message = "Quiz reset successfully!";
    } elseif ($_GET['reset_success'] == '0' && isset($_GET['error'])) {
        $reset_success_message = $_GET['error'];
    }
}

$purchase_cleared_message = '';
if (isset($_GET['purchase_cleared'])) {
    if ($_GET['purchase_cleared'] == '1') {
        $purchase_cleared_message = "Purchase marked as cleared!";
    } elseif ($_GET['purchase_cleared'] == '0' && isset($_GET['error'])) {
        $purchase_cleared_message = $_GET['error'];
    }
}

// Fetch all pending reports
$stmtReports = $conn->prepare("SELECT * FROM reports WHERE status = 'Pending' ORDER BY created_at DESC");
$stmtReports->execute();
$allReports = $stmtReports->fetchAll(PDO::FETCH_ASSOC);

// Separate quiz reports and other reports based on the presence of quiz_id
$quizReports = array_filter($allReports, function ($report) {
    return isset($report['quiz_id']) && $report['quiz_id'] > 0;
});

$otherReports = array_filter($allReports, function ($report) {
    return !isset($report['quiz_id']) || $report['quiz_id'] <= 0;
});

$pendingQuizCount = count($quizReports);
$pendingOtherCount = count($otherReports);

// Fetch new card purchases (only those not cleared)
$stmtCardPurchases = $conn->prepare("SELECT p.*, at.username, ct.title AS card_name
                                         FROM purchases p
                                         JOIN admin_tb at ON p.user_id = at.tbl_user_id
                                         JOIN cards ct ON p.card_id = ct.id
                                         WHERE p.cleared IS NULL OR p.cleared = 0
                                         ORDER BY p.purchased_at DESC");
$stmtCardPurchases->execute();
$newCardPurchases = $stmtCardPurchases->fetchAll(PDO::FETCH_ASSOC);

$pendingCardPurchaseCount = count($newCardPurchases);

// Fetch new tutorial purchases
$stmtTutorialPurchases = $conn->prepare("SELECT tp.*, at.username, pt.part_title
                                          FROM tbl_purchase tp
                                          JOIN admin_tb at ON tp.user_id = at.tbl_user_id
                                          JOIN parts pt ON tp.part_id = pt.id
                                          WHERE tp.cleared IS NULL OR tp.cleared = 0
                                          ORDER BY tp.purchase_date DESC");
$stmtTutorialPurchases->execute();
$newTutorialPurchases = $stmtTutorialPurchases->fetchAll(PDO::FETCH_ASSOC);

$pendingTutorialPurchaseCount = count($newTutorialPurchases);

$pendingTotalCount = $pendingQuizCount + $pendingOtherCount + $pendingCardPurchaseCount + $pendingTutorialPurchaseCount;

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
    font-size: 14px;        /* Palakihin ang text */
    padding: 12px 20px;        /* Palakihin ang click area */
    font-weight: bold;        /* Gawing bold */
    color: #ffffff;          /* Pwede mo rin i-adjust color kung gusto mo */
    display: block;          /* Para mas maging buong linya ang clickable */
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

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050; /* Ensure it's above other elements */
        }
        .toast {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .toast.show {
            opacity: 1;
        }
        .toast-header .btn-close {
            margin-left: auto;
        }
        .report-item.hidden {
            display: none !important;
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
  <div class="container mt-5 notification-container">
      <h2>Notifications</h2>

      <?php if (!empty($reset_success_message)): ?>
          <div class="toast-container">
          <div class="toast bg-success text-white fade show" role="alert" aria-live="assertive" aria-atomic="true">
                  <div class="toast-header">
                      <strong class="mr-auto">Success</strong>
                      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                  </div>
                  <div class="toast-body">
                      <?= $reset_success_message ?>
                  </div>
              </div>
          </div>
      <?php endif; ?>

      <?php if (!empty($purchase_cleared_message)): ?>
          <div class="toast-container">
              <div class="toast bg-info text-white fade show" role="alert" aria-live="assertive" aria-atomic="true">
                  <div class="toast-header">
                      <strong class="mr-auto">Info</strong>
                      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                  </div>
                  <div class="toast-body">
                      <?= $purchase_cleared_message ?>
                  </div>
              </div>
          </div>
      <?php endif; ?>

      <h3>Quiz Reports</h3>
      <div class="notification-list">
          <?php if ($pendingQuizCount > 0): ?>
              <ul class="list-group">
                  <?php foreach ($quizReports as $report): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                          <div>
                              <strong>Report by User ID:</strong> <?= $report['user_id']; ?><br>
                              <strong>Comment:</strong> <?= $report['comment']; ?><br>
                              <strong>Quiz ID:</strong> <?= $report['quiz_id']; ?><br>
                              <small>Submitted at: <?= $report['created_at']; ?></small>
                          </div>
                          <div>
                              <a href="view_report.php?id=<?= $report['id']; ?>" class="btn btn-outline-info ">View To Remove</a>
                              <form action="reset_quiz.php" method="POST" class="d-inline-block">
                                  <input type="hidden" name="user_id" value="<?= $report['user_id']; ?>">
                                  <input type="hidden" name="quiz_id" value="<?= $report['quiz_id']; ?>">
                                  <button type="submit" class="btn btn-outline-success">Reset Quiz</button>
                              </form>
                          </div>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php else: ?>
              <div class="alert alert-info mt-3">No pending quiz reports.</div>
          <?php endif; ?>
      </div>

      <h3 class="mt-4">Other Reports</h3>
      <div class="notification-list">
          <?php if ($pendingOtherCount > 0): ?>
              <ul class="list-group">
                  <?php foreach ($otherReports as $report): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                          <div>
                              <strong>Report by User ID:</strong> <?= $report['user_id']; ?><br>
                              <strong>Comment:</strong> <?= $report['comment']; ?><br>
                              <?php if (isset($report['quiz_id']) && $report['quiz_id'] > 0): ?>
                                  <strong>Quiz ID:</strong> <?= $report['quiz_id']; ?><br>
                              <?php endif; ?>
                              <small>Submitted at: <?= $report['created_at']; ?></small>
                          </div>
                          <div>
                              <a href="view_report.php?id=<?= $report['id']; ?>" class="btn btn-outline-info">View</a>
                          </div>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php else: ?>
              <div class="alert alert-info mt-3">No other pending reports.</div>
          <?php endif; ?>
      </div>

      <h3 class="mt-4">Card Purchases</h3>
      <div class="notification-list">
          <?php if ($pendingCardPurchaseCount > 0): ?>
              <ul class="list-group">
                  <?php foreach ($newCardPurchases as $purchase): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                          <div>
                              <strong>New Card Purchase!</strong>
                              User: <?= htmlspecialchars($purchase['username']) ?> (ID: <?= $purchase['user_id'] ?>) bought card: <?= htmlspecialchars($purchase['card_name']) ?> (ID: <?= $purchase['card_id'] ?>) at <?= $purchase['purchased_at'] ?>
                          </div>
                          <div>
                              <form method="post" action="clear_purchase.php" class="d-inline-block">
                                  <input type="hidden" name="purchase_id" value="<?= $purchase['id'] ?>">
                                  <button type="submit" class="btn btn-sm btn-outline-secondary">Mark as Cleared</button>
                              </form>
                          </div>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php else: ?>
              <div class="alert alert-info mt-3">No new card purchases.</div>
          <?php endif; ?>
      </div>

      <h3 class="mt-4">Tutorial Purchases</h3>
      <div class="notification-list">
          <?php if ($pendingTutorialPurchaseCount > 0): ?>
              <ul class="list-group">
                  <?php foreach ($newTutorialPurchases as $purchase): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center">
                          <div>
                              <strong>New Tutorial Purchase!</strong>
                              User: <?= htmlspecialchars($purchase['username']) ?> (ID: <?= $purchase['user_id'] ?>) bought tutorial: <?= htmlspecialchars($purchase['part_title']) ?> (ID: <?= $purchase['part_id'] ?>) at <?= $purchase['purchase_date'] ?>
                          </div>
                          <div>
                              <form method="post" action="clear_tutorial_purchase.php" class="d-inline-block">
                                  <input type="hidden" name="purchase_id" value="<?= $purchase['purchase_id'] ?>">
                                  <button type="submit" class="btn btn-sm btn-outline-secondary">Mark as Cleared</button>
                              </form>
                          </div>
                      </li>
                  <?php endforeach; ?>
              </ul>
          <?php else: ?>
              <div class="alert alert-info mt-3">No new tutorial purchases.</div>
          <?php endif; ?>
      </div>

  </div>

</main>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

    $(document).ready(function(){
        $('.toast').toast('show');
    });
</script>
</body>
</html>

<?php $conn = null; ?>