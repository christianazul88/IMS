<?php
include "../config/database.php";
include "../config/on_session.php";

//unfinished
// // Use prepared statements to prevent SQL injection
// $stmt = $conn->prepare("SELECT first_login FROM users WHERE hashed_id = ? LIMIT 1");
// $stmt->bind_param("s", $user_id);
// $stmt->execute();
// $result = $stmt->get_result();

// if ($row = $result->fetch_assoc()) {
//     if ($row['first_login'] !== "false") {
//         header("Location: ../Authentication/");
//         exit(); // Ensure script stops execution after redirection
//     }
// } else {
//     echo "Invalid user ID"; // Handle cases where no result is found
// }

// // Close statement
// $stmt->close();
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">

  
<!-- Mirrored from prium.github.io/falcon/v3.22.0/pages/starter.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 14 Oct 2024 08:15:49 GMT -->
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=utf-8" /><!-- /Added by HTTrack -->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ===============================================--><!--    Document Title--><!-- ===============================================-->
    <title>LPO</title>
    <?php include "../page_properties/header.php";?>
  </head>

  <body>
    <!-- ===============================================--><!--    Main Content--><!-- ===============================================-->
    <main class="main" id="top">
      <div class="container" data-layout="container">
        <?php include "../page_properties/nav.php";?>


        <div class="content">
          <?php include "../page_properties/top-nav.php";?>

          <?php include "content.php";?>
          
          <?php include "../page_properties/footer.php";?>
        </div>

      </div>
    </main><!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->

    <!-- ===============================================--><!--    JavaScripts--><!-- ===============================================-->
    <?php include "../page_properties/footer_main.php";?>
  </body>


<!-- Mirrored from prium.github.io/falcon/v3.22.0/pages/starter.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 14 Oct 2024 08:15:49 GMT -->
</html>