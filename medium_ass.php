<?php
include('conn/connect.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// ✅ Fetch Categories with Medium Difficulty for the dropdown
$categories = $conn->query("
    SELECT c.id, c.cat_title, d.select_diff AS difficulty
    FROM category c
    JOIN diff d ON c.diff_id = d.id
    WHERE d.select_diff = 'Medium'
    ORDER BY c.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Fetch Parts with Medium Difficulty for the dropdown
$parts = $conn->query("
    SELECT p.id, p.part_title, p.part_dis, d.select_diff AS difficulty
    FROM parts p
    LEFT JOIN diff d ON p.diff_id = d.id
    WHERE d.select_diff = 'Medium'
    ORDER BY p.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ✅ Fetch Quizzes with Medium Difficulty for the table
try {
    $stmt = $conn->prepare("
    SELECT q.id AS quiz_id, q.quiz_title, q.countdown,
            c.cat_title, c.id AS category_id, p.part_title, p.id AS part_id, d.select_diff AS difficulty
    FROM quizzes q
    LEFT JOIN category c ON q.category_id = c.id
    LEFT JOIN parts p ON q.part_id = p.id
    LEFT JOIN diff d ON c.diff_id = d.id
    WHERE d.select_diff = 'Medium'
    ORDER BY q.id ASC
");
    $stmt->execute();
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<b>❌ Error fetching quizzes:</b> " . $e->getMessage());
}

// ✅ Handle Form Submission (Create Quiz) - No change needed
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['createQuiz'])) {
    $quiz_title = trim($_POST['quiz_title']);
    $category_id = $_POST['category_id'];
    $part_id = $_POST['part_id'];
    $countdown = $_POST['countdown'];
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_answer = $_POST['correct_answer'];

    if (!empty($quiz_title) && !empty($category_id) && !empty($part_id) && !empty($countdown) && !empty($question_text)) {
        try {
            // Insert Quiz
            $stmt = $conn->prepare("INSERT INTO quizzes (quiz_title, category_id, part_id, countdown)
                                     VALUES (:quiz_title, :category_id, :part_id, :countdown)");
            $stmt->execute([
                ':quiz_title' => $quiz_title,
                ':category_id' => $category_id,
                ':part_id' => $part_id,
                ':countdown' => $countdown
            ]);
            $quiz_id = $conn->lastInsertId();

            // Insert Question
            $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_answer)
                                     VALUES (:quiz_id, :question, :option_a, :option_b, :option_c, :option_d, :correct_answer)");
            $stmt->execute([
                ':quiz_id' => $quiz_id,
                ':question' => $question_text,
                ':option_a' => $option_a,
                ':option_b' => $option_b,
                ':option_c' => $option_c,
                ':option_d' => $option_d,
                ':correct_answer' => $correct_answer
            ]);

            echo "<script>alert('✅ Quiz added successfully!'); window.location.href='medium_ass.php';</script>";
        } catch (PDOException $e) {
            die("<b>❌ Database Error:</b> " . $e->getMessage());
        }
    }
}

// ✅ Handle Quiz Update - No change needed
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updateQuiz'])) {
    $quiz_id = $_POST['quiz_id'];
    $quiz_title = trim($_POST['quiz_title']);
    $category_id = $_POST['category_id'];
    $part_id = $_POST['part_id'];
    $countdown = $_POST['countdown'];
    $choice_A = trim($_POST['choice_A']);
    $choice_B = trim($_POST['choice_B']);
    $choice_C = trim($_POST['choice_C']);
    $choice_D = trim($_POST['choice_D']);
    $correct_answer = $_POST['correct_answer'];

    if (!empty($quiz_title) && !empty($category_id) && !empty($part_id) && !empty($countdown)) {
        try {
            // ✅ Update the quiz table
            $stmt = $conn->prepare("UPDATE quizzes
                                     SET quiz_title = :quiz_title, category_id = :category_id,
                                         part_id = :part_id, countdown = :countdown
                                     WHERE id = :quiz_id");
            $stmt->execute([
                ':quiz_title' => $quiz_title,
                ':category_id' => $category_id,
                ':part_id' => $part_id,
                ':countdown' => $countdown,
                ':quiz_id' => $quiz_id
            ]);

            // ✅ Update quiz questions
            $stmt = $conn->prepare("UPDATE quiz_questions
                                     SET option_a = :option_a, option_b = :option_b,
                                         option_c = :option_c, option_d = :option_d,
                                         correct_answer = :correct_answer
                                     WHERE quiz_id = :quiz_id");
            $stmt->execute([
                ':option_a' => $choice_A,
                ':option_b' => $choice_B,
                ':option_c' => $choice_C,
                ':option_d' => $choice_D,
                ':correct_answer' => $correct_answer,
                ':quiz_id' => $quiz_id
            ]);

            echo "<script>alert('✅ Quiz updated successfully!'); window.location.href='medium_ass.php';</script>";
        } catch (PDOException $e) {
            die("<b>❌ Update Error:</b> " . $e->getMessage());
        }
    } else {
        echo "<script>alert('❌ Please fill in all required fields!');</script>";
    }
}

// ✅ Handle Quiz Deletion - No change needed
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deleteQuiz'])) {
    $quiz_id = $_POST['id'];

    try {
        // Delete quiz questions first (if there are any)
        $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_id = :quiz_id");
        $stmt->execute([':quiz_id' => $quiz_id]);

        // Delete the quiz itself
        $stmt = $conn->prepare("DELETE FROM quizzes WHERE id = :quiz_id");
        $stmt->execute([':quiz_id' => $quiz_id]);

        echo "<script>alert('✅ Quiz deleted successfully!'); window.location.href='medium_ass.php';</script>";
    } catch (PDOException $e) {
        die("<b>❌ Error deleting quiz:</b> " . $e->getMessage());
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
   .form-select {
   height: 30px;
   padding: 5px 10px;
   border: 1px solid #ccc;
   border-radius: 4px;
   background-color: #fff;
   color: #333;
   font-size: 14px;
   cursor: pointer;
   transition: border-color 0.2s ease-in-out;
   }

   .form-select option {
   background-color: #fff;
   color: #333;
   font-size: 14px;
   padding: 5px 10px;
   border-radius: 4px;
   cursor: pointer;
   }

.form-select:hover {
    border-color: #007bff; /* Change border color on hover */
}
.btn-create-line:hover {
    background-color: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.12);
}

.table-responsive {
    overflow-x: auto; /* Keep horizontal scrolling if needed */
    overflow-y: auto; /* Enable vertical scrolling */
    max-height: 400px; /* Adjust this value as needed */
}

.modal-content {
    background-color: #1e293b; /* Dark blue-grey background */
    color: #f0f9ff; /* Light grey text */
    border: 1px solid #334155; /* Darker border */
    border-radius: 0.5rem; /* Optional: Rounded corners */
}

.modal-header {
    background-color: #334155; /* Slightly lighter header */
    color: #f0f9ff;
    border-bottom: 1px solid #475569;
    padding: 1rem 1.5rem;
    border-top-left-radius: 0.5rem; /* Match content border */
    border-top-right-radius: 0.5rem; /* Match content border */
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

<div class="container mt-4">
    <br>
    <h2>Create a New Quiz (Medium)</h2>
    <form action="" method="POST" class="row g-3">
        <div class="col-md-6">
            <label for="quiz_title" class="form-label"><i class="bx bx-heading"></i> Quiz Title:</label>
            <input type="text" name="quiz_title" class="form-control" id="quiz_title" required>
        </div>

        <div class="col-md-6">
            <br>
            <label for="category_id" class="form-label"><i class="bx bx-folder"></i> Category:</label>
            <select name="category_id" class="form-select" id="category_id" required>
                <option value="" disabled selected>Select Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>">
                        <?= htmlspecialchars($category['cat_title']) ?> (<?= htmlspecialchars($category['difficulty']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <br>
            <label for="part_id" class="form-label"><i class="bx bx-puzzle"></i> Part:</label>
            <select name="part_id" class="form-select" id="part_id" required>
                <option value="" disabled selected>Select Part</option>
                <?php
                if (!$parts) {
                    echo "<option disabled>No Parts Found</option>";
                } else {
                    foreach ($parts as $row) {
                        echo "<option value='{$row['id']}'>{$row['part_title']} - {$row['part_dis']} (" . htmlspecialchars($row['difficulty'] ?? 'N/A') . ")</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="countdown" class="form-label"><i class="bx bx-timer"></i> Countdown (Seconds):</label>
            <input type="number" name="countdown" class="form-control" id="countdown" required>
        </div>

        <div class="col-12">
            <label for="question_text" class="form-label"><i class="bx bx-question-mark"></i> Question:</label>
            <textarea class="form-control" name="question_text" id="question_text" rows="3" required></textarea>
        </div>

        <div class="col-12">
            <label class="form-label"><i class="bx bx-list-ul"></i> Options:</label>
            <div class="options-container">
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="option_a" placeholder="Option A" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="option_b" placeholder="Option B" required>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="option_c" placeholder="Option C" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="option_d" placeholder="Option D" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <br>
            <label for="correct_answer" class="form-label"><i class="bx bx-check-circle"></i> Correct Answer:</label>
            <select name="correct_answer" class="form-select" id="correct_answer" required>
                <option value="A">Option A</option>
                <option value="B">Option B</option>
                <option value="C">Option C</option>
                <option value="D">Option D</option>
            </select>
        </div>

        <div class="col-12">
            <button type="submit" name="createQuiz" class="btn btn-primary"><i class="bx bx-plus-circle"></i> Create Quiz</button>
        </div>
    </form>

    <div class="mt-4">
        <input type="text" id="quizSearch" class="form-control" placeholder="Search Quizzes...">
    </div>

    <div class="container mt-4">
        <h2>List of Quizzes</h2>
        <div class="table-responsive">
            <div class="table-wrapper">
                <table class="table table-bordered" style="color: white" id="quizTable">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Part</th>
                        <th>Difficulty</th>
                        <th>Countdown</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $rowNumber = 1; ?> <?php foreach ($quizzes as $quiz): ?>
                        <tr>
                            <td><?= $rowNumber; ?></td>
                            <td><?= htmlspecialchars($quiz['quiz_title'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($quiz['cat_title'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($quiz['part_title'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($quiz['difficulty'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($quiz['countdown'] ?? '0') ?>s</td>
                            <td>
                            <button type="button" class="btn btn-sm btn-primary editBtn"
                                data-id="<?= htmlspecialchars($quiz['quiz_id'] ?? '') ?>"
                                data-title="<?= htmlspecialchars($quiz['quiz_title'] ?? '') ?>"
                                data-category="<?= htmlspecialchars($quiz['category_id'] ?? '') ?>"
                                data-part="<?= htmlspecialchars($quiz['part_id'] ?? '') ?>"
                                data-countdown="<?= htmlspecialchars($quiz['countdown'] ?? '') ?>"
                                data-question="<?= htmlspecialchars($quiz['question'] ?? '') ?>"
                                data-choice-a="<?= htmlspecialchars($quiz['choice_A'] ?? '') ?>"
                                data-choice-b="<?= htmlspecialchars($quiz['choice_B'] ?? '') ?>"
                                data-choice-c="<?= htmlspecialchars($quiz['choice_C'] ?? '') ?>"
                                data-choice-d="<?= htmlspecialchars($quiz['choice_D'] ?? '') ?>"
                                data-correct-answer="<?= htmlspecialchars($quiz['correct_answer'] ?? '') ?>">
                                Edit
                            </button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($quiz['quiz_id']) ?>">
                                <button type="submit" name="deleteQuiz" class="btn btn-sm btn-danger ml-2" onclick="return confirm('Are you sure you want to delete this quiz?');">
                                    Delete
                                </button>
                            </form>
                        </td>
                        </tr>
                    <?php $rowNumber++; ?> <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>

</main>
                   
<!-- ✅ Edit Quiz Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Quiz</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
            <form method="POST" action="medium_ass.php">
    <input type="hidden" name="quiz_id" id="edit_id">

    <!-- ✅ Quiz Title -->
    <div class="form-group">
        <label>Quiz Title:</label>
        <input type="text" class="form-control" name="quiz_title" id="edit_title" required>
    </div>

    <!-- ✅ Category -->
    <div class="form-group">
        <label>Category:</label>
        <select name="category_id" class="form-control" id="edit_category" required>
            <option value="" disabled>Select Category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= htmlspecialchars($category['id']) ?>">
                    <?= htmlspecialchars($category['cat_title']) ?> (<?= htmlspecialchars($category['difficulty']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- ✅ Part -->
    <div class="form-group">
        <label>Part:</label>
        <select name="part_id" class="form-control" id="edit_part" required>
            <option value="" disabled>Select Part</option>
            <?php foreach ($parts as $part): ?>
                <option value="<?= $part['id'] ?>">
                    <?= $part['part_title'] ?> - <?= $part['part_dis'] ?>(<?= htmlspecialchars($part['difficulty']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- ✅ Countdown -->
    <div class="form-group">
        <label>Countdown (Seconds):</label>
        <input type="number" class="form-control" name="countdown" id="edit_countdown" required min="10">
    </div>

    <!-- ✅ Questions -->
    <div class="form-group">
    <label>Question:</label>
        <textarea class="form-control" name="question_text" id="edit_question_text" rows="2" required></textarea>
    </div>

    <!-- ✅ Editable Answer Choices -->
    <div class="form-group">
        <label>Option A:</label>
        <input type="text" class="form-control" name="choice_A" id="edit_choice_A" required>
    </div>

    <div class="form-group">
        <label>Option B:</label>
        <input type="text" class="form-control" name="choice_B" id="edit_choice_B" required>
    </div>

    <div class="form-group">
        <label>Option C:</label>
        <input type="text" class="form-control" name="choice_C" id="edit_choice_C" required>
    </div>

    <div class="form-group">
        <label>Option D:</label>
        <input type="text" class="form-control" name="choice_D" id="edit_choice_D" required>
    </div>

    <!-- ✅ Correct Answer -->
    <div class="form-group">
        <label>Correct Answer:</label>
        <select name="correct_answer" id="edit_correct_answer" class="form-control" required>
            <option value="A">Option A</option>
            <option value="B">Option B</option>
            <option value="C">Option C</option>
            <option value="D">Option D</option>
        </select>
    </div>

    <button type="submit" name="updateQuiz" class="btn btn-primary">Update Quiz</button>
</form>
            </div>
        </div>
    </div>
</div>


<script>
    // Sidebar toggle (no changes)
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

    // Right nav dropdown (no changes)
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

    // Edit modal population (no changes)
    document.querySelectorAll('.editBtn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_title').value = this.dataset.title || '';
            document.getElementById('edit_category').value = this.dataset.category || '';
            document.getElementById('edit_part').value = this.dataset.part || '';
            document.getElementById('edit_countdown').value = this.dataset.countdown || '';
            document.getElementById('edit_question_text').value = this.dataset.question || '';
            document.getElementById('edit_choice_A').value = this.dataset.choiceA || '';
            document.getElementById('edit_choice_B').value = this.dataset.choiceB || '';
            document.getElementById('edit_choice_C').value = this.dataset.choiceC || '';
            document.getElementById('edit_choice_D').value = this.dataset.choiceD || '';
            document.getElementById('edit_correct_answer').value = this.dataset.correctAnswer || '';

            $('#editModal').modal('show'); // Show the modal
        });
    });

    // Edit modal part auto-selection (no changes)
    document.querySelectorAll('.editBtn').forEach(button => {
        button.addEventListener('click', function() {
            const quizId = this.dataset.id;
            const selectedPartId = this.dataset.part;

            document.getElementById("edit_id").value = quizId;
            document.getElementById("edit_part").value = selectedPartId; // ✅ Auto-select part

            $('#editModal').modal('show');
        });
    });

    // ✅ Search functionality for the quiz table
    const searchInput = document.getElementById('quizSearch');
    const quizTable = document.getElementById('quizTable');
    const tableBody = quizTable.getElementsByTagName('tbody')[0];
    const tableRows = tableBody.getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();

        for (let i = 0; i < tableRows.length; i++) {
            const rowData = tableRows[i].textContent.toLowerCase();
            if (rowData.includes(searchTerm)) {
                tableRows[i].style.display = '';
            } else {
                tableRows[i].style.display = 'none';
            }
        }
    });
</script>
<script>
    document.querySelectorAll('.editBtn').forEach(button => {
    button.addEventListener('click', function() {
        const quizId = this.dataset.id;
        const selectedPartId = this.dataset.part;

        document.getElementById("edit_id").value = quizId;
        document.getElementById("edit_part").value = selectedPartId; // ✅ Auto-select part

        $('#editModal').modal('show');
    });
});

</script>

<!-- ✅ Required for Bootstrap Modals -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
 </body>
 </html>

 <?php $conn = null; ?>
