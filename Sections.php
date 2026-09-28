<?php
// Database connection using PDO
$servername = "localhost";
$username = "root";
$password = "";
$db = "admin1"; // Your database name

try {
    $conn = new PDO("mysql:host=$servername;dbname=$db", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database Connection Failed: " . $e->getMessage());
}

$error_message = ''; // Initialize error message variable for user feedback

// --- PHP Logic for adding a new Code Exercise ---
if (isset($_POST['addCodeEx'])) {
    $newTitle = trim($_POST['title']);
    $newDescription = trim($_POST['description']);
    $newCategoryId = $_POST['category_id'];
    $newPartId = $_POST['part_id'];

    if (!empty($newTitle) && !empty($newDescription) && !empty($newCategoryId) && !empty($newPartId)) {
        try {
            $stmt = $conn->prepare("INSERT INTO Code_Ex (title, description, category_id, part_id) VALUES (:title, :description, :category_id, :part_id)");
            $stmt->bindParam(':title', $newTitle, PDO::PARAM_STR);
            $stmt->bindParam(':description', $newDescription, PDO::PARAM_STR);
            $stmt->bindParam(':category_id', $newCategoryId, PDO::PARAM_INT);
            $stmt->bindParam(':part_id', $newPartId, PDO::PARAM_INT);
            $stmt->execute();
            header("Location: Sections.php?status=added");
            exit();
        } catch (PDOException $e) {
            $error_message = "❌ Failed to add code exercise: " . $e->getMessage();
        }
    } else {
        $error_message = "All code exercise fields are required.";
    }
}

// --- PHP Logic for updating a Code Exercise ---
if (isset($_POST['updateCodeEx'])) {
    $codeExId = $_POST['code_ex_id'];
    $updatedTitle = trim($_POST['updated_title']);
    $updatedDescription = trim($_POST['updated_description']);
    $updatedCategoryId = $_POST['updated_category_id'];
    $updatedPartId = $_POST['updated_part_id'];

    if (!empty($updatedTitle) && !empty($updatedDescription) && !empty($updatedCategoryId) && !empty($updatedPartId)) {
        try {
            $stmt = $conn->prepare("UPDATE Code_Ex SET title = :title, description = :description, category_id = :category_id, part_id = :part_id WHERE id = :id");
            $stmt->bindParam(':title', $updatedTitle, PDO::PARAM_STR);
            $stmt->bindParam(':description', $updatedDescription, PDO::PARAM_STR);
            $stmt->bindParam(':category_id', $updatedCategoryId, PDO::PARAM_INT);
            $stmt->bindParam(':part_id', $updatedPartId, PDO::PARAM_INT);
            $stmt->bindParam(':id', $codeExId, PDO::PARAM_INT);
            $stmt->execute();
            header("Location: Sections.php?status=updated");
            exit();
        } catch (PDOException $e) {
            $error_message = "❌ Failed to update code exercise: " . $e->getMessage();
        }
    } else {
        $error_message = "All fields are required to update a code exercise.";
    }
}

// --- PHP Logic for deleting a Code Exercise ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM Code_Ex WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        header("Location: Sections.php?status=deleted");
        exit();
    } catch (PDOException $e) {
        $error_message = "❌ Failed to delete code exercise: " . $e->getMessage();
    }
}

