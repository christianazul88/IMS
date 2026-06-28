<?php
require_once '../config/database.php';

// $audit_id = $audit;
if(isset($_POST['audit_id'])){
    $audit_id = $_POST['audit_id'];
}


if (isset($_POST['delete_personnel'])) {

    $hashed_id = $_POST['hashed_id'];

    $stmt = $conn->prepare("
        DELETE FROM audit_users
        WHERE hashed_id = ?
        AND audit_id = ?
    ");

    $stmt->bind_param("si", $hashed_id, $audit_id);
    $stmt->execute();
    $stmt->close();

    // echo "<script>
    //     alert('Personnel removed successfully.');
    //     window.location.href = window.location.pathname;
    // </script>";
    // exit;
    header("Location:../assign-positions/?audit=$audit_id");
}

if (isset($_POST['update_position'])) {

    $hashed_id = $_POST['hashed_id'];
    $audit_position = (int) $_POST['audit_position'];

    $stmt = $conn->prepare("
        UPDATE audit_users
        SET audit_position = ?
        WHERE hashed_id = ?
        AND audit_id = ?
    ");

    $stmt->bind_param('isi', $audit_position, $hashed_id, $audit_id);
    $stmt->execute();
    $stmt->close();

    // echo "<script>
    //     alert('Position updated successfully.');
    //     window.location.href = window.location.pathname;
    // </script>";
    // exit;
    header("Location:../assign-positions/?audit=$audit_id");
}