<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sidebar</title>
    <style>
        .sidebar {
            height: 100vh; /* Full height of the viewport */
            width: 200px;
            background-color: #f0f0f0;
            padding-top: 20px;
            position: fixed; /* Stay in place on scroll */
            left: 0;
            top: 0;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li a {
            display: block;
            padding: 10px 20px;
            text-decoration: none;
            color: #333;
        }

        .sidebar li a:hover {
            background-color: #ddd;
        }

        .content {
            margin-left: 220px; /* Adjust based on sidebar width */
            padding: 20px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <ul>
            <li><a href="#">Dashboard</a></li>
            <li><a href="#">Users</a></li>
            <li><a href="#">Content</a></li>
            <li><a href="#">Settings</a></li>
            <li><a href="#">Analytics</a></li>
        </ul>
    </div>

    <div class="content">
        <h1>Admin Content Area</h1>
        <p>This is where the main content of your admin panel will go.</p>
    </div>

</body>
</html>
