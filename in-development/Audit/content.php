<div class="row">
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Recent Activity</h6>
        </div>
        <div class="card-body scrollbar recent-activity-body-height ps-2" style="height:80vh;">

        <?php 
        function timeAgo($datetime) {
            $timestamp = strtotime($datetime);
            $difference = time() - $timestamp;

            if ($difference < 60) {
                return $difference . "s ago";
            } elseif ($difference < 3600) {
                return floor($difference / 60) . "m ago";
            } elseif ($difference < 86400) {
                return floor($difference / 3600) . "h ago";
            } elseif ($difference < 172800) {
                return "1d ago";
            } elseif ($difference < 31536000) {
                return date("M j gA", $timestamp);
            } else {
                return date("Y M j gA", $timestamp);
            }
        }

        // SQL query to fetch logs and user information
        $sql = "SELECT
                    l.*,
                    u.user_fname,
                    u.user_lname,
                    u.pfp
                FROM logs l
                LEFT JOIN users u ON u.hashed_id = l.user_id
                ORDER BY l.date DESC LIMIT 100";

        $res = $conn->query($sql);

        // Check if the query returned any results
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                // Extract user and log details
                $doer = $row['user_fname'] . " " . $row['user_lname'];
                $doer_pfp = empty($row['pfp']) ? "../../assets/img/def_pfp.png" : $row['pfp'];
                $log_title = $row['title'];
                $log_action = $row['action'];
                $log_date = $row['date']; // Datetime format
                $time_ago = timeAgo($log_date);
        ?>
        
        <!-- Activity: Log Display -->
        <div class="row g-3 timeline timeline-primary timeline-past pb-x1">
            <div class="col-auto ps-4 ms-2">
                <div class="ps-2">
                    <img src="<?php echo $doer_pfp; ?>" style="width: 30px;" alt="">
                </div>
            </div>
            <div class="col">
                <div class="row gx-0 border-bottom pb-x1">
                    <div class="col">
                        <h6 class="text-800 mb-1"><?php echo $doer; ?></h6>
                        <p class="fs-10 text-600 mb-0"><?php echo $log_action; ?></p>
                    </div>
                    <div class="col-auto">
                        <p class="fs-11 text-500 mb-0"><?php echo $time_ago; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php
            }
        }
        ?>

        </div>
    </div>
</div>
