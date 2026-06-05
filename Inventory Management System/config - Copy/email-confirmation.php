<?php
include "../config/database.php"; 
include "../config/on_session.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_otp = $_POST['otp'] ?? '';

    $sql = "SELECT otp FROM users WHERE hashed_id = '$user_id' LIMIT 1";
    $res = $conn->query($sql);
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $_SESSION['otp'] = $row['otp'];
    }

    // Ensure session variable exists
    if (!isset($_SESSION['otp'])) {
        echo json_encode(["status" => "error", "message" => "Session expired. Please request a new OTP."]);
        exit;
    }

    // Initialize attempt counter if not set
    if (!isset($_SESSION['otp_attempts'])) {
        $_SESSION['otp_attempts'] = 0;
    }

    $correct_otp = $_SESSION['otp']; // The OTP stored in session

    if ($input_otp == $correct_otp) {
        // OTP is correct, reset attempts and clear OTP
        $_SESSION['otp_attempts'] = 0;
        unset($_SESSION['otp']);

        // Update first_login to false
        $update_sql = "UPDATE users SET first_login = 'false' WHERE hashed_id = '$user_id'";
        $conn->query($update_sql);

        echo json_encode(["status" => "success", "redirect" => "../Account-setup/"]);
    } else {
        $_SESSION['otp_attempts']++;

        if ($_SESSION['otp_attempts'] >= 6) {
            // Too many failed attempts, redirect to logout
            echo json_encode(["status" => "failed", "redirect" => "../config/logout.php"]);
        } else {
            echo json_encode([
                "status" => "failed",
                "message" => "Invalid OTP. Attempt {$_SESSION['otp_attempts']} of 6."
            ]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>