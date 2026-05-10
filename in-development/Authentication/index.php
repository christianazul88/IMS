<?php
include "../config/database.php";
include "../config/on_session.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require "../config/vendor/autoload.php";

$query = "SELECT otp FROM users WHERE hashed_id = '$user_id' LIMIT 1";
$res = $conn->query($query);

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $otp = $row['otp'];

    if ($otp == "0" || $otp == 0) {
        $new_otp = rand(100000, 999999); // Generate 6-digit OTP that doesn’t start with 0

        // Update OTP in the database
        $update_query = "UPDATE users SET otp = '$new_otp' WHERE hashed_id = '$user_id'";
        $conn->query($update_query);

        // echo "New OTP generated: " . $new_otp; // Debugging purpose
    }
    if(isset($new_otp)){
      $otp = $new_otp;
    }
}
$password = $otp;

$mail = new PHPMailer;
$mail = new PHPMailer(true);

try {
    $mail -> isSMTP();
    $mail -> Host = "smtp.gmail.com";
    $mail -> SMTPAuth = true;
    $mail -> Username = "pdm.azulchristian@gmail.com";
    $mail -> Password = "xucm vpjf iqob vsyb";
    $mail -> SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail -> Port = 587;

    $mail -> setFrom("noreply@lpo.com", "noreplylpo");
    $mail -> addAddress("$user_email");

    $mail -> Subject = "New Contact Form Submission";
    $mail -> Body = "Your One time password is: $otp";
    if($mail -> send()){

    }else{
        // echo "Message could not be sent, Error: " . $mail->ErrorInfo; 
    }

} catch (Exception $e) {
    echo "Message could not be sent, Error: " . $mail->ErrorInfo; 
}


?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">

  
<!-- Mirrored from prium.github.io/falcon/v3.22.0/pages/authentication/simple/confirm-mail.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 14 Oct 2024 08:15:46 GMT -->
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=utf-8" /><!-- /Added by HTTrack -->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ===============================================--><!--    Document Title--><!-- ===============================================-->
    <title>LPO | Authentication</title>

    <?php 
    include "../page_properties/header.php";
    ?>
  </head>

  <body>
    <!-- ===============================================--><!--    Main Content--><!-- ===============================================-->
    <main class="main" id="top">
      <div class="container" data-layout="container">
        <script>
          var isFluid = JSON.parse(localStorage.getItem('isFluid'));
          if (isFluid) {
            var container = document.querySelector('[data-layout]');
            container.classList.remove('container');
            container.classList.add('container-fluid');
          }
        </script>
        <form action="../config/email-confirmation.php" method="POST" id="my_authentication_form">
          <div class="row flex-center min-vh-100 py-6">
            <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4"><a class="d-flex flex-center mb-4" href="#"><img class="me-2" src="../../assets/img/logo/LPO Logo.png" alt="" width="120" /></a>
              <div class="card">
                <div class="card-body p-4 p-sm-5">
                  <div class="text-center"><img class="d-block mx-auto mb-4" src="../assets/img/icons/spot-illustrations/16.png" alt="Email" width="100" />
                    <h4 class="mb-2">Please check your email!</h4>
                    <p>An email has been sent to <strong><?php echo $user_email;?></strong>. Please enter one-time password</p>
                    <input type="text" class="form-control" name="otp">
                    <button class="btn btn-primary btn-sm mt-3" id="submitBtn" type="submit">Submit</button>
                    <button class="btn btn-primary btn-sm mt-3" id="loadingBtn" disabled hidden><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Loading...</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </main><!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->


    <!-- ===============================================--><!--    JavaScripts--><!-- ===============================================-->
    <script src="../vendors/popper/popper.min.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/fontawesome/all.min.js"></script>
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      document.getElementById("my_authentication_form").addEventListener("submit", function(event) {
        event.preventDefault(); // Prevent form submission from reloading the page

        let submitBtn = document.getElementById("submitBtn");
        let loadingBtn = document.getElementById("loadingBtn");

        submitBtn.hidden = true; // Hide submit button
        loadingBtn.hidden = false; // Show loading button

        let formData = new FormData(this);

        fetch(this.action, {
          method: this.method,
          body: formData
        })
        .then(response => response.json()) // Assuming server returns JSON response
        .then(data => {
          submitBtn.hidden = false;
          loadingBtn.hidden = true;

          console.log(data);

          if (data.status === "success") {
            Swal.fire({
              icon: "success",
              title: "Success",
              text: data.message,
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
              window.location.href = "../Account-setup/";
            });
          } else {
            let attempts = parseInt(localStorage.getItem("otpAttempts") || 0) + 1;
            localStorage.setItem("otpAttempts", attempts);

            Swal.fire({
              icon: "error",
              title: "Failed",
              text: data.message
            });

            if (attempts >= 6) {
              window.location.href = "../config/logout.php";
            }
          }
        })
        .catch(error => {
          submitBtn.hidden = false;
          loadingBtn.hidden = true;
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Something went wrong. Please try again."
          });
        });
      });
    </script>

  </body>


<!-- Mirrored from prium.github.io/falcon/v3.22.0/pages/authentication/simple/confirm-mail.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 14 Oct 2024 08:15:46 GMT -->
</html>