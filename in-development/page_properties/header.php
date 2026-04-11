<?php 
if (isset($_GET['notnot'])) {
    $notification_id = $_GET['notnot'];
    for($i = 0; $i <= 100000; $i++){
        $hashed_num = hash('sha256', $i);
        if($hashed_num === $notification_id){
            // Prepare and bind
            $stmt = $conn->prepare("UPDATE notification SET `status` = 1 WHERE id = ?");
            $stmt->bind_param("i", $i);

            // Execute the query
            if ($stmt->execute()) {
                // echo "Notification updated successfully.";
                break;
            } else {
                echo "Error updating notification: " . $stmt->error;
            }
        }
    }
}
?>

<!-- ===============================================--><!--    Favicons--><!-- ===============================================-->
<link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../assets/img/logo/LPO Logo.png">
<link rel="icon" type="image/png" sizes="16x16" href="../../assets/img/logo/LPO Logo.png">
<link rel="shortcut icon" type="image/x-icon" href="../../assets/img/logo/LPO Logo.png">
<link rel="manifest" href="../assets/img/favicons/manifest.json">
<meta name="msapplication-TileImage" content="../assets/img/favicons/mstile-150x150.png">
<meta name="theme-color" content="#ffffff">

<script src="../assets/js/config.js"></script>
<script src="../vendors/simplebar/simplebar.min.js"></script>

<!-- ===============================================--><!--    Stylesheets--><!-- ===============================================-->
<link href="../vendors/dropzone/dropzone.css" rel="stylesheet">
<script src="../vendors/dropzone/dropzone-min.js"></script>
<link href="../vendors/select2/select2.min.css" rel="stylesheet">
<link href="../vendors/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<link href="../vendors/datatables.net-bs5/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../vendors/glightbox/glightbox.min.css" rel="stylesheet">
<link href="../vendors/plyr/plyr.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.gstatic.com/">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
<link href="../vendors/simplebar/simplebar.min.css" rel="stylesheet">
<link href="../assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
<link href="../assets/css/theme.min.css" rel="stylesheet" id="style-default">
<link href="../assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
<link href="../assets/css/user.min.css" rel="stylesheet" id="user-style-default">
<link href="../vendors/flatpickr/flatpickr.min.css" rel="stylesheet" />
<link href="../vendors/choices/choices.min.css" rel="stylesheet" />
<link href="../vendors/swiper/swiper-bundle.min.css" rel="stylesheet" />
<script src="../vendors/echarts/echarts.min.js"></script>
<script src="../vendors/jquery/jquery.min.js"></script>
<script src="../vendors/sortablejs/Sortable.min.js"></script>
<script src="../vendors/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css" rel="stylesheet"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    var isRTL = JSON.parse(localStorage.getItem('isRTL'));
    if (isRTL) {
    var linkDefault = document.getElementById('style-default');
    var userLinkDefault = document.getElementById('user-style-default');
    linkDefault.setAttribute('disabled', true);
    userLinkDefault.setAttribute('disabled', true);
    document.querySelector('html').setAttribute('dir', 'rtl');
    } else {
    var linkRTL = document.getElementById('style-rtl');
    var userLinkRTL = document.getElementById('user-style-rtl');
    linkRTL.setAttribute('disabled', true);
    userLinkRTL.setAttribute('disabled', true);
    }
</script>
<script>
    const lightbox = GLightbox({
        selector: '[data-glightbox]'
    });

</script>

<audio id="notification-sound" src="../../assets/audio/mixkit-bell-notification-933.wav" preload="auto"></audio>
