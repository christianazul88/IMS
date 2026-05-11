<?php
include "database.php";
include "on_session.php";

if (isset($_GET['type']) && isset($_GET['d'])) {
    $type = $_GET['type']; //refund || replace 
    $rts_id = intval($_GET['d']); // Ensure rts_id is integer
    
    $set_status = ($type === "replace") ? 2 : 1;
    $action = ($type === "replace") ? "item is replaced." : "item is refunded.";


    $update_logs = $conn->prepare("UPDATE rts_logs SET `status` = 1, returned_date = ? WHERE id = ?");
    $update_logs->bind_param("si", $currentDateTime, $rts_id);
    $update_logs->execute();
    $update_logs->close();

    $select_contents_query = "SELECT unique_barcode FROM rts_content WHERE rts_id = '$rts_id'";
    $select_contents_res = $conn->query($select_contents_query);
    while($row=$select_contents_res->fetch_assoc()){
        $rts_unique_barcode = $row['unique_barcode'];
        // Use prepared statements to prevent SQL injection
        $update = $conn->prepare("UPDATE rts_content SET `status` = ?, returned_date = ? WHERE rts_id = ?");
        $update->bind_param("isi", $set_status, $currentDateTime, $rts_id);

        if ($update->execute()) {
            // Insert into stock_timeline
            $stock_timeline = $conn->prepare("INSERT INTO stock_timeline (unique_barcode, title, `action`, `date`, user_id) VALUES (?, 'RTS', ?, ?, ?)");
            $stock_timeline->bind_param("ssss", $rts_unique_barcode, $action, $currentDateTime, $user_id);
            $stock_timeline->execute();
            $stock_timeline->close();
        }
    }
        

    // Insert into logs
    $log_action = "$rts_id " . ($type === "replace" ? "is replaced." : "is refunded.");
    $logs = $conn->prepare("INSERT INTO logs (title, `action`, `date`, user_id) VALUES ('Return to Supplier', ?, ?, ?)");
    $logs->bind_param("sss", $log_action, $currentDateTime, $user_id);
    $logs->execute();
    $logs->close();

    
}

$conn->close();
?>
