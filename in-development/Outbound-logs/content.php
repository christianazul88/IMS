<?php 
// Unset the session variable if it exists
if (isset($_SESSION['outbound_id'])) {
    unset($_SESSION['outbound_id']);
}


/* ======================================================
   GET PARAMETERS
====================================================== */
$get_date         = $_GET['date_range'] ?? null;
$get_type         = $_GET['type'] ?? null;
$get_warehouse_id = $_GET['wh'] ?? null;
$search           = $_GET['search'] ?? null;

/* ======================================================
   PAGINATION
====================================================== */
$limit = 25;
$page  = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

/* ======================================================
   WAREHOUSE PLACEHOLDERS
====================================================== */
$warehouse_placeholders = implode(',', array_fill(0, count($user_warehouse_ids), '?'));

/* ======================================================
   QUERY BUILD VARIABLES
====================================================== */
$params   = [];
$types    = "";
$date_sql = "";

/* ======================================================
   DATE FILTER
====================================================== */
if (!empty($get_date)) {
    if (strpos($get_date, "to") !== false) {
        list($start, $end) = explode(" to ", $get_date);
        $start_date = date("Y-m-d 00:00:01", strtotime(trim($start)));
        $end_date   = date("Y-m-d 23:59:59", strtotime(trim($end)));
    } else {
        $start_date = date("Y-m-d 00:00:01", strtotime($get_date));
        $end_date   = date("Y-m-d 23:59:59", strtotime($get_date));
    }
    $date_sql = " AND ol.date_sent BETWEEN ? AND ? ";
    $params[] = $start_date;
    $params[] = $end_date;
    $types   .= "ss";
}

/* ======================================================
   WAREHOUSE FILTER
====================================================== */
if (!empty($get_warehouse_id)) {
    $warehouse_sql = "ol.warehouse = ?";
    $params[] = $get_warehouse_id;
    $types   .= "s";
} else {
    $warehouse_sql = "ol.warehouse IN ($warehouse_placeholders)";
    foreach ($user_warehouse_ids as $wid) {
        $params[] = $wid;
        $types   .= "s";
    }
}

/* ======================================================
   SEARCH FILTER
====================================================== */
$search_sql = "";
if (!empty($search)) {
    $search_sql = "
        AND (
            ol.hashed_id LIKE ?
            OR ol.order_num LIKE ?
            OR ol.order_line_id LIKE ?
            OR ol.customer_fullname LIKE ?
            OR w.warehouse_name LIKE ?
            OR u.user_fname LIKE ?
            OR u.user_lname LIKE ?
            OR EXISTS (
                SELECT 1 FROM outbound_content oc
                WHERE oc.hashed_id = ol.hashed_id
                AND oc.unique_barcode LIKE ?
            )
        )
    ";
    $search_term = "%" . $search . "%";
    for ($i = 0; $i < 8; $i++) {
        $params[] = $search_term;
        $types   .= "s";
    }
}

/* ======================================================
   COUNT QUERY
====================================================== */
$count_sql = "
    SELECT COUNT(*)
    FROM outbound_logs ol
    LEFT JOIN users u ON u.hashed_id = ol.user_id
    LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
    WHERE $warehouse_sql
    $date_sql
    $search_sql
";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($total_rows);
$stmt->fetch();
$stmt->close();
$total_pages = ceil($total_rows / $limit);

/* ======================================================
   MAIN QUERY
====================================================== */
$main_sql = "
    SELECT 
        ol.*, 
        u.user_fname, 
        u.user_lname, 
        w.warehouse_name
    FROM outbound_logs ol
    LEFT JOIN users u ON u.hashed_id = ol.user_id
    LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
    WHERE $warehouse_sql
    $date_sql
    $search_sql
    ORDER BY ol.id DESC
    LIMIT ? OFFSET ?
";

$params_main   = $params;
$params_main[] = $limit;
$params_main[] = $offset;
$types_main    = $types . "ii";

$stmt = $conn->prepare($main_sql);
$stmt->bind_param($types_main, ...$params_main);
$stmt->execute();
$result = $stmt->get_result();

/* ======================================================
   STORE ROWS
====================================================== */
$outbound_rows = [];
$outbound_ids  = [];
while ($row = $result->fetch_assoc()) {
    $outbound_rows[] = $row;
    $outbound_ids[]  = $row['hashed_id'];
}

/* ======================================================
   BARCODE QUERY
====================================================== */
$barcode_map = [];
if (!empty($outbound_ids)) {
    $placeholders = implode(',', array_fill(0, count($outbound_ids), '?'));
    $sql = "
        SELECT hashed_id, unique_barcode
        FROM outbound_content
        WHERE hashed_id IN ($placeholders)
    ";
    $stmt = $conn->prepare($sql);
    $types = str_repeat("s", count($outbound_ids));
    $stmt->bind_param($types, ...$outbound_ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $barcode_map[$row['hashed_id']][] = $row['unique_barcode'];
    }
}

