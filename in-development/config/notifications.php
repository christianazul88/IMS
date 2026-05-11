<?php
include "database.php";
include "on_session.php";

if($user_position_name === "Administrator"){
    $query = "SELECT n.*,
        u.user_fname,
        u.user_lname
    FROM notification n 
    LEFT JOIN users u ON u.hashed_id = n.to_userid
    WHERE n.to_userid = '' OR n.to_userid = '$user_id'
    ORDER BY n.date DESC
    LIMIT 100
    ";
} elseif(strpos($access, "approve_inbound") !== false){
    $query = "SELECT n.*,
                    u.user_fname,
                    u.user_lname
                FROM notification n 
                LEFT JOIN users u ON u.hashed_id = n.to_userid
                WHERE n.to_userid = ''
                ORDER BY n.date DESC
                LIMIT 100
                ";
} else {
    $query = "SELECT n.*,
                    u.user_fname,
                    u.user_lname
                FROM notification n 
                LEFT JOIN users u ON u.hashed_id = n.to_userid
                WHERE n.to_userid = '$user_id'
                ORDER BY n.date DESC
                LIMIT 100
                ";
}

$result = $conn->query($query);

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $notification_id = hash('sha256', $row['id']);
        $notification_title = $row['title'];
        $notification_message = $row['message'];
        $notification_status = $row['status']; // Get the status
        $notification_date = $row['date']; // Date in datetime format

        // Calculate time difference
        $current_time = time();
        $notification_time = strtotime($notification_date);
        $time_diff = $current_time - $notification_time;

        // Time formatting logic
        if ($time_diff < 60) {
            $time_display = "Just Now";
        } elseif ($time_diff < 3600) { // Less than 1 hour
            $minutes = floor($time_diff / 60);
            $time_display = $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
        } elseif ($time_diff < 86400) { // Less than 1 day
            $hours = floor($time_diff / 3600);
            $time_display = $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
        } else { // More than 1 day
            $time_display = date("M j, Y g:i A", $notification_time); // Format as "Jan 1, 2001 10:34 PM"
        }

        // Check if the status is 1, and if so, add the "notification-unread" class
        $notification_class = ($notification_status == 0) ? "notification-unread" : "";

        if(strpos($notification_title, "Inbound")!==false){
            $link = "../Inbound-logs/?notnot=$notification_id";
        } elseif(strpos($notification_title, "Outbound")!==false){
            $link = "../Outbound-logs/?notnot=$notification_id";
        }

        ?>
        <a class="border-bottom-0 <?php echo $notification_class; ?> notification rounded-0 border-x-0 border-300" href="<?php echo $link;?>">
            <div class="notification-avatar">
                <div class="avatar avatar-xl me-3">
                <img class="rounded-circle" src="../../assets/img/<?php echo $user_pfp;?>" alt="" />
                </div>
            </div>
            <div class="notification-body">
                <p class="mb-1"><?php echo $notification_message;?></p>
                <span class="notification-time">
                    <span class="me-2" role="img" aria-label="Emoji">📢</span><?php echo $time_display;?>
                </span>
            </div>
        </a>
        <?php
    }
}
?>