// --- Fetch Categories (ONLY 'Easy' difficulty) for dropdowns ---
$categories = [];
try {
    $stmt = $conn->prepare("SELECT c.id, CONCAT(c.cat_title, ' (', d.select_diff, ')') AS cat_display_name
                            FROM category c
                            JOIN diff d ON c.diff_id = d.id
                            WHERE d.select_diff = 'Easy'
                            ORDER BY c.cat_title");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "❌ Failed to load 'Easy' categories: " . $e->getMessage();
}

// --- Fetch Parts (ONLY 'Easy' difficulty) for dropdowns ---
$parts = [];
try {
    $stmt = $conn->prepare("SELECT p.id, CONCAT(p.part_title, ' - ', p.part_dis, ' (', d.select_diff, ')') AS part_display_name
                            FROM parts p
                            JOIN diff d ON p.diff_id = d.id
                            WHERE d.select_diff = 'Easy'
                            ORDER BY p.part_title");
    $stmt->execute();
    $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "❌ Failed to load 'Easy' parts: " . $e->getMessage();
}

// --- Filtering and Search Logic for Code_Ex ---
$filterCategoryId = isset($_GET['filter_category']) ? $_GET['filter_category'] : '';
$filterPartId = isset($_GET['filter_part']) ? $_GET['filter_part'] : '';
$searchQuery = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';

// Base SQL query to fetch Code_Ex entries with their category and part names
$sql = "SELECT ce.id, ce.title, ce.description, ce.category_id, ce.part_id, c.cat_title AS category_name, p.part_title AS part_name
        FROM Code_Ex ce
        JOIN category c ON ce.category_id = c.id
        JOIN parts p ON ce.part_id = p.id
        WHERE 1=1"; // Start with a true condition to easily append AND clauses

$params = []; // Array to store parameters for the prepared statement

// Append filter conditions if they are set
if (!empty($filterCategoryId)) {
    $sql .= " AND ce.category_id = :filter_category_id";
    $params[':filter_category_id'] = $filterCategoryId;
}

if (!empty($filterPartId)) {
    $sql .= " AND ce.part_id = :filter_part_id";
    $params[':filter_part_id'] = $filterPartId;
}

// Append search condition if search query is provided (searching in title and description)
if (!empty($searchQuery)) {
    $sql .= " AND (ce.title LIKE :search_query OR ce.description LIKE :search_query)";
    $params[':search_query'] = '%' . $searchQuery . '%'; // Add wildcards for LIKE search
}

$sql .= " ORDER BY ce.id DESC"; // Order results by ID, newest first

$code_exercises = []; // Initialize array for results
try {
    $stmt = $conn->prepare($sql); // Prepare the SQL query
    $stmt->execute($params); // Execute with the collected parameters
    $code_exercises = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all results
} catch (PDOException $e) {
    $error_message = "❌ Failed to load code exercises: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Code Exercises Management</title>
    <link rel="stylesheet" href="style.css" /><link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        /* Your existing CSS styles go here */
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
        body { background-color: #0a0b0c; color: #e0e0e0; }
        nav.top-nav { background-color:#1f2937; height: 55px; display: flex; align-items: center; padding: 0 20px; position: fixed; top: 20px; left: 0; width: 100%; z-index: 100; border-bottom: 1px solid #334155; justify-content: space-between; }
        .logo { display: flex; align-items: center; }
        .menu-icon { color: #64748b; font-size: 1.3em; cursor: pointer; display: none; }
        .logo-name { font-size: 1.1em; font-weight: bold; color: #f8fafc; margin-left: 10px; }
        .sidebar { position: fixed; top: 75px; left: 0; width: 240px; height: calc(100vh - 75px); background-color: #1e293b; color: #e0e0e0; transform: translateX(-100%); transition: transform 0.3s ease-in-out; z-index: 300; }
        .sidebar.open { transform: translateX(0); }
        .sidebar-content { padding: 15px; overflow-y: auto; height: 100%; }
        .tutorials-heading { font-size: 0.9em; font-weight: bold; margin-bottom: 15px; color: #64748b; text-transform: uppercase; letter-spacing: 0.7px; padding-left: 5px; }
        .lists { list-style: none; padding: 0; margin: 0 0 15px 0; }
        .list-group-heading { font-weight: bold; color: #cbd5e1; padding: 8px 15px; margin-bottom: 3px; font-size: 0.9em; }
        .list-group-heading .nav-link { font-size: 14px; padding: 12px 20px; font-weight: bold; color: #ffffff; display: block; }
        .list-item { margin-bottom: 3px; }
        .nav-link { display: block; color: #a3a3a3; text-decoration: none; padding: 7px 20px; border-radius: 6px; transition: background-color 0.2s ease; font-size: 0.85em; }
        .nav-link:hover { background-color: #334155; color: #f0f0f0; }
        .main { padding: 40px 20px; padding-top: 75px; background-color: #121827; color:#727883; min-height: 100vh; transition: margin-left 0.3s ease-in-out; }
        .main > div { padding: 20px; background-color: #121827; border-radius: 4px; }
        nav.additional-nav { background-color: #374151; height: 20px; display: flex; align-items: center; padding: 0 20px; position: fixed; top: 0; left: 0; width: 100%; z-index: 101; }
        nav.additional-nav .block { display: none; }
        .sidebar-backdrop { position: fixed; top: 75px; left: 0; width: 100%; height: calc(100vh - 75px); background: #111827; z-index: 250; display: none; }
        .sidebar-backdrop.show { display: block; }
        /* Right Nav Section */
        .right-icons { display: flex; align-items: center; position: relative; }
        .right-links-desktop { display: flex; gap: 10px; }
        .right-icons a { color: #64748b; text-decoration: none; margin-left: 10px; font-size: 0.9em; transition: color 0.2s ease; }
        .right-icons a:hover { color: #f8fafc; }
        .dropdown-toggle-icon { color: #f0f0f0; font-size: 1.5em; cursor: pointer; display: none; margin-left: 10px; }
        .right-icons i { font-size: 24px; }
        .right-dropdown { position: absolute; right: 40px; top: 45px; background-color: #1e293b; border: 1px solid #334155; border-radius: 4px; display: none; flex-direction: column; width: 180px; z-index: 999; }
        .right-dropdown a { color: #cbd5e1; text-decoration: none; padding: 10px; border-bottom: 1px solid #334155; display: block; font-size: 0.9em; }
        .right-dropdown a:hover { background-color: #334155; }
        .right-dropdown.show { display: flex; }
        /* Responsive Adjustments */
        @media (min-width: 769px) { .right-links-desktop { display: flex; } .dropdown-toggle-icon, .right-dropdown { display: none !important; } .sidebar { transform: translateX(0) !important; } .main { margin-left: 240px; } .menu-icon { display: none !important; } .sidebar-backdrop { display: none !important; } }
        @media (max-width: 768px) { .right-links-desktop { display: none; } .dropdown-toggle-icon { display: block; } .menu-icon { display: block; margin-right: 10px; } .main { margin-left: 0; width: 100%; } }
        /* Styles for the modal backdrop (the dark overlay) */
        .modal-backdrop { background-color: rgba(0, 0, 0, 0.8); }
        /* Style for the modal content (the white box) */
        .modal-content { background-color:#111827; color: #212529; border: 1px solid rgba(0, 0, 0, 0.2); }
        /* Style for the modal header */
        .modal-header { background-color:#727883; color: #212529; border-bottom: 1px solid #dee2e6; }
        /* Style for the modal title */
        .modal-title { color: #212529; }
        /* Style for the modal body */
        .modal-body { background-color:#111827; color: #fff; }
        /* Style for the modal footer */
        .modal-footer { background-color: #e9ecef; border-top: 1px solid #dee2e6; }
        /* Style for the close button */
        .modal-header .close { color: #000; opacity: 0.7; }
        .modal-header .close:hover { opacity: 0.9; }
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

        <a href="#"><i class='bx bx-log-out'></i></a>
        <a href="notifications.php"><i class='bx bx-bell'></i></a>
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
            <li class="list-item"><a href="category.php" class="nav-link">Manage Category</a></li>
            <li class="list-item"><a href="part.php" class="nav-link">Manage Part</a></li>
            <li class="list-item"><a href="dificulty.php" class="nav-link">Manage Difficulty</a></li> </ul>
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

    <div class="container mt-3">
        <h4>Manage Code Exercises (Code_Ex)</h4>

        <div class="card bg-dark text-light p-3 mb-4">
            <form action="Sections.php" method="POST">
                <div class="form-group">
                    <label for="title">Exercise Title:</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Create a button with red color and CSS" required>
                </div>
                <div class="form-group">
                    <label for="description">Exercise Description:</label>
                    <textarea class="form-control" id="description" name="description" rows="5" placeholder="Explain the exercise steps..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                <?php echo htmlspecialchars($category['cat_display_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="part_id">Part:</label>
                    <select class="form-control" id="part_id" name="part_id" required>
                        <option value="">Select Part</option>
                        <?php foreach ($parts as $part): ?>
                            <option value="<?php echo htmlspecialchars($part['id']); ?>">
                                <?php echo htmlspecialchars($part['part_display_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="addCodeEx" class="btn btn-primary">Add Code Exercise</button>
            </form>
        </div>
        <?php
        // Display status messages
        if (isset($_GET['status'])) {
            echo "<div class='alert alert-";
            switch ($_GET['status']) {
                case 'added':
                    echo "success'>✅ Code exercise added successfully!";
                    break;
                case 'updated':
                    echo "info'>🔄 Code exercise updated successfully!";
                    break;
                case 'deleted':
                    echo "warning'>🗑️ Code exercise deleted successfully!";
                    break;
                default:
                    echo "light'>Action completed.";
                    break;
            }
            echo "</div>";
        }
        if (isset($error_message) && !empty($error_message)) {
            echo "<div class='alert alert-danger'>{$error_message}</div>";
        }
        ?>

        <hr style="border-color: #334155;">

        <div class="card bg-dark text-light p-3 mb-4">
            <h5>Filter Code Exercises</h5>
            <form action="Sections.php" method="GET" class="form-inline">
                <div class="form-group mb-2 mr-2">
                    <label for="filter_category" class="sr-only">Category</label>
                    <select class="form-control" id="filter_category" name="filter_category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>"
                                <?php echo ($filterCategoryId == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['cat_display_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-2 mr-2">
                    <label for="filter_part" class="sr-only">Part</label>
                    <select class="form-control" id="filter_part" name="filter_part">
                        <option value="">All Parts</option>
                        <?php foreach ($parts as $part): ?>
                            <option value="<?php echo htmlspecialchars($part['id']); ?>"
                                <?php echo ($filterPartId == $part['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($part['part_display_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-2 mr-2">
                    <label for="search_query" class="sr-only">Search</label>
                    <input type="text" class="form-control" id="search_query" name="search_query"
                           placeholder="Search title/description" value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <button type="submit" class="btn btn-secondary mb-2">Apply Filters</button>
                <a href="Sections.php" class="btn btn-outline-secondary mb-2 ml-2">Clear Filters</a>
            </form>
        </div>
        </div>


    <div class="container mt-4">
        <h4>Code Exercises Data Table</h4>
        <table class="table table-bordered"  style="color: white">
            <thead>
            <tr>
                <th>No.</th>
                <th>Title</th>
                <th>Description</th>
                <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $i = 1;
            if (count($code_exercises) > 0):
                foreach ($code_exercises as $row):
                    // IMPORTANT: Escaping output for security!
                    $display_title = htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');
                    $display_description = htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8');
            ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td><?php echo $display_title; ?></td>
                        <td><?php echo $display_description; ?></td>
                        <td>
                            <button class='btn btn-success btn-sm edit-btn'
                                        data-id='<?php echo htmlspecialchars($row['id']); ?>'
                                        data-title='<?php echo $display_title; ?>'
                                        data-description='<?php echo $display_description; ?>'
                                        data-categoryid='<?php echo htmlspecialchars($row['category_id']); ?>'
                                        data-partid='<?php echo htmlspecialchars($row['part_id']); ?>'
                                        data-toggle='modal'
                                        data-target='#updateCodeExModal'>
                                        ✏ Edit
                            </button>
                            <a href='Sections.php?delete_id=<?php echo htmlspecialchars($row['id']); ?>'
                               class='btn btn-danger btn-sm delete-btn'
                               onclick='return confirm("Are you sure you want to delete this code exercise?")'>
                                🗑 Delete
                            </a>
                        </td>
                    </tr>
            <?php
                    $i++;
                endforeach;
            else:
                echo "<tr><td colspan='4' class='text-center'>No code exercises found matching the criteria.</td></tr>";
            endif;
            ?>
            </tbody>
        </table>
    </div>
</main>

<div class="modal fade" id="updateCodeExModal" tabindex="-1" role="dialog" aria-labelledby="updateCodeExLabel" aria-hidden="true" style=background-color: rgba>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateCodeExLabel">Update Code Exercise</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <input type="hidden" name="code_ex_id" id="updateCodeExId">
                    <div class="form-group">
                        <label>Exercise Title:</label>
                        <input type="text" name="updated_title" id="updateTitleInput" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Exercise Description:</label>
                        <textarea name="updated_description" id="updateDescriptionInput" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="updated_category_id">Category:</label>
                        <select class="form-control" id="updated_category_id" name="updated_category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                    <?php echo htmlspecialchars($category['cat_display_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="updated_part_id">Part:</label>
                        <select class="form-control" id="updated_part_id" name="updated_part_id" required>
                            <?php foreach ($parts as $part): ?>
                                <option value="<?php echo htmlspecialchars($part['id']); ?>">
                                    <?php echo htmlspecialchars($part['part_display_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="updateCodeEx" class="btn btn-success">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Sidebar toggle (unchanged)
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

    // Right nav dropdown (unchanged)
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

    // Edit button functionality (adapted for Code_Ex table columns)
    $(document).ready(function () {
        $(".edit-btn").click(function () {
            let codeExId = $(this).data("id");
            let title = $(this).data("title");
            let description = $(this).data("description");
            let categoryId = $(this).data("categoryid");
            let partId = $(this).data("partid");

            $("#updateCodeExId").val(codeExId);
            $("#updateTitleInput").val(title);
            $("#updateDescriptionInput").val(description);
            $("#updated_category_id").val(categoryId);
            $("#updated_part_id").val(partId);

            $("#updateCodeExModal").modal('show');
        });
    });
</script>
</body>
</html>

<?php $conn = null; // Close the database connection ?>
