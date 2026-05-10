<?php 
// Unset the session variable if it exists
if (isset($_SESSION['outbound_id'])) {
    unset($_SESSION['outbound_id']);
}

// Sanitize GET parameters
$get_date = $get_type = $get_warehouse_id = null;
if (isset($_GET['date_range'], $_GET['type'], $_GET['wh'])) {
    $get_date = filter_input(INPUT_GET, 'date_range', FILTER_SANITIZE_STRING);
    $get_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
    $get_warehouse_id = filter_input(INPUT_GET, 'wh', FILTER_SANITIZE_STRING);
}

// Pagination setup
$count_sql = "SELECT COUNT(*) as total FROM outbound_logs";
$count_res = $conn->query($count_sql);
$total_rows = $count_res->fetch_assoc()['total'];

$limit = 10;
$total_pages = ceil($total_rows / $limit);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Prepare warehouse ID filter
$quoted_warehouse_ids = array_map(function ($id) {
    return "'" . trim($id) . "'";
}, $user_warehouse_ids);
$imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);

// Filter by date and warehouse
if (isset($get_date) && isset($get_type)) {

    // Date range filter
    if (strpos($get_date, "to") !== false) {
        list($start, $end) = explode(" to ", $get_date);
        $start_date = date("Y-m-d H:i:s", strtotime(trim($start) . " 00:00:01"));
        $end_date = date("Y-m-d H:i:s", strtotime(trim($end) . " 23:59:59"));
    } else {
        $single_date = trim($get_date);
        $start_date = date("Y-m-d H:i:s", strtotime($single_date . " 00:00:00"));
        $end_date = date("Y-m-d H:i:s", strtotime($single_date . " 23:59:59"));
    }

    $additional_date_query = "AND ol.date_sent BETWEEN '$start_date' AND '$end_date'";

    // Warehouse filter
    if (!empty($get_warehouse_id)) {
        $warehouse_query = "ol.warehouse = '$get_warehouse_id'";
    } else {
        $warehouse_query = "ol.warehouse IN ($imploded_warehouse_ids)";
    }

    $outbound_sql = "
        SELECT ol.*, u.user_fname, u.user_lname, w.warehouse_name, ol.status, ol.order_line_id, ol.order_num
        FROM outbound_logs ol
        LEFT JOIN users u ON u.hashed_id = ol.user_id
        LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
        WHERE $warehouse_query
        $additional_date_query
        ORDER BY ol.id DESC
    ";

} else {
    $outbound_sql = "
        SELECT ol.*, u.user_fname, u.user_lname, w.warehouse_name, ol.status, ol.order_line_id, ol.order_num
        FROM outbound_logs ol
        LEFT JOIN users u ON u.hashed_id = ol.user_id
        LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
        WHERE ol.warehouse IN ($imploded_warehouse_ids)
        ORDER BY ol.id DESC
    ";
}

$outbound_res = $conn->query($outbound_sql);
?>

