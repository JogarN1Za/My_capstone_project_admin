<?php
include('conn/connect.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Handle card creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['card_id'])) {
    $title = htmlspecialchars($_POST['title'] ?? '');
    $description = htmlspecialchars($_POST['description'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $color = htmlspecialchars($_POST['color'] ?? '#FFFFFF');
    $expiration_date = $_POST['expiration_date'] ?? null; // Get the expiration date

    // Save card details to the database
    $stmt = $conn->prepare("INSERT INTO cards (title, description, price, color, expiration_date) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $description, $price, $color, $expiration_date])) {
        $_SESSION['success'] = "Card successfully posted!";
    } else {
        $_SESSION['error'] = "Failed to post the card.";
    }

    // Redirect to prevent duplicate submissions
    header("Location: messages.php");
    exit();
}

// Fetch the count of pending notifications
$stmt = $conn->prepare("SELECT COUNT(*) FROM reports WHERE status = 'Pending'");
$stmt->execute();
$pendingTotalCount = $stmt->fetchColumn();
// Handle card updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_id'])) {
    $card_id = (int)$_POST['card_id'];
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $price = (int)$_POST['price'];
    $color = htmlspecialchars($_POST['color']);
    $expiration_date = $_POST['expiration_date'] ?? null; // Get the expiration date from the form

    // Update card details in the database
    $stmt = $conn->prepare("UPDATE cards SET title = ?, description = ?, price = ?, color = ?, expiration_date = ? WHERE id = ?");
    if ($stmt->execute([$title, $description, $price, $color, $expiration_date, $card_id])) {
        $_SESSION['success'] = "Card updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update the card.";
    }

    // Redirect to prevent duplicate submissions
    header("Location: messages.php");
    exit();
}

// Fetch existing cards to display
$stmt = $conn->prepare("SELECT * FROM cards ORDER BY created_at DESC");
$stmt->execute();
$allCards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define rarity order and colors (same as about.php and home.php)
$rarityOrder = [
    'Common' => ['#CD7F32', '#C0C0C0'],
    'Uncommon' => ['#FFD700'],
    'Rare' => ['#E5E4E2'],
    'Epic' => ['#50C878'],
    'Legendary' => ['#B9F2FF'],
];

// Group all cards by rarity
$cardsByRarity = [];
foreach ($allCards as $card) {
    $rarity = null;
    foreach ($rarityOrder as $key => $colors) {
        if (in_array($card['color'], $colors)) {
            $rarity = $key;
            break;
        }
    }
    if ($rarity) {
        $cardsByRarity[$rarity][] = $card;
    }
}
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
   .card-form label i {
        margin-right: 5px;
    }

    .line-input {
        border: none;
        border-bottom: 1px solid #ccc; /* Add a bottom border to simulate a line */
        padding: 0.375rem 0; /* Adjust padding to control the line's vertical space */
        border-radius: 0; /* Remove any border radius */
        background-color: transparent; /* Make the background transparent */
        box-shadow: none !important; /* Remove any default box shadow on focus */
    }

    .line-input:focus {
        border-bottom: 2px solid #007bff; /* Highlight the line on focus */
    }

    .color-options {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-top: 0.5rem;
    }

    .color-options input[type="radio"] {
        display: none;
    }

    .color-options label {
        display: inline-block;
        width: 30px;
        height: 30px;
        border-radius: 5px;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #ccc;
        text-indent: -9999px;
        overflow: hidden;
    }

    .color-options input[type="radio"]:checked + label {
        border: 2px solid #007bff;
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
    }

    .color-options label:hover {
        transform: scale(1.1);
        transition: transform 0.15s ease-in-out;
    }

    .color-options {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-top: 0.5rem;
        flex-wrap: wrap;
    }

    .color-option {
        display: flex;
        align-items: center;
        gap: 5px; /* Space between color box and name */
    }

    .color-option input[type="radio"] {
        display: none;
    }

    .color-option label {
        display: inline-block;
        width: 20px; /* Smaller color boxes */
        height: 20px;
        border-radius: 3px;
        cursor: pointer;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #ccc;
        overflow: hidden;
    }

    .color-option input[type="radio"]:checked + label {
        border: 2px solid #007bff;
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
    }

    .color-option span {
        font-size: 0.9rem;
        color: #333;
    }

        /*Container*/
        .card-container {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: space-between;
    max-width: 350px;
    margin: 20px auto;
    border-radius: 8px;
    padding: 15px;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden; /* Clip the pseudo-element */
}

.card-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 60px;
    height: 60px;
    background-color: #007bff;
    clip-path: polygon(0 0, 100% 0, 0 100%); /* Diagonal cut */
    border-top-left-radius: 8px;
    opacity: 0.1; /* Make it subtle */
}

