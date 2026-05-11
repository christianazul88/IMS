<?php
session_start(); // Ensure session is started

if (isset($_GET['type']) && isset($_GET['d'])) {
    // Sanitize inputs
    $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
    $id = filter_input(INPUT_GET, 'd', FILTER_SANITIZE_NUMBER_INT);

    // Validate sanitized values
    if (!empty($type) && is_numeric($id)) {
        // Optional: limit allowed values for 'type' if applicable
        $allowed_types = ['refund', 'replace']; // example types
        if (in_array($type, $allowed_types)) {
            $_SESSION['rts_type'] = $type;
            $_SESSION['rts_id'] = intval($id);

            header("Location: ../rts-form/");
            exit;
        } else {
            // Invalid type value
            http_response_code(400);
            echo "Invalid type.";
            exit;
        }
    } else {
        // Invalid or missing parameters
        http_response_code(400);
        echo "Invalid parameters.";
        exit;
    }
} else {
    // Required parameters not set
    http_response_code(400);
    echo "Missing parameters.";
    exit;
}
