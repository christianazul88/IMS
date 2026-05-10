<?php
include "../config/database.php";
include "../config/on_session.php";

if(strpos($access, "inbound_logs")!==false || $user_position_name === "Administrator"){

} else {
  header("Location: ../500/");
}

// if (isset($_GET['success']) && $_GET['success'] == 0 && isset($_SESSION['unique_key'])) {
//   $uniqueKey = $_SESSION['unique_key'];
//   require_once '../../vendor/autoload.php';
//   // Query to fetch required data
//   $query = "
//       SELECT 
//           s.unique_barcode,
//           p.description AS product_description,
//           b.brand_name,
//           c.category_name
//       FROM stocks s
//       JOIN product p ON s.product_id = p.hashed_id
//       JOIN brand b ON p.brand = b.hashed_id
//       JOIN category c ON p.category = c.hashed_id
//       WHERE s.unique_key = ?";
  
//   $stmt = $conn->prepare($query);
//   $stmt->bind_param("s", $uniqueKey);
//   $stmt->execute();
//   $result = $stmt->get_result();

//   // Create a temporary directory for PDFs
//   $zip = new ZipArchive();
//   $zipFile = tempnam(sys_get_temp_dir(), 'pdf_zip_') . '.zip';
//   $zip->open($zipFile, ZipArchive::CREATE);

//   while ($row = $result->fetch_assoc()) {
//       $uniqueBarcode = $row['unique_barcode'];
//       $productDescription = $row['product_description'];
//       $brandName = $row['brand_name'];
//       $categoryName = $row['category_name'];

//       // Generate HTML content
//       $html = "<html><head><style>body { font-family: Arial, sans-serif; }</style></head>";
//       $html .= "<body>";
//       $html .= "<h1>Product: $productDescription</h1>";
//       $html .= "<p>Barcode: $uniqueBarcode</p>";
//       $html .= "<p>Brand: $brandName</p>";
//       $html .= "<p>Category: $categoryName</p>";
//       $html .= "<div class='barcode-container'>";
//       $html .= "<img alt='Barcode' src='../../assets/barcode/barcode.php?codetype=Code128&size=50&text=$uniqueBarcode&print=true'/>";
//       $html .= "</div>";
//       $html .= "</body></html>";

//       // Initialize mPDF for individual PDF
//       $mpdf = new \Mpdf\Mpdf();
//       $mpdf->WriteHTML($html);

//       // Save the PDF to a temporary file
//       $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', "$productDescription - $brandName - $categoryName - $uniqueBarcode") . '.pdf';
//       $pdfPath = sys_get_temp_dir() . '/' . $fileName;
//       $mpdf->Output($pdfPath, 'F');

//       // Add the file to the ZIP archive
//       $zip->addFile($pdfPath, $fileName);
//   }

//   $zip->close();
//   $stmt->close();

//   // Output the ZIP for download
//   header('Content-Type: application/zip');
//   header('Content-Disposition: attachment; filename="Products.zip"');
//   header('Content-Length: ' . filesize($zipFile));
//   readfile($zipFile);

//   // Cleanup
//   unlink($zipFile); // Delete ZIP file after download
//   exit;
// }
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
        <div class="modal fade" id="authentication-modal" tabindex="-1" role="dialog" aria-labelledby="authentication-modal-label" aria-hidden="true">
          <div class="modal-dialog mt-6" role="document">
            <div class="modal-content border-0">
              <div class="modal-header px-5 position-relative modal-shape-header bg-shape">
                <div class="position-relative z-1">
                  <h4 class="mb-0 text-white" id="authentication-modal-label">Register</h4>
                  <p class="fs-10 mb-0 text-white">Please create your free Falcon account</p>
                </div>
                <div data-bs-theme="dark"><button class="btn-close position-absolute top-0 end-0 mt-2 me-2" data-bs-dismiss="modal" aria-label="Close"></button></div>
              </div>
              <div class="modal-body py-4 px-5">
                <form>
                  <div class="mb-3"><label class="form-label" for="modal-auth-name">Name</label><input class="form-control" type="text" autocomplete="on" id="modal-auth-name" /></div>
                  <div class="mb-3"><label class="form-label" for="modal-auth-email">Email address</label><input class="form-control" type="email" autocomplete="on" id="modal-auth-email" /></div>
                  <div class="row gx-2">
                    <div class="mb-3 col-sm-6"><label class="form-label" for="modal-auth-password">Password</label><input class="form-control" type="password" autocomplete="on" id="modal-auth-password" /></div>
                    <div class="mb-3 col-sm-6"><label class="form-label" for="modal-auth-confirm-password">Confirm Password</label><input class="form-control" type="password" autocomplete="on" id="modal-auth-confirm-password" /></div>
                  </div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" id="modal-auth-register-checkbox" /><label class="form-label" for="modal-auth-register-checkbox">I accept the <a href="#!">terms </a>and <a class="white-space-nowrap" href="#!">privacy policy</a></label></div>
                  <div class="mb-3"><button class="btn btn-primary d-block w-100 mt-3" type="submit" name="submit">Register</button></div>
                </form>
                <div class="position-relative mt-5">
                  <hr />
                  <div class="divider-content-center">or register with</div>
                </div>
                <div class="row g-2 mt-2">
                  <div class="col-sm-6"><a class="btn btn-outline-google-plus btn-sm d-block w-100" href="#"><span class="fab fa-google-plus-g me-2" data-fa-transform="grow-8"></span> google</a></div>
                  <div class="col-sm-6"><a class="btn btn-outline-facebook btn-sm d-block w-100" href="#"><span class="fab fa-facebook-square me-2" data-fa-transform="grow-8"></span> facebook</a></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main><!-- ===============================================--><!--    End of Main Content--><!-- ===============================================-->

    <!-- ===============================================--><!--    JavaScripts--><!-- ===============================================-->
    <?php include "../page_properties/footer_main.php";?>
  </body>


<!-- Mirrored from prium.github.io/falcon/v3.22.0/pages/starter.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 14 Oct 2024 08:15:49 GMT -->
</html>