/* Rest of your .card and other styles remain the same */

.card {
    position: relative;
    background: linear-gradient(135deg, #f9f9f9, #fff); /* Subtle diagonal gradient */
    border-radius: 12px; /* Slightly more rounded */
    padding: 25px; /* Increased padding */
    width: 100%;
    color: #333;
    margin: 0 auto;
    word-break: break-word;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); /* Enhanced shadow */
    transition: transform 0.2s ease-in-out; /* Add subtle hover effect */
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
}

.corner-deco {
    position: absolute;
    top: 0;
    right: 0;
    width: 60px; /* Slightly larger */
    height: 60px;
    clip-path: polygon(100% 0, 0% 0%, 100% 100%);
    border-top-right-radius: 12px;
}

h5.card-title {
    font-size: 1.1rem;
    color: #777;
    margin-bottom: 10px;
}

h5.card-title i {
    margin-right: 8px;
}

h1 {
    font-size: 1.8rem; /* More prominent title */
    line-height: 1.4;
    margin-bottom: 15px;
    text-align: left;
    color: #222;
}

p {
    font-size: 0.95rem;
    color: #555;
    text-align: left;
    margin-bottom: 10px;
    line-height: 1.6; /* Improved readability */
}

p span {
    color: #007bff;
    font-size: 0.85rem;
    font-weight: bold;
    margin-right: 5px;
}

.icons {
    display: flex;
    justify-content: flex-start;
    gap: 15px;
    margin-top: 20px;
    position: relative;
    bottom: auto;
    right: auto;
}

.icons i {
    color: #444;
    font-size: 26px; /* Slightly larger icons */
}

.price {
    font-weight: bold;
    margin-bottom: 15px;
    text-align: left; /* Align price to the left */
    color: #333;
    font-size: 1.1rem;
}

.buttons {
    display: flex;
    justify-content: flex-end; /* Align buttons to the right */
    gap: 8px;
    margin-top: 10px;
}

.buttons button {
    font-size: 0.9rem;
    padding: 8px 12px;
    border-radius: 5px;
    border: 1px solid #ccc;
    cursor: pointer;
}

.buttons .btn-warning {
    background-color: #ffc107;
    color: #212529;
    border-color: #ffc107;
}

.buttons .btn-danger {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}

.buttons .btn-warning:hover,
.buttons .btn-danger:hover {
    opacity: 0.9;
}