<div class="card">
  <div class="card-header bg-primary bg-gradient">
    <h2 class="text-white">Outbound Logs <?php if (isset($get_date)) echo $get_date; ?></h2>
  </div>

  <div class="card-body overflow-hidden py-6 px-0">
    <div class="row justify-content-between gx-3 gy-0 px-3">
      
        <div class="table-responsive scrollbar">
          <table class="table mb-0 data-table fs-10" data-datatables='{"paging":true,"scrollY":"600px","scrollCollapse":true}'>
            <thead class="bg-200">
              <tr>
                <th class="text-900 sort" data-sort="outbound_no">Outbound no.</th>
                <th class="text-900 sort" data-sort="outbound_status">Fulfillment Status</th>
                <th class="text-900 sort text-end" data-sort="order_no">Order #</th>
                <th class="text-900 sort text-end" data-sort="order_line">Order Line ID</th>
                <th class="text-900 sort" data-sort="warehouse">Warehouse</th>
                <th class="text-900 sort" data-sort="date">Date</th>
                <th class="text-900 sort" data-sort="receiver">Client</th>
                <th class="text-900 sort" data-sort="outbounder">Staff</th>
                <th class="d-none">Barcodes</th>
              </tr>
            </thead>
            <tbody class="list">
              <?php while ($row = $outbound_res->fetch_assoc()) { 
                $outbound_id = $row['hashed_id'];
                $outbound_warehouse = $row['warehouse_name'];
                $outbound_date = $row['date_sent'];
                $outbound_receiver = $row['customer_fullname'];
                $order_no = $row['order_num'];
                $order_line = $row['order_line_id'];
                $outbounder = $row['user_fname'] . " " . $row['user_lname'];

                // Determine fulfillment status badge
                switch ($row['status']) {
                    case 0:
                        $outbound_status = '<span class="badge rounded-pill badge-subtle-success">Paid</span>';
                        break;
                    case 1:
                        $outbound_status = '<span class="badge rounded-pill badge-subtle-success">Paid w/ return</span><span class="badge rounded-pill badge-subtle-danger">-1</span>';
                        break;
                    case 2:
                        $outbound_status = '<span class="badge rounded-pill badge-subtle-danger">Returned</span>';
                        break;
                    case 3:
                        $outbound_status = '<span class="badge rounded-pill badge-subtle-danger">Void Requested</span>';
                        break;
                    case 4:
                        $outbound_status = '<span class="badge rounded-pill badge-subtle-primary">Voided</span>';
                        break;
                    case 5:
                        $outbound_status = '<span class="badge rounded-pill badge-subtle-danger">Void Rejected</span>';
                        break;
                    case 6:
                        $outbound_status = '<span class="badge rounded-pill badge-subtle-info">Outbounded</span>';
                        break;
                    default:
                        $outbound_status = '<span class="badge rounded-pill badge-subtle-secondary">Unknown</span>';
                        break;
                }
              ?>
              <tr>
                <td class="outbound_no">
                  <a type="button" data-bs-toggle="modal" data-bs-target="#view-modal" target-id="<?php echo $outbound_id; ?>">
                    <?php echo $outbound_id; ?>
                  </a>
                </td>
                <td class="outbound_status"><?php echo $outbound_status; ?></td>
                <td class="order_no text-end"><?php echo $order_no; ?></td>
                <td class="order_line text-end"><?php echo $order_line; ?></td>
                <td class="warehouse"><?php echo $outbound_warehouse; ?></td>
                <td class="date"><?php echo $outbound_date; ?></td>
                <td class="receiver"><?php echo $outbound_receiver; ?></td>
                <td class="outbounder"><?php echo $outbounder; ?></td>
                <td class="d-none">
                  <?php 
                  $barcode_query = "SELECT unique_barcode FROM outbound_content WHERE hashed_id = '$outbound_id'";
                  $barcode_res = $conn->query($barcode_query);
                  if ($barcode_res->num_rows > 0) {
                      while ($row = $barcode_res->fetch_assoc()) {
                          echo $row['unique_barcode'] . ",";
                      }
                  }
                  ?>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        

      
    </div>
  </div>
</div>




<!-- Modal for Viewing Content -->
<div class="modal fade" id="view-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div id="target-id"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>



<?php 
if(isset($_GET['notnot'])){
    $notnot = $_GET['notnot'];

    // Sanitize input
    $notnot = $conn->real_escape_string($notnot);

    // Correct the query: MySQL doesn't have a built-in SHA256 comparison like this
    // Instead, compute SHA256 in PHP or use MySQL's SHA2 function properly
    $notification_query = "SELECT `message` FROM `notification` WHERE SHA2(`id`, 256) = '$notnot'";
    
    $not_result = $conn->query($notification_query);

    if ($not_result && $not_result->num_rows > 0) {
        $row = $not_result->fetch_assoc();
        $notification_message = $row['message'];

        // Extract only the number using regex
        if (preg_match('/\d+/', $notification_message, $matches)) {
            $number = $matches[0]; // This will be something like 0000004454
            // echo "Extracted number: " . $number;
        } else {
            // echo "No number found in the message.";
        }
    } else {
        // echo "Notification not found.";
    }
?>
<!-- Auto trigger script -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const targetId = "<?php echo $number; ?>";
    if (targetId) {
      const anchor = document.querySelector(`a[target-id="${targetId}"]`);
      if (anchor) {
        anchor.click();
      }
    }
  });
</script>
<?php
}
?>



<script>
// Load content into modal on click
$(document).on("click", "a[data-bs-toggle='modal']", function() {
    var targetId = $(this).attr("target-id"); // Get unique key
    $("#target-id").load("form-content.php?id=" + targetId); // Load content
});

$(document).on('submit', '.void-form', function(e) {
    e.preventDefault(); // Stop default submission
    const $form = $(this);
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you really want to submit this form?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, submit it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: $form.attr('action'),
                type: $form.attr('method'),
                data: $form.serialize(),
                success: function(response) {
                    Swal.fire({
                        title: 'Success!',
                        text: response,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error!',
                        text: xhr.responseText || 'Something went wrong.',
                        icon: 'error'
                    });
                }
            });
        }
    });
});


$(document).on('submit', '.void-decision', function(e) {
    e.preventDefault(); // Prevent default form submission
    const $decisionForm = $(this);

    Swal.fire({
        title: 'Are you sure?',
        text: "Do you really want to submit this form?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, submit it!',
        cancelButtonText: 'Cancel'
    }).then((confirmation) => {
        if (confirmation.isConfirmed) {
            $.ajax({
                url: $decisionForm.attr('action'),
                type: $decisionForm.attr('method'),
                data: $decisionForm.serialize(),
                success: function(serverResponse) {
                    Swal.fire({
                        title: 'Success!',
                        text: serverResponse,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                },
                error: function(errorResponse) {
                    Swal.fire({
                        title: 'Error!',
                        text: errorResponse.responseText || 'Something went wrong.',
                        icon: 'error'
                    });
                }
            });
        }
    });
});

$(document).on("click", ".paid_btn", function () {
    const outboundId = $(this).data("targetid");

    Swal.fire({
        title: 'Mark as Paid?',
        text: "Are you sure you want to mark this as paid?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, mark as paid'
    }).then((result) => {
        if (result.isConfirmed) {
            // Perform AJAX call to PHP
            $.get("../config/paid_outbound.php", { name: outboundId })
                .done(function (data) {
                    Swal.fire('Success!', 'The record has been marked as paid.', 'success');
                    // Optional: disable button or update status visually
                    // $(this).prop("disabled", true);
                    window.location.href = window.location.href;
                })
                .fail(function () {
                    Swal.fire('Error!', 'There was a problem processing your request.', 'error');
                });
        }
    });
});
</script>
