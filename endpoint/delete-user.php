<?php
include ('../conn/connect.php'); // Adjust path if needed

if (isset($_GET['user']) && is_numeric($_GET['user'])) {
    $userIdToDelete = $_GET['user'];

    try {
        $query = "DELETE FROM `tbl_user` WHERE `tbl_user_id` = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userIdToDelete, PDO::PARAM_INT);
        $query_execute = $stmt->execute();

        if ($query_execute) {
            echo "
            <script>
                alert('User Deleted Successfully');
                window.location.href = '../settings.php'; // Adjust path if needed
            </script>
            ";
        } else {
            echo "
            <script>
                alert('Error deleting user.');
                window.location.href = 'http://localhost/Webdev/admin/settings.php';
            </script>
            ";
        }

    } catch (PDOException $e) {
        echo "
        <script>
            alert('Database Error: " . $e->getMessage() . "');
           window.location.href = 'http://localhost/Webdev/admin/settings.php';
        </script>
        ";
    }
} else {
    echo "
    <script>
        alert('Invalid user ID for deletion.');
         window.location.href = 'http://localhost/Webdev/admin/settings.php';
    </script>
    ";
}

?>