/* Purchased Cards Section (Horizontal Scroll) */
.purchased-cards-section {
    background-color: #111827;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

.purchased-cards-section h2 {
    font-size: 1.5rem;
    color: #fff;
    margin-top: 0;
    margin-bottom: 20px;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
}

.purchased-cards-row {
    display: flex;
    gap: 20px;
    overflow-x: auto; /* Enable horizontal scrolling */
    padding-bottom: 15px; /* Add some padding for the scrollbar */
}

.purchased-card {
    background-color: #1e293b;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: transform 0.2s ease-in-out;
    display: flex;
    flex-direction: column;
    min-width: 280px; /* Minimum width for each card */
}

.purchased-card:hover {
    transform: translateY(-5px);
}

.purchased-card .card-header {
    background-color: var(--card-color);
    color: #111827;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.purchased-card .card-header i {
    font-size: 1.3rem;
    margin-right: 10px;
}

.purchased-card .card-header h4 {
    font-size: 1.1rem;
    margin: 0;
}

.purchased-card .card-body {
    padding: 20px;
}

.purchased-card .card-body .description {
    color: #555;
    margin-bottom: 15px;
    line-height: 1.6;
}

.purchased-card .card-body .user-details {
    list-style: none;
    padding: 0;
    margin-bottom: 15px;
    color: #777;
    font-size: 0.9rem;
}

.purchased-card .card-body .user-details li {
    margin-bottom: 5px;
    display: flex;
    align-items: center;
}

.purchased-card .card-body .user-details li i {
    margin-right: 8px;
    font-size: 1rem;
}

.purchased-card .card-body .tech-icons {
    display: flex;
    gap: 15px;
    font-size: 1.5rem;
    color: #999;
}
.available-card .card-header {
    background-color: var(--card-color, #6c757d); /* Use a CSS variable with a default */
    color: #fff;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
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
    <div class="container mt-5">
    <h2>Generate Card License</h2>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="messages.php" method="POST" class="card-form">
    <div class="row mb-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="title"><i class="bx bx-heading"></i> Card Title:</label>
            <input type="text" class="form-control line-input" id="title" name="title" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="price"><i class="bx bx-coin"></i> Price (Coins):</label>
            <input type="number" class="form-control line-input" id="price" name="price" required>
        </div>
    </div>
</div>

<div class="form-group mb-3">
    <label for="description"><i class="bx bx-detail"></i> Description:</label>
    <textarea class="form-control line-input" id="description" name="description" rows="4" required></textarea>
</div>

<div class="form-group mb-3">
    <label for="expiration_date"><i class="bx bx-calendar-x"></i> Expiration Date:</label>
    <input type="date" class="form-control line-input" id="expiration_date" name="expiration_date">
    <small class="form-text text-muted">Optional: Set an expiration date for this card.</small>
</div>

<div class="form-group mb-4">
    <label for="color"><i class="bx bx-color-fill"></i> Card Color:</label>
    <div class="color-options">
        <div class="color-option">
            <input type="radio" id="bronze" name="color" value="#CD7F32" required>
            <label for="bronze" style="background-color: #CD7F32;"></label>
            <span>Bronze</span>
        </div>

        <div class="color-option">
            <input type="radio" id="silver" name="color" value="#C0C0C0" required>
            <label for="silver" style="background-color: #C0C0C0;"></label>
            <span>Silver</span>
        </div>

        <div class="color-option">
            <input type="radio" id="gold" name="color" value="#FFD700" required>
            <label for="gold" style="background-color: #FFD700;"></label>
            <span>Gold</span>
        </div>

        <div class="color-option">
            <input type="radio" id="platinum" name="color" value="#E5E4E2" required>
            <label for="platinum" style="background-color: #E5E4E2;"></label>
            <span>Platinum</span>
        </div>

        <div class="color-option">
            <input type="radio" id="emerald" name="color" value="#50C878" required>
            <label for="emerald" style="background-color: #50C878;"></label>
            <span>Emerald</span>
        </div>

        <div class="color-option">
            <input type="radio" id="diamond" name="color" value="#B9F2FF" required>
            <label for="diamond" style="background-color: #B9F2FF;"></label>
            <span>Diamond</span>
        </div>
    </div>
</div>

<button type="submit" class="btn btn-primary"><i class="bx bx-plus-circle"></i> Post Card</button>

    </form>
</div>
    <hr>

    <h2>Available Cards</h2>
    <?php foreach ($rarityOrder as $rarity => $colors): ?>
        <section class="mb-4">
            <h3><?= htmlspecialchars($rarity) ?> Licenses</h3>
            <div class="row">
                <?php if (!empty($cardsByRarity[$rarity])): ?>
                    <?php foreach ($cardsByRarity[$rarity] as $card): ?>
                        <div class="col-md-4">
                            <div class="card-container">
                                <div class="purchased-card">
                                    <div class="card-header" style="background-color: <?= htmlspecialchars($card['color'] ?? '#6c757d') ?>;">
                                        <i class='bx bx-award'></i>
                                        <h4><?= htmlspecialchars($card['title']) ?></h4>
                                        <?php if ($card['expiration_date']): ?>
                                            <?php
                                            $expiration = new DateTime($card['expiration_date']);
                                            $today = new DateTime();
                                            $interval = $today->diff($expiration);
                                            $remainingDays = (int)$interval->format('%R%a');

                                            if ($remainingDays > 0) {
                                                echo '<span class="badge badge-success ml-2">Expires in ' . $remainingDays . ' day' . ($remainingDays > 1 ? 's' : '') . '</span>';
                                            } elseif ($remainingDays === 0) {
                                                echo '<span class="badge badge-warning ml-2">Expires today!</span>';
                                            } else {
                                                echo '<span class="badge badge-danger ml-2">Expired ' . abs($remainingDays) . ' day' . (abs($remainingDays) > 1 ? 's' : '') . ' ago</span>';
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-body">
                                        <p class="description"><?= htmlspecialchars($card['description']) ?></p>
                                        <ul class="user-details">
                                            <li><i class='bx bx-user'></i> N/A</li>
                                            <li><i class='bx bx-calendar'></i> N/A</li>
                                            <li><i class='bx bx-label'></i> Web Dev</li>
                                            <li><i class='bx bx-envelope'></i> N/A</li>
                                        </ul>
                                        <div class="tech-icons">
                                            <i class="bx bxl-html5"></i>
                                            <i class="bx bxl-css3"></i>
                                            <i class="bx bxl-javascript"></i>
                                        </div>
                                        <?php if ($card['expiration_date']): ?>
                                            <?php
                                            $expiration = new DateTime($card['expiration_date']);
                                            $today = new DateTime();
                                            $interval = $today->diff($expiration);
                                            $remainingDays = (int)$interval->format('%R%a'); // %R for +/- sign, %a for total days

                                            if ($remainingDays > 0) {
                                                echo '<p class="text-success">Expires in ' . $remainingDays . ' day' . ($remainingDays > 1 ? 's' : '') . ' left</p>';
                                            } elseif ($remainingDays === 0) {
                                                echo '<p class="text-warning">Expires today!</p>';
                                            } else {
                                                echo '<p class="text-danger">Expired ' . abs($remainingDays) . ' day' . (abs($remainingDays) > 1 ? 's' : '') . ' ago</p>';
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                    <p class="price" style="color: white">Price: <?= htmlspecialchars($card['price']) ?> Coins</p>
                                    <div class="buttons">
                                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#editModal<?= $card['id'] ?>">Edit</button>
                                        <form action="delete_card.php" method="POST">
                                            <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-muted">No <?= htmlspecialchars($rarity) ?> licenses available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
    </div>
  </div>
  </div>
  </div>
  </div>
    </main>

    <?php foreach ($allCards as $card): ?>
    <div class="modal fade" id="editModal<?= $card['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?= $card['id'] ?>" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?= $card['id'] ?>">Edit Card</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="messages.php" method="POST" class="card-form">
                    <div class="modal-body">
                        <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title"><i class="bx bx-heading"></i> Card Title:</label>
                                    <input type="text" class="form-control line-input" id="title" name="title" value="<?= htmlspecialchars($card['title']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price"><i class="bx bx-coin"></i> Price (Coins):</label>
                                    <input type="number" class="form-control line-input" id="price" name="price" value="<?= htmlspecialchars($card['price']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description"><i class="bx bx-detail"></i> Description:</label>
                            <textarea class="form-control line-input" id="description" name="description" rows="4" required><?= htmlspecialchars($card['description']) ?></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="expiration_date"><i class="bx bx-calendar-x"></i> Expiration Date:</label>
                            <input type="date" class="form-control line-input" id="edit_expiration_date<?= $card['id'] ?>" name="expiration_date" value="<?= htmlspecialchars($card['expiration_date']) ?>">
                            <small class="form-text text-muted">Set or update the expiration date for this card.</small>
                        </div>

                        <div class="form-group mb-4">
                            <label for="color"><i class="bx bx-color-fill"></i> Card Color:</label>
                            <div class="color-options">
                                <div class="color-option">
                                    <input type="radio" id="edit_bronze<?= $card['id'] ?>" name="color" value="#CD7F32" <?= htmlspecialchars($card['color']) === '#CD7F32' ? 'checked' : '' ?> required>
                                    <label for="edit_bronze<?= $card['id'] ?>" style="background-color: #CD7F32;"></label>
                                    <span>Bronze</span>
                                </div>

                                <div class="color-option">
                                    <input type="radio" id="edit_silver<?= $card['id'] ?>" name="color" value="#C0C0C0" <?= htmlspecialchars($card['color']) === '#C0C0C0' ? 'checked' : '' ?> required>
                                    <label for="edit_silver<?= $card['id'] ?>" style="background-color: #C0C0C0;"></label>
                                    <span>Silver</span>
                                </div>

                                <div class="color-option">
                                    <input type="radio" id="edit_gold<?= $card['id'] ?>" name="color" value="#FFD700" <?= htmlspecialchars($card['color']) === '#FFD700' ? 'checked' : '' ?> required>
                                    <label for="edit_gold<?= $card['id'] ?>" style="background-color: #FFD700;"></label>
                                    <span>Gold</span>
                                </div>

                                <div class="color-option">
                                    <input type="radio" id="edit_platinum<?= $card['id'] ?>" name="color" value="#E5E4E2" <?= htmlspecialchars($card['color']) === '#E5E4E2' ? 'checked' : '' ?> required>
                                    <label for="edit_platinum<?= $card['id'] ?>" style="background-color: #E5E4E2;"></label>
                                    <span>Platinum</span>
                                </div>

                                <div class="color-option">
                                    <input type="radio" id="edit_emerald<?= $card['id'] ?>" name="color" value="#50C878" <?= htmlspecialchars($card['color']) === '#50C878' ? 'checked' : '' ?> required>
                                    <label for="edit_emerald<?= $card['id'] ?>" style="background-color: #50C878;"></label>
                                    <span>Emerald</span>
                                </div>

                                <div class="color-option">
                                    <input type="radio" id="edit_diamond<?= $card['id'] ?>" name="color" value="#B9F2FF" <?= htmlspecialchars($card['color']) === '#B9F2FF' ? 'checked' : '' ?> required>
                                    <label for="edit_diamond<?= $card['id'] ?>" style="background-color: #B9F2FF;"></label>
                                    <span>Diamond</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

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
 </script>
 </body>
 </html>

 <?php $conn = null; ?>
