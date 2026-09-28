<?php include ('./conn/conn.php'); ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sidebar Menu | Side Navigation Bar</title>
    <!-- CSS -->
    <link rel="stylesheet" href="css/dash.css" />
    <!-- Boxicons CSS -->
    <link
      href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css"
      rel="stylesheet"/>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
      <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
 
  </head>
  <body>
    <nav>
      <div class="logo">
        <i class="bx bx-menu menu-icon"></i>
        <span class="logo-name">CodingLab</span>
      </div>
      <div class="sidebar">
        <div class="sidebar-content">
          <ul class="lists">
            <li class="list">
              <a href="dashboard.php" class="nav-link">
                <i class="bx bx-home-alt icon"></i>
                <span class="link">Dashboard</span>
              </a>
            </li>
            <li class="list">
              <a href="analytics.php" class="nav-link">
                <i class="bx bx-bar-chart-alt-2 icon"></i>
                <span class="link">Analytics</span>
              </a>
            </li>
            <li class="list">
              <a href="notifications.php" class="nav-link">
                <i class="bx bx-bell icon"></i>
                <span class="link">Notifications</span>
              </a>
            </li>
            <li class="list">
              <a href="messages.php" class="nav-link">
                <i class="bx bx-message-rounded icon"></i>
                <span class="link">Messages</span>
              </a>
            </li>
            <li class="list">
              <a href="category.php" class="nav-link">
                <i class="bx bx-pie-chart-alt-2 icon"></i>
                <span class="link">Category</span>
              </a>
            </li>
            <li class="list">
                        <a href="part.php" class="nav-link">
                             <i class='bx bx-list-ol icon'></i>
                            <span class="link">Part</span>
                        </a>
                    </li>
                    <li class="list">
                        <a href="dificulty.php" class="nav-link">
                             <i class='bx bx-question-mark icon'></i>
                            <span class="link">Difficulties</span>
                        </a>
                    </li>            
            <li class="list">
              <a href="tutorials.php" class="nav-link">
                <i class="bx bx-code-alt icon" ></i>
                <span class="link">Tutorials</span>
              </a>
            </li>
            <li class="list">
              <a href="users.php" class="nav-link">
                <i class="bx bxs-user-plus icon"></i>
                <span class="link">Users</span>
              </a>
            </li>
          </ul>
          <div class="bottom-content">
            <li class="list">
              <a href="settings.php" class="nav-link">
                <i class="bx bx-cog icon"></i>
                <span class="link">Settings</span>
              </a>
            </li>
            <li class="list">
              <a href="index.php" class="nav-link">
                <i class="bx bx-log-out icon"></i>
                <span class="link">Logout</span>
              </a>
            </li>
          </div>
        </div>
      </div>
    </nav>

    <main class="main">
        <div class="container-wrapper">
          <div class="container">
            <h2>Add Code Editor Tutorial Easy</h2>
            <a href="code_edit.php" class="nav-link">
            <i class='bx bxs-edit' ></i>
                <span class="link">Create Tutorial</span>
              </a>
          </div>
          <div class="container">
            <h2>Add Assessment Easy</h2>
            <a href="ass_tut.php" class="nav-link">
            <i class='bx bxs-edit' ></i>
                <span class="link">Create Assessment</span>
              </a>
          </div>
        </div>
        <br>
        <div class="container-wrapper">
          <div class="container">
            <h2>Add Code Editor Tutorial Medium</h2>
            <a href="miduem_edit.php" class="nav-link">
            <i class='bx bxs-edit' ></i>
                <span class="link">Create Tutorial</span>
              </a>
          </div>
          <div class="container">
            <h2>Add Assessment Medium</h2>
            <a href="medium_ass.php" class="nav-link">
            <i class='bx bxs-edit' ></i>
                <span class="link">Create Assessment</span>
              </a>
          </div>
        </div>
        <br>
        <div class="container-wrapper">
          <div class="container">
            <h2>Add Code Editor Tutorial Hard</h2>
            <a href="hard_edit.php" class="nav-link">
            <i class='bx bxs-edit' ></i>
                <span class="link">Create Tutorial</span>
              </a>
          </div>
          <div class="container">
            <h2>Add Assessment Hard</h2>
            <a href="hard_ass.php" class="nav-link">
            <i class='bx bxs-edit' ></i>
                <span class="link">Create Assessment</span>
              </a>
          </div>
        </div>
      </main>


      
    <script src="js/dash.js"></script>
  </body>
</html>
