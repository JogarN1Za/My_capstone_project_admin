<?php
 include('conn/connect.php');
 session_start();
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
   </div>

   <i class="bx bx-dots-vertical-rounded dropdown-toggle-icon"></i>

   <div class="right-dropdown">
    <a href="messages.php">Create Cards</a>
    <a href="users.php">Add User</a>
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
  <div>
   <h2>Content</h2>
   <p>This is your main content area. Resize the screen to see the responsive sidebar and top navigation in action.</p>
   <hr>
   <p>The sidebar toggles on small screens, and the top nav items collapse into a dropdown menu.</p>
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
 </script>
 </body>
 </html>

 <?php $conn = null; ?>
