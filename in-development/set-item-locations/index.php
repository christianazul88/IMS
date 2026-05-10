<?php
include "../config/database.php";
include "../config/on_session.php";

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
        
    </main><!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->

    <!-- ===============================================--><!--    JavaScripts--><!-- ===============================================-->
    <?php include "../page_properties/footer_main.php";?>
    <?php
    if (isset($_GET['missing_field']) && $_GET['missing_field'] === "true") {
        echo "
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Fields',
                text: 'If you input a quantity or select a location, all fields must be filled!',
                confirmButtonText: 'OK'
            });
        </script>
        ";
    }
    ?>
  </body>


<!-- Mirrored from prium.github.io/falcon/v3.22.0/pages/starter.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 14 Oct 2024 08:15:49 GMT -->
</html>