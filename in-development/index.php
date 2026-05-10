<?php
session_set_cookie_params(0);
session_start();

if (isset($_SESSION['user_id'])) {
            // Redirect to Dashboard if the account is active
            header("Location: dashboard/");
            exit(); // Prevent further script execution after a redirect
    
}
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Document Title -->
    <title>LPO | Login</title>
    
    <!-- ===============================================--><!--    Favicons--><!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/logo/LPO Logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/logo/LPO Logo.png">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/img/logo/LPO Logo.png">
    <link rel="manifest" href="assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    <script src="assets/js/config.js"></script>
    <script src="vendors/simplebar/simplebar.min.js"></script>
    
    <!-- Stylesheets -->
    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="vendors/simplebar/simplebar.min.css" rel="stylesheet">
    <link href="assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="assets/css/user.min.css" rel="stylesheet" id="user-style-default">
    
    <script>
      var isRTL = JSON.parse(localStorage.getItem('isRTL'));
      if (isRTL) {
        document.getElementById('style-default').setAttribute('disabled', true);
        document.getElementById('user-style-default').setAttribute('disabled', true);
        document.querySelector('html').setAttribute('dir', 'rtl');
      } else {
        document.getElementById('style-rtl').setAttribute('disabled', true);
        document.getElementById('user-style-rtl').setAttribute('disabled', true);
      }
    </script>
</head>

<body>
    <!-- Main Content -->
    <main class="main" id="top">
        <div class="container-fluid">
            <div class="row min-vh-100 flex-center g-0">
                <div class="col-lg-8 col-xxl-5 py-3 position-relative">
                    <img class="bg-auth-circle-shape" src="assets/img/icons/spot-illustrations/bg-shape.png" alt="" width="250">
                    <img class="bg-auth-circle-shape-2" src="assets/img/icons/spot-illustrations/shape-1.png" alt="" width="150">
                    
                    <div class="card overflow-hidden z-1">
                        <div class="card-body p-0">
                            <div class="row g-0 h-100">
                                <!-- Left Side (Image & Info) -->
                                <div class="col-md-5 text-center bg-light bg-gradient">
                                    <div class="position-relative p-4 pt-md-5 pb-md-7" data-bs-theme="light">
                                        <div class="bg-holder bg-auth-card-shape" style="background-image:url(assets/img/icons/spot-illustrations/half-circle.png);"></div>
                                        <div class="z-1 position-relative">
                                            <img class="me-2" src="../assets/img/logo/LPO Logo.png" alt="" width="120" />
                                            <!-- <a class="link-light mb-4 font-sans-serif fs-5 d-inline-block fw-bolder" href="">IMS</a> -->
                                            <p class="opacity-75 text-dark">Inventory Management System</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 mb-4 mt-md-4 mb-md-5" data-bs-theme="light">
                                        <!-- <p class="mb-0 mt-4 mt-md-5 fs-10 fw-semi-bold text-white opacity-75">Read our <a class="text-decoration-underline text-white" href="#!">terms</a> and <a class="text-decoration-underline text-white" href="#!">conditions </a></p> -->
                                    </div>
                                </div>
                                
                                <!-- Right Side (Login Form) -->
                                <div class="col-md-7 d-flex flex-center">
                                    <div class="p-4 p-md-5 flex-grow-1">
                                        <div class="row flex-between-center">
                                            <div class="col-auto">
                                                <h3>Account Login</h3>
                                            </div>
                                        </div>
                                        <form id="login-form" action="config/login.php" method="POST">
                                            <div class="mb-3">
                                                <label class="form-label" for="card-email">Email address</label>
                                                <input class="form-control" id="email" name="email" type="email" />
                                            </div>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <label class="form-label" for="card-password">Password</label>
                                                </div>
                                                <input class="form-control" id="password" name="password" type="password" />
                                            </div>
                                            <!-- <div class="row flex-between-center">
                                                <div class="col-auto">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" id="card-checkbox" />
                                                        <label class="form-check-label mb-0" for="card-checkbox">Show Password</label>
                                                    </div>
                                                </div>
                                            </div> -->
                                            <div class="mb-3" id="btn-login-container">
                                                <button class="btn btn-primary d-block w-100 mt-3" id="btn-login" type="submit" name="submit">Log in</button>
                                                 
                                            </div>
                                            <div class="mb-3" id="loading-btn-container" style="display:none;">
                                                <button id="loading-btn" class="btn btn-primary d-block w-100 mt-3" type="button" disabled="">
                                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                    Loading...
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <!-- End of Right Side -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- JavaScripts -->
    <script src="vendors/popper/popper.min.js"></script>
    <script src="vendors/bootstrap/bootstrap.min.js"></script>
    <script src="vendors/anchorjs/anchor.min.js"></script>
    <script src="vendors/is/is.min.js"></script>
    <script src="vendors/fontawesome/all.min.js"></script>
    <script src="vendors/lodash/lodash.min.js"></script>
    <script src="vendors/list.js/list.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent form from submitting normally

            const loginButtonContainer = document.getElementById('btn-login-container');
            const loadingButtonContainer = document.getElementById('loading-btn-container');

            // Hide login button container and show loading button
            loginButtonContainer.style.display = 'none';
            loadingButtonContainer.style.display = 'block';

            // Collect form data
            const formData = new FormData(this);

            // Send AJAX request
            fetch('config/login.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Login successful, delay redirection by 1.5 seconds
                    setTimeout(() => {
                        window.location.href = 'dashboard/';
                    }, 1500);
                } else {
                    // Login failed, show SweetAlert2 error message
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid credentials!',
                            text: data.message || 'Please check your email and password and try again.',
                            confirmButtonText: 'OK'
                        });
                        loadingButtonContainer.style.display = 'none'; // Hide loading button
                        loginButtonContainer.style.display = 'block'; // Show login button container again
                    }, 1500);
                }
            })
            .catch(error => {
                // Handle any network or unexpected errors
                console.error('Error:', error);
                setTimeout(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred. Please try again later.',
                        confirmButtonText: 'OK'
                    });
                    loadingButtonContainer.style.display = 'none'; // Hide loading button
                    loginButtonContainer.style.display = 'block'; // Show login button container again
                }, 1500);
            });
        });
    </script>


</body>

</html>
