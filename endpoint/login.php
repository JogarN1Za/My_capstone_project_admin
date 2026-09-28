<?php
include ('../conn/connect.php');
session_start(); // Start the session at the beginning

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch user data, including user_id
    $stmt = $conn->prepare("SELECT `tbl_user_id`, `password` FROM `tbl_user` WHERE `username` = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $user_id = $row['tbl_user_id'];
        $stored_password = $row['password'];

        if ($password === $stored_password) {
            // **Set a session variable to mark the user as logged in**
            $_SESSION['admin_id'] = $user_id;

            // **Use PHP header for redirection**
            header("Location: /Webdev/admin/dashboard.php");
            exit(); // Ensure script stops execution after redirection
        } else {
            echo "<script>alert('Login Failed, Incorrect Password!'); window.location.href = '/Webdev/admin/index.php';</script>";
        }
    } else {
        echo "<script>alert('Login Failed, User Not Found!'); window.location.href = '/Webdev/admin/index.php';</script>";
    }
}
?>
