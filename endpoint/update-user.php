<?php
include ('../conn/connect.php');

$updateUserID = $_POST['tbl_user_id'];
$updateFirstName = $_POST['first_name'];
$updateLastName = $_POST['last_name'];
$updateContactNumber = $_POST['contact_number'];
$updateEmail = $_POST['email'];
$updateUsername = $_POST['username'];
$newPassword = $_POST['password'];  // Get the new password field

try {
    $conn->beginTransaction();

    $updateStmt = $conn->prepare("UPDATE `tbl_user` SET `first_name` = :first_name, `last_name` = :last_name, `contact_number` = :contact_number, `email` = :email, `username` = :username WHERE `tbl_user_id` = :userID");
    $updateStmt->bindParam(':first_name', $updateFirstName, PDO::PARAM_STR);
    $updateStmt->bindParam(':last_name', $updateLastName, PDO::PARAM_STR);
    $updateStmt->bindParam(':contact_number', $updateContactNumber, PDO::PARAM_STR); // Changed to PDO::PARAM_STR
    $updateStmt->bindParam(':email', $updateEmail, PDO::PARAM_STR);
    $updateStmt->bindParam(':username', $updateUsername, PDO::PARAM_STR);
    $updateStmt->bindParam(':userID', $updateUserID, PDO::PARAM_INT);
    $updateStmt->execute();

    // Update password only if a new password was provided
    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT); // Hash the new password
        $passwordStmt = $conn->prepare("UPDATE `tbl_user` SET `password` = :password WHERE `tbl_user_id` = :userID");
        $passwordStmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $passwordStmt->bindParam(':userID', $updateUserID, PDO::PARAM_INT);
        $passwordStmt->execute();
    }

    echo "
    <script>
        alert('Updated Successfully');
        window.location.href = 'http://localhost/Webdev/admin/settings.php';
    </script>
    ";

    $conn->commit();

} catch (PDOException $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
?>