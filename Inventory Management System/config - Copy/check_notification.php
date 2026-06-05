<?php
// Include the database connection file
include('database.php');
include('on_session.php');

if(strpos($access, "approve_inbound")!==false || $user_position_name === "Administrator"){
    $user_id = "";
    $sql = "SELECT COUNT(*) AS unread_count FROM notification WHERE to_userid = ? AND status = 0";
} elseif(strpos($access, "inbound_logs")!==false || $user_position_name === "Administrator" || strpos($access, "logistics")!==false){
    $sql = "SELECT COUNT(*) AS unread_count FROM notification WHERE to_userid = ? AND status = 0";
}
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode(['unread_count' => (int)$result['unread_count']]);
?>