/* ======================================================
   ROW RANGE
====================================================== */
$start_row = ($page - 1) * $limit + 1;
$end_row   = min($page * $limit, $total_rows);
?>

<div class="px-3 mb-2">
    <small class="text-muted">
        Showing <?= $start_row ?>–<?= $end_row ?> of <?= number_format($total_rows) ?> results
    </small>
</div>

<div class="card">
    <div class="card-header bg-primary bg-gradient">
        <h2 class="text-white">Outbound Logs <?= htmlspecialchars($get_date ?? '') ?></h2>
    </div>

    <div class="card-body overflow-hidden py-6 px-0">
        <!-- Search Form -->
        <div class="row">
            <div class="col-4 mb-3 mx-3">
                <form method="GET">
                    <input type="hidden" name="date_range" value="<?= htmlspecialchars($get_date) ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($get_type) ?>">
                    <input type="hidden" name="wh" value="<?= htmlspecialchars($get_warehouse_id) ?>">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search outbound, order, client, barcode..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>
        

        <!-- Table -->
        <div class="row justify-content-between gx-3 gy-0 px-3">
            <div class="table-responsive scrollbar">
                <table class="table mb-0 fs-10">
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
                        <?php foreach ($outbound_rows as $row): 
                            $outbound_id        = $row['hashed_id'];
                            $outbound_warehouse = $row['warehouse_name'];
                            $outbound_date      = $row['date_sent'];
                            $outbound_receiver  = $row['customer_fullname'];
                            $order_no           = $row['order_num'];
                            $order_line         = $row['order_line_id'];
                            $outbounder         = $row['user_fname'] . " " . $row['user_lname'];

                            // Status badge
                            $status_map = [
                                0 => '<span class="badge rounded-pill badge-subtle-success">Paid</span>',
                                1 => '<span class="badge rounded-pill badge-subtle-success">Paid w/ return</span><span class="badge rounded-pill badge-subtle-danger">-1</span>',
                                2 => '<span class="badge rounded-pill badge-subtle-danger">Returned</span>',
                                3 => '<span class="badge rounded-pill badge-subtle-danger">Void Requested</span>',
                                4 => '<span class="badge rounded-pill badge-subtle-primary">Voided</span>',
                                5 => '<span class="badge rounded-pill badge-subtle-danger">Void Rejected</span>',
                                6 => '<span class="badge rounded-pill badge-subtle-info">Outbounded</span>',
                            ];
                            $outbound_status = $status_map[$row['status']] ?? '<span class="badge rounded-pill badge-subtle-secondary">Unknown</span>';
                        ?>
                        <tr>
                            <td class="outbound_no">
                                <a type="button" data-bs-toggle="modal" data-bs-target="#view-modal" target-id="<?= $outbound_id ?>">
                                    <?= $outbound_id ?>
                                </a>
                            </td>
                            <td class="outbound_status"><?= $outbound_status ?></td>
                            <td class="order_no text-end"><?= $order_no ?></td>
                            <td class="order_line text-end"><?= $order_line ?></td>
                            <td class="warehouse"><?= $outbound_warehouse ?></td>
                            <td class="date"><?= $outbound_date ?></td>
                            <td class="receiver"><?= $outbound_receiver ?></td>
                            <td class="outbounder"><?= $outbounder ?></td>
                            <td class="d-none">
                                <?= isset($barcode_map[$outbound_id]) ? implode(",", $barcode_map[$outbound_id]) : '' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="row mt-3 px-3">
            <div class="col">
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php
                        $query = $_GET;
                        $range = 2; 
                        $start = max(1, $page - $range);
                        $end   = min($total_pages, $page + $range);

                        // Previous button
                        if ($page > 1) {
                            $query['page'] = $page - 1;
                            echo '<li class="page-item"><a class="page-link" href="?'.http_build_query($query).'">Previous</a></li>';
                        }

                        // First page
                        if ($start > 1) {
                            $query['page'] = 1;
                            echo '<li class="page-item"><a class="page-link" href="?'.http_build_query($query).'">1</a></li>';
                            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }

                        // Middle pages
                        for ($i = $start; $i <= $end; $i++) {
                            $query['page'] = $i;
                            $active = ($i == $page) ? "active" : "";
                            echo '<li class="page-item '.$active.'"><a class="page-link" href="?'.http_build_query($query).'">'.$i.'</a></li>';
                        }

                        // Last page
                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            $query['page'] = $total_pages;
                            echo '<li class="page-item"><a class="page-link" href="?'.http_build_query($query).'">'.$total_pages.'</a></li>';
                        }

                        // Next button
                        if ($page < $total_pages) {
                            $query['page'] = $page + 1;
                            echo '<li class="page-item"><a class="page-link" href="?'.http_build_query($query).'">Next</a></li>';
                        }
                        ?>
                    </ul>
                </nav>
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
