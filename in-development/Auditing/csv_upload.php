<?php
include "../config/database.php";
include "../config/on_session.php";

// Create csv_auditing table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS csv_auditing (
        id INT AUTO_INCREMENT PRIMARY KEY,
        status TINYINT NOT NULL CHECK (status IN (1, 2)),
        filename VARCHAR(255),
        csv_file LONGBLOB NOT NULL,
        user_id VARCHAR(100),
        date DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Save uploaded CSV file to csv_auditing if upload is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $filename = $_FILES['file']['name'];
    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileContents = file_get_contents($fileTmpPath);

    // Default values
    $status = 1;

    // Attempt to extract warehouse from the first data row
    if (($handle = fopen($fileTmpPath, 'r')) !== false) {
        $headers = fgetcsv($handle);
        $firstDataRow = fgetcsv($handle);
        if ($firstDataRow && isset($firstDataRow[2])) {
            $warehouse = strtoupper(trim($firstDataRow[2])); // assuming 3rd column is warehouse
        }
        fclose($handle);
    }

    // Prepare and insert into csv_auditing
    $stmt = $conn->prepare("INSERT INTO csv_auditing (status, filename, csv_file, user_id, date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $status, $filename, $fileContents, $user_id, $currentDateTime);
    $stmt->execute();
    $csv_id = $conn->insert_id;
    $stmt->close();
}



// Utility function to normalize strings
function normalize(string $str): string {
    return strtoupper(trim($str));
}

// Map of status codes to messages
$statusMessages = [
    0 => 'Paid',
    1 => 'Outbounded with returns',
    2 => 'All returned',
    3 => 'Void Requested',
    4 => 'Voided',
    5 => 'Void Rejected',
    6 => 'Outbounded',
];

// Expected CSV headers after normalization
$expectedHeaders = ['ORDER NUMBER', 'ORDER LINE ID', 'WAREHOUSE', 'CLIENT', 'FULFILLMENT STATUS', 'AMOUNT PAID'];

// Initialize variables
$rows = [];
$results = [];
$error = '';
$summary = ['matched' => 0, 'mismatched' => 0, 'not_found' => 0];
$nonSortableRows = [];
$sortableRows = [];
$paidRows = [];

// Handle file upload and CSV parsing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        if (($handle = fopen($fileTmpPath, 'r')) !== false) {
            $headers = array_map('normalize', fgetcsv($handle));

            if ($headers !== $expectedHeaders) {
                $error = 'CSV headers do not match the expected format.';
            } else {
                while (($row = fgetcsv($handle)) !== false) {
                    $rows[] = array_map('normalize', $row);
                }
            }
            fclose($handle);
        } else {
            $error = 'Could not open uploaded file.';
        }
    } else {
        $error = 'File upload error.';
    }
} else {
    $error = 'No file uploaded.';
}

