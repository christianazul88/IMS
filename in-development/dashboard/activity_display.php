<?php
include "../config/database.php";
include "../config/on_session.php";

$staffs = [];
$sql = "SELECT 
          u.user_fname, 
          u.user_lname, 
          u.pfp AS user_pfp, 
          up.position_name, 
          u.hashed_id AS user_id 
        FROM users u 
        LEFT JOIN user_position up ON up.hashed_id = u.user_position";
        
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $user_fname = trim($row['user_fname']);
    $user_lname = trim($row['user_lname']);

    // Skip if both names are empty
    if (empty($user_fname) && empty($user_lname)) {
      continue;
    }

    $staff_fullname = $user_fname . " " . $user_lname;
    $staff_position = $row['position_name'] ?? 'Unknown';
    $staff_user_id = $row['user_id'];

    // Skip if the staff user is the same as the currently logged-in user
    if ($staff_user_id === $user_id) {
      continue;
    }

    $staff_profile_img = !empty($row['user_pfp']) 
      ? "../../assets/img/" . basename($row['user_pfp']) 
      : "../../assets/img/def_img.png";

    // Check last activity time
    $check_activity = "SELECT `date` FROM activity WHERE user_id = '$staff_user_id' ORDER BY id DESC LIMIT 1";
    $check_activity_res = $conn->query($check_activity);

    $is_online = false;
    if ($check_activity_res && $check_activity_res->num_rows > 0) {
      $activity_row = $check_activity_res->fetch_assoc();
      $last_activity = strtotime($activity_row['date']);

      // If last activity is within 3 minutes (180 seconds), mark online
      if ((time() - $last_activity) <= 180) {
        $is_online = true;
      }
    }

    $status_class = $is_online ? 'status-online' : 'status-offline';

    $staffs[] = '
      <div class="flex-fill align-items-center position-relative mb-3 mx-2">
        <div class="avatar avatar-2xl ' . $status_class . ' mb-3">
          <img class="rounded-circle" src="' . htmlspecialchars($staff_profile_img) . '" alt="" />
        </div>
        <div class="flex-1 ms-3">
          <h6 class="mb-0 fw-semi-bold">' . htmlspecialchars($staff_fullname) . '</h6>
          <p class="text-500 fs-11 mb-0">' . htmlspecialchars($staff_position) . '</p>
        </div>
      </div>';
  }
}
?>

<div class="col-12">
  <div class="carousel slide theme-slider text-center" id="controlStyledExample" data-bs-ride="carousel">
    <div class="carousel-inner rounded">
      <?php 
      $chunks = array_chunk($staffs, 6);
      $first = true;
      foreach ($chunks as $group) {
        echo '<div class="carousel-item ' . ($first ? 'active' : '') . '">';
        $first = false;
        echo '<div class="d-flex justify-content-center flex-wrap">';
        foreach ($group as $staff_html) {
          echo $staff_html;
        }
        echo '</div></div>';
      }
      ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#controlStyledExample" data-bs-slide="prev">
      <span class="fas fa-chevron-left"></span>
      <span class="sr-only">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#controlStyledExample" data-bs-slide="next">
      <span class="fas fa-chevron-right"></span>
      <span class="sr-only">Next</span>
    </button>
  </div>
</div>
