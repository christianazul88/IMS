<?php
include "../config/database.php";
include "../config/on_session.php";

if($user_email === "lpo_admin@lpo.com"){
  header("Location: ../500/");
}
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">

  
<!-- Mirrored from prium.github.io/falcon/v3.22.0/pages/authentication/wizard.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 14 Oct 2024 08:15:58 GMT -->
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=utf-8" /><!-- /Added by HTTrack -->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ===============================================--><!--    Document Title--><!-- ===============================================-->
    <title>LPO | Account Setup</title>

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
        <div class="row justify-content-center pt-6">
          <div class="col-sm-10 col-lg-7 col-xxl-5"><a class="d-flex flex-center mb-4" href="#"><img class="me-2" src="../../assets/img/logo/LPO Logo.png" alt="" width="75" /><span class="font-sans-serif text-secondary fw-bolder fs-5 d-inline-block">IMS</span></a>
            <div class="card theme-wizard mb-5" id="wizard">
              <div class="card-header bg-body-tertiary pt-3 pb-2">
                <ul class="nav justify-content-between nav-wizard">
                  <li class="nav-item"><a class="nav-link active fw-semi-bold" href="#bootstrap-wizard-tab1" data-bs-toggle="tab" data-wizard-step="1"><span class="nav-item-circle-parent"><span class="nav-item-circle"><span class="fas fa-lock"></span></span></span><span class="d-none d-md-block mt-1 fs-10">Account</span></a></li>
                  <li class="nav-item"><a class="nav-link fw-semi-bold" href="#bootstrap-wizard-tab4" data-bs-toggle="tab" data-wizard-step="3"><span class="nav-item-circle-parent"><span class="nav-item-circle"><span class="fas fa-thumbs-up"></span></span></span><span class="d-none d-md-block mt-1 fs-10">Done</span></a></li>
                </ul>
              </div>
              <div class="card-body py-4" id="wizard-controller">
                <div class="tab-content">
                  <div class="tab-pane active px-sm-3 px-md-5" role="tabpanel" aria-labelledby="bootstrap-wizard-tab1" id="bootstrap-wizard-tab1">
                    <form class="needs-validation" action="../config/initial-account-update.php?wizard=password" method="POST" id="wizard1" novalidate="novalidate" data-wizard-form="1">
                      <div class="row g-2">
                        <div class="col-6">
                          <div class="mb-3"><label class="form-label" for="bootstrap-wizard-wizard-password">Password*</label><input class="form-control" type="password" name="password" placeholder="Password" required="required" id="bootstrap-wizard-wizard-password" data-wizard-password="true" />
                            <div class="text-danger small" id="password-requirements">
                              Password must be 8-16 characters long, contain at least one uppercase letter, one number, and one special character.
                            </div>
                            <div class="invalid-feedback">Please enter password</div>
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="mb-3"><label class="form-label" for="bootstrap-wizard-wizard-confirm-password">Confirm Password*</label><input class="form-control" type="password" name="confirmPassword" placeholder="Confirm Password" required="required" id="bootstrap-wizard-wizard-confirm-password" data-wizard-confirm-password="true" />
                            <div class="invalid-feedback">Passwords need to match</div>
                          </div>
                        </div>
                      </div>
                      <!-- <div class="form-check"><input class="form-check-input" type="checkbox" name="terms" required="required" checked="checked" id="bootstrap-wizard-wizard-checkbox" /><label class="form-check-label" for="bootstrap-wizard-wizard-checkbox">I accept the <a href="#!">terms </a>and <a href="#!">privacy policy</a></label></div> -->
                    </form>
                  </div>
                  <div class="tab-pane text-center px-sm-3 px-md-5" role="tabpanel" aria-labelledby="bootstrap-wizard-tab4" id="bootstrap-wizard-tab4">
                    <div class="wizard-lottie-wrapper">
                      <div class="lottie wizard-lottie mx-auto my-3" data-options='{"path":"../assets/img/animated-icons/celebration.json"}'></div>
                    </div>
                    <h4 class="mb-1">Your account is all set!</h4>
                    <p>Now you can access to your account</p><a class="btn btn-primary px-5 my-3" href="../dashboard/">Proceed to dashboard</a>
                  </div>
                </div>
              </div>
              <div class="card-footer bg-body-tertiary">
                <div class="px-sm-3 px-md-5">
                  <ul class="pager wizard list-inline mb-0">
                    <li class="previous"><button class="btn btn-link ps-0" type="button"><span class="fas fa-chevron-left me-2" data-fa-transform="shrink-3"></span>Prev</button></li>
                    <li class="next"><button class="btn btn-primary px-5 px-sm-6" type="submit">Next<span class="fas fa-chevron-right ms-2" data-fa-transform="shrink-3"> </span></button></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
          <div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 400px">
              <div class="modal-content position-relative p-5">
                <div class="d-flex align-items-center">
                  <div class="lottie me-3" data-options='{"path":"../assets/img/animated-icons/warning-light.json"}'></div>
                  <div class="flex-1"><button class="btn btn-link text-danger position-absolute top-0 end-0 mt-2 me-2" data-bs-dismiss="modal"><span class="fas fa-times"></span></button>
                    <p class="mb-0">You don't have access to the link. Please try again.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main><!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->

    

    <!-- ===============================================--><!--    JavaScripts--><!-- ===============================================-->
    <script src="../vendors/popper/popper.min.js"></script>
    <script src="../vendors/bootstrap/bootstrap.min.js"></script>
    <script src="../vendors/anchorjs/anchor.min.js"></script>
    <script src="../vendors/is/is.min.js"></script>
    <script src="../vendors/flatpickr/flatpickr.min.js"></script>
    <script src="../vendors/dropzone/dropzone-min.js"></script>
    <script src="../vendors/lottie/lottie.min.js"></script>
    <script src="../vendors/validator/validator.min.js"></script>
    <script src="../vendors/fontawesome/all.min.js"></script>
    <script src="../vendors/lodash/lodash.min.js"></script>
    <script src="../vendors/list.js/list.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
          // Password validation logic
          const passwordInput = document.getElementById("bootstrap-wizard-wizard-password");
          const confirmPasswordInput = document.getElementById("bootstrap-wizard-wizard-confirm-password");
          const nextButtons = document.querySelectorAll(".next button"); // Select all next buttons
          const passwordRequirements = document.getElementById("password-requirements");

          function validatePassword() {
              const password = passwordInput.value;
              const confirmPassword = confirmPasswordInput.value;

              // Regular expression for password validation
              const passwordPattern = /^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,16}$/;

              if (passwordPattern.test(password)) {
                  passwordRequirements.style.display = "none"; // Hide requirements if valid
              } else {
                  passwordRequirements.style.display = "block"; // Show requirements if invalid
              }

              if (passwordPattern.test(password) && password === confirmPassword) {
                  nextButtons[0].disabled = false; // Enable first next button
              } else {
                  nextButtons[0].disabled = true; // Disable if invalid
              }
          }

          // Add event listeners for real-time validation
          passwordInput.addEventListener("input", validatePassword);
          confirmPasswordInput.addEventListener("input", validatePassword);

          // Initially disable the first next button
          nextButtons[0].disabled = true;

          // File upload validation logic
          const fileInput = document.querySelector('input[type="file"][name="pfp"]');

          if (fileInput && nextButtons.length > 1) {
              const secondNextButton = nextButtons[1]; // Target the second "Next" button
              secondNextButton.disabled = true; // Disable initially

              fileInput.addEventListener("change", function () {
                  if (fileInput.files.length > 0) {
                      secondNextButton.disabled = false; // Enable if file is selected
                  } else {
                      secondNextButton.disabled = true; // Keep disabled if no file
                  }
              });
          }
      });
    </script>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
          document.querySelectorAll("form[data-wizard-form]").forEach(form => {
              form.addEventListener("submit", function (e) {
                  e.preventDefault(); // Prevent default form submission

                  let formData = new FormData(this);
                  let actionUrl = this.getAttribute("action");

                  fetch(actionUrl, {
                      method: "POST",
                      body: formData
                  })
                  .then(response => response.json()) // Expecting JSON response
                  .then(data => {
                      if (data.success) {
                          moveToNextStep(this); // Proceed only if success is true
                      } else {
                          showErrorMessage(data.message || "An error occurred. Please try again.");
                      }
                  })
                  .catch(error => {
                      console.error("Error:", error);
                      showErrorMessage("Server error. Please try again.");
                  });
              });
          });

          function moveToNextStep(form) {
              let currentStep = form.closest(".tab-pane"); // Find the current tab
              let nextStep = currentStep.nextElementSibling; // Get the next tab

              if (nextStep && nextStep.classList.contains("tab-pane")) {
                  // Hide current tab and show next
                  currentStep.classList.remove("active");
                  nextStep.classList.add("active");

                  // Update navigation
                  let currentNav = document.querySelector(`[href="#${currentStep.id}"]`);
                  let nextNav = document.querySelector(`[href="#${nextStep.id}"]`);
                  if (currentNav && nextNav) {
                      currentNav.classList.remove("active");
                      nextNav.classList.add("active");
                  }
              }
          }

          function showErrorMessage(message) {
              // Show error modal or alert
              let errorModal = new bootstrap.Modal(document.getElementById("error-modal"));
              document.querySelector("#error-modal p").innerText = message;
              errorModal.show();
          }
      });
    </script>

  </body>


<!-- Mirrored from prium.github.io/falcon/v3.22.0/pages/authentication/wizard.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 14 Oct 2024 08:15:59 GMT -->
</html>