// Process data if no errors and rows exist
if (empty($error) && !empty($rows)) {
    $stmt = $conn->prepare("
        SELECT 
            UPPER(ol.customer_fullname) AS CUSTOMER, 
            UPPER(ol.status) AS STATUS, 
            SUM(oc.sold_price) AS TOTAL_SALES_EACH_OUTBOUND,
            w.hashed_id AS WAREHOUSE_ID,
            UPPER(w.warehouse_name) AS WAREHOUSE_NAME
        FROM outbound_logs ol
        LEFT JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id
        LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
        WHERE ol.order_num = ? AND ol.order_line_id = ? AND ol.status IN (0, 6)
        GROUP BY ol.customer_fullname, ol.status, w.hashed_id, w.warehouse_name
        LIMIT 1
    ");

    foreach ($rows as $row) {
        [$ORDERNUMBER, $ORDERLINEID, $WAREHOUSE, $CLIENT, $FULFILLMENTSTATUS, $AMOUNTPAID] = $row;
        // Remove extra spaces
        $CLIENT = trim(preg_replace('/\s+/', ' ', $CLIENT));


        $stmt->bind_param("ss", $ORDERNUMBER, $ORDERLINEID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();

            $CUSTOMER = trim(preg_replace('/\s+/', ' ', $data['CUSTOMER']));
            $OUTBOUND_STATUS = $data['STATUS'];
            $EXPECTED_PAYMENT = $data['TOTAL_SALES_EACH_OUTBOUND'];
            $WAREHOUSE_NAME = $data['WAREHOUSE_NAME'];

            $statusCode = (int) trim($OUTBOUND_STATUS);
            $fulfillment = strtoupper($FULFILLMENTSTATUS);

            // Initialize eligibility and message
            $eligible = false;
            $EXPECTED_OR_MESSAGE = '';

            if ($CUSTOMER === $CLIENT && $WAREHOUSE === $WAREHOUSE_NAME) {
                // Eligibility logic based on status codes and fulfillment
                if ($statusCode == 6 && in_array($fulfillment, ['PAID', 'UNPAID', 'PENDING'])) {
                    $eligible = true;
                } elseif ($statusCode == 0 && in_array($fulfillment, ['RETURN', 'REFUND'])) {
                    $EXPECTED_OR_MESSAGE = "Already Paid. Returns or refunds must be processed through the Return Module in IMS. Proof of return is required.";
                } elseif ($statusCode == 1 && in_array($fulfillment, ['RETURN', 'REFUND'])) {
                    $EXPECTED_OR_MESSAGE = "Some items on this order were already returned. Please use the Return Module in IMS to process returns/refunds with proof.";
                } elseif ($statusCode == 2) {
                    $EXPECTED_OR_MESSAGE = "All items on this order were already returned/refunded. No further action required.";
                } elseif (in_array($statusCode, [3])) {
                    $EXPECTED_OR_MESSAGE = "Void request in progress. This order is not eligible for payment check. Please search for another outbound.";
                } elseif (in_array($statusCode, [4, 5])) {
                    $EXPECTED_OR_MESSAGE = "This order was voided and is not eligible for payment check. Please search for another matching outbound.";
                } elseif ($statusCode == 0 && $fulfillment === 'PAID') {
                    $EXPECTED_OR_MESSAGE = "This order has already been paid, so it cannot be paid again.";
                }

                if ($eligible) {
                    $EXPECTED_OR_MESSAGE = number_format($EXPECTED_PAYMENT, 2);
                }

                $rowData = [
                    'ORDERNUMBER'       => $ORDERNUMBER,
                    'ORDERLINEID'       => $ORDERLINEID,
                    'WAREHOUSE'         => $WAREHOUSE,
                    'CLIENT'            => $CLIENT,
                    'FULFILLMENTSTATUS' => $FULFILLMENTSTATUS,
                    'AMOUNTPAID'        => $AMOUNTPAID,
                    'EXPECTED_PAYMENT'  => $EXPECTED_OR_MESSAGE,
                    'class'             => $eligible ? 'table-secondary' : 'table-warning'
                ];

                if ($statusCode === 0 && $fulfillment === 'PAID') {
                    $nonSortableRows[] = $rowData;
                } elseif($statusCode == 6 && $fulfillment === 'PAID'){
                    $paidRows[] = $rowData;
                } else {
                    $sortableRows[] = $rowData;
                } 

                $summary['matched']++;
            } else {
                // Client or Warehouse mismatch
                $message = ($CUSTOMER !== $CLIENT && $WAREHOUSE !== $WAREHOUSE_NAME)
                    ? 'Client and warehouse do not match the records in IMS.'
                    : (($CUSTOMER !== $CLIENT) ? 'Client does not match IMS record.' : 'Warehouse does not match IMS record.');

                $rowData = [
                    'ORDERNUMBER'       => $ORDERNUMBER,
                    'ORDERLINEID'       => $ORDERLINEID,
                    'WAREHOUSE'         => $WAREHOUSE,
                    'CLIENT'            => $CLIENT,
                    'FULFILLMENTSTATUS' => $FULFILLMENTSTATUS,
                    'AMOUNTPAID'        => $AMOUNTPAID,
                    'EXPECTED_PAYMENT'  => $message,
                    'class'             => 'table-warning'
                ];

                $sortableRows[] = $rowData;
                $summary['mismatched']++;
            }
        } else {
            // Not found in IMS
            $rowData = [
                'ORDERNUMBER'       => $ORDERNUMBER,
                'ORDERLINEID'       => $ORDERLINEID,
                'WAREHOUSE'         => $WAREHOUSE,
                'CLIENT'            => $CLIENT,
                'FULFILLMENTSTATUS' => $FULFILLMENTSTATUS,
                'AMOUNTPAID'        => $AMOUNTPAID,
                'EXPECTED_PAYMENT'  => 'Order number and line ID not found in IMS.',
                'class'             => 'table-danger'
            ];

            $sortableRows[] = $rowData;
            $summary['not_found']++;
        }
    }
}
?>
<style>
    #non-saved {
  position: relative;
  min-height: 100px;
}

#non-saved:empty::before {
  content: "drag data here";
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: #999;
  font-size: 16px;
  pointer-events: none;
  white-space: nowrap;
}

</style>
<!-- HTML OUTPUT SECTION -->
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif (!empty($sortableRows) || !empty($nonSortableRows) || !empty($paidRows)): ?>
    <div class="alert alert-info">
        Total: <?= count($sortableRows) + count($nonSortableRows) ?> rows |
        Matched: <?= $summary['matched'] ?> |
        Mismatched: <?= $summary['mismatched'] ?> |
        Not Found: <?= $summary['not_found'] ?>
    </div>

    <div class="row">
        <!-- NON-SORTABLE TABLE: Already Paid -->
        <div class="col-lg-6">
            <div class="border bg-white rounded-2 p-3 mb-3">
                <h6 class="text-muted">Already Paid & Data that wont be saved.</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th></th>
                                <th class="fs-11">ORDER NUMBER</th>
                                <th class="fs-11">ORDER LINE ID</th>
                                <th class="fs-11">WAREHOUSE</th>
                                <th class="fs-11">CLIENT</th>
                                <th class="fs-11">FULFILLMENT STATUS</th>
                                <th class="text-end fs-11">AMOUNT PAID</th>
                                <th class="text-end fs-11">EXPECTED PAYMENT / MESSAGE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nonSortableRows as $res): ?>
                                <tr class="<?= $res['class'] ?>">
                                    <td>
                                        <button class="btn btn-primary fs-11 view-details-btn" type="button" data-bs-toggle="modal" target-id="<?= $res['ORDERNUMBER'] ?>" data-bs-target="#staticBackdrop">View Details</button>
                                        <input type="text" name="order_num[]" value="<?php echo $res['ORDERNUMBER']; ?>" required  hidden>
                                        <input type="text" name="order_line[]" value="<?php echo $res['ORDERLINEID'];?>" required hidden>
                                        <input type="text" name="warehouse[]" value="<?php echo $res['WAREHOUSE']; ?>" required hidden>
                                        <input type="text" name="client[]" value="<?php echo trim(preg_replace('/\s+/', ' ', $res['CLIENT'])); ?>" required hidden>
                                        <input type="text" name="status[]" value="<?php echo $res['FULFILLMENTSTATUS']; ?>" required hidden>
                                        <input type="text" name="paid_amount[]" value="<?php echo $res['AMOUNTPAID']; ?>" hidden>
                                        <input type="text" name="expect_amount[]" value="<?php echo $res['EXPECTED_PAYMENT']; ?>" hidden>
                                        <input type="text" name="csv_id[]" value="<?php echo $csv_id; ?>" hidden>
                                    </td>
                                    <td class="fs-11"><?= $res['ORDERNUMBER'] ?></td>
                                    <td class="fs-11"><?= $res['ORDERLINEID'] ?></td>
                                    <td class="fs-11"><?= $res['WAREHOUSE'] ?></td>
                                    <td class="fs-11"><?= trim(preg_replace('/\s+/', ' ', $res['CLIENT'])) ?></td>
                                    <td class="fs-11"><?= $res['FULFILLMENTSTATUS'] ?></td>
                                    <td class="text-end fs-11"><small><?= $res['AMOUNTPAID'] ?></small></td>
                                    <td class="text-end fs-11"><small><?= $res['EXPECTED_PAYMENT'] ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tbody id="non-saved" data-sortable="data-sortable" style="min-height: 100px;">

                        </tbody>
                    </table>
                </div>
                <p class="text-muted fs-10">Please drag here data's that you dont want to be saved to "Pending and Unmatched Records" tab.</p>
            </div>
        </div>

        <!-- SORTABLE TABLE: All Others -->
        <div class="col-lg-6">
            <div class="border bg-white rounded-2 p-3 mb-3">
                <h6 class="text-muted">Only paid orders will automatically update the fulfillment status in the outbound logs, while unpaid and pending orders will be saved in this module. You can view them in the "Pending and Unmatched Records" tab. Returns and refunds must be processed through the Return Module in IMS.</h6>
                <form id="financeForm" action="../config/save-finance-auditing.php" method="post" enctype="multipart/form-data">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th></th>
                                <th class="fs-11">ORDER NUMBER</th>
                                <th class="fs-11">ORDER LINE ID</th>
                                <th class="fs-11">WAREHOUSE</th>
                                <th class="fs-11">CLIENT</th>
                                <th class="fs-11">FULFILLMENT STATUS</th>
                                <th class="text-end fs-11">AMOUNT PAID</th>
                                <th class="text-end fs-11">EXPECTED PAYMENT / MESSAGE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paidRows as $res): ?>
                                <tr class="<?= $res['class'] ?>">
                                    <td>
                                        <button class="btn btn-primary fs-11 view-details-btn" type="button" data-bs-toggle="modal" target-id="<?= $res['ORDERNUMBER'] ?>" data-bs-target="#staticBackdrop">View Details</button>
                                        <input type="text" name="order_num[]" value="<?php echo $res['ORDERNUMBER']; ?>" required  hidden>
                                        <input type="text" name="order_line[]" value="<?php echo $res['ORDERLINEID'];?>" required hidden>
                                        <input type="text" name="warehouse[]" value="<?php echo $res['WAREHOUSE']; ?>" required hidden>
                                        <input type="text" name="client[]" value="<?php echo trim(preg_replace('/\s+/', ' ', $res['CLIENT'])); ?>" required hidden>
                                        <input type="text" name="status[]" value="<?php echo $res['FULFILLMENTSTATUS']; ?>" required hidden>
                                        <input type="text" name="paid_amount[]" value="<?php echo $res['AMOUNTPAID']; ?>" hidden>
                                        <input type="text" name="expect_amount[]" value="<?php echo $res['EXPECTED_PAYMENT']; ?>" hidden>
                                        <input type="text" name="csv_id[]" value="<?php echo $csv_id; ?>" hidden>
                                    </td>
                                    <td class="fs-11"><?= $res['ORDERNUMBER'] ?></td>
                                    <td class="fs-11"><?= $res['ORDERLINEID'] ?></td>
                                    <td class="fs-11"><?= $res['WAREHOUSE'] ?></td>
                                    <td class="fs-11"><?= $res['CLIENT'] ?></td>
                                    <td class="fs-11"><?= $res['FULFILLMENTSTATUS'] ?></td>
                                    <td class="text-end fs-11"><?= $res['AMOUNTPAID'] ?></td>
                                    <td class="text-end fs-11"><?= $res['EXPECTED_PAYMENT'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tbody data-sortable="data-sortable" style="min-height: 100px;">
                            <?php foreach ($sortableRows as $res): ?>
                                <tr class="<?= $res['class'] ?>">
                                    <td class="fs-11">
                                        <button class="btn btn-primary fs-11 view-details-btn" type="button" data-bs-toggle="modal" target-id="<?= $res['ORDERNUMBER'] ?>" data-bs-target="#staticBackdrop">View Details</button>
                                        <input type="text" name="order_num[]" value="<?php echo $res['ORDERNUMBER']; ?>" required  hidden>
                                        <input type="text" name="order_line[]" value="<?php echo $res['ORDERLINEID'];?>" required hidden>
                                        <input type="text" name="warehouse[]" value="<?php echo $res['WAREHOUSE']; ?>" required hidden>
                                        <input type="text" name="client[]" value="<?php echo trim(preg_replace('/\s+/', ' ', $res['CLIENT'])); ?>" required hidden>
                                        <input type="text" name="status[]" value="<?php echo $res['FULFILLMENTSTATUS']; ?>" required hidden>
                                        <input type="text" name="paid_amount[]" value="<?php echo $res['AMOUNTPAID']; ?>" hidden>
                                        <input type="text" name="expect_amount[]" value="<?php echo $res['EXPECTED_PAYMENT']; ?>" hidden>
                                        <input type="text" name="csv_id[]" value="<?php echo $csv_id; ?>" hidden>
                                    </td>
                                    <td class="fs-11"><?= $res['ORDERNUMBER'] ?></td>
                                    <td class="fs-11"><?= $res['ORDERLINEID'] ?></td>
                                    <td class="fs-11"><?= $res['WAREHOUSE'] ?></td>
                                    <td class="fs-11"><?= $res['CLIENT'] ?></td>
                                    <td class="fs-11"><?= $res['FULFILLMENTSTATUS'] ?></td>
                                    <td class="text-end fs-11"><?= $res['AMOUNTPAID'] ?></td>
                                    <td class="text-end fs-11"><?= $res['EXPECTED_PAYMENT'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="row">
                    <div class="col-12 text-end">
                        <button class="btn btn-primary" type="submit">Submit</button>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>