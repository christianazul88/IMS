<?php 
// Unset the session variable if it exists
if (isset($_SESSION['Stock_id'])) {
    unset($_SESSION['Stock_id']);
}
?>

<div class="card">
  <div class="card-header bg-success">
    <h2 class="text-dark">Stock Transfer Logs</h2>
  </div>
  <div class="card-body overflow-hidden py-6 px-0">
    <div id="tableExample4" data-list='{"valueNames":["Stock_no","batch","status","from_wh","from_name","date_sent","remarks_sender","to_wh","date_rec","receiver_name","remarks_rec"]}'>
     
      

      <!-- Table Section -->
      <div class="table-responsive scrollbar px-1">
        <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
          <thead class="bg-200">
            <tr>
              <th></th>
              <th>Transfer ID</th>
              <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="batch">Details</th>
              <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="status">Fulfillment Status</th>
              <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="from_wh">From</th>
              <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="from_name">Sent By</th>
              <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="date_sent">Date Sent</th>
              <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="remarks_sender">Sender Remarks</th>
              <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="to_wh">To</th>
              <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="date_rec">Date Received</th>
              <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="receiver_name">Received By</th>
              <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="remarks_rec">Receiver Remarks</th>
            </tr>
          </thead>
          <tbody class="list" id="table-purchase-body">
            <?php 
            // Prepare warehouse IDs for SQL query
            $quoted_warehouse_ids = array_map(fn($id) => "'" . trim($id) . "'", $user_warehouse_ids);
            $imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);

            $Stock_sql = "
              SELECT 
                st.*, 
                fw.warehouse_name AS from_warehouse_name, 
                tw.warehouse_name AS to_warehouse_name, 
                CONCAT(fu.user_fname, ' ', fu.user_lname) AS from_fullname, 
                CONCAT(ru.user_fname, ' ', ru.user_lname) AS receiver_fullname
              FROM 
                stock_transfer st
              INNER JOIN stock_transfer_content stc ON stc.st_id = st.id
              LEFT JOIN warehouse fw ON st.from_warehouse = fw.hashed_id
              LEFT JOIN warehouse tw ON st.to_warehouse = tw.hashed_id
              LEFT JOIN users fu ON st.from_userid = fu.hashed_id
              LEFT JOIN users ru ON st.received_userid = ru.hashed_id
              WHERE 
                st.from_warehouse IN ($imploded_warehouse_ids) 
                OR st.to_warehouse IN ($imploded_warehouse_ids)
              GROUP BY st.id
              ORDER BY st.id DESC";


            $Stock_res = $conn->query($Stock_sql);

            if ($Stock_res->num_rows > 0) {
              while ($row = $Stock_res->fetch_assoc()) {
                $transfer_id = $row['id'];
                $batch_codes = [];

                // Fetch batch codes for the transfer
                $transfer_contents_sql = "
                  SELECT s.batch_code 
                  FROM stock_transfer_content tc
                  LEFT JOIN stocks s ON tc.unique_barcode = s.unique_barcode
                  WHERE tc.st_id = '$transfer_id'
                  ORDER BY s.batch_code DESC";

                $content_res = $conn->query($transfer_contents_sql);
                while ($content_row = $content_res->fetch_assoc()) {
                  $batch_codes[] = $content_row['batch_code'];
                }

                $imploded_batch_codes = implode(", ", $batch_codes);
                $truncatedText = strlen($imploded_batch_codes) > 50 ? substr($imploded_batch_codes, 0, 50) . "..." : $imploded_batch_codes;

                $status_badge = match ($row['status']) {
                  "pending" => '<span class="badge bg-primary">Pending</span>',
                  "enroute" => '<span class="badge bg-warning">Enroute</span>',
                  "received" => '<span class="badge bg-success">Received</span>',
                  default => '<span class="badge bg-danger">Failed</span>',
                };

                if($row['status']==="pending"){
                  $tr_type = "bg-danger";
                  $td = "text-white";
                } else {
                  $tr_type = "";
                  $td = "";
                }
            ?>
            <tr class="btn-reveal-trigger <?php echo $tr_type;?>">
              <td>
                <button 
                  class="btn btn-transparent <?php echo $td;?> view-button" 
                  type="button" 
                  data-bs-toggle="modal" 
                  data-bs-target="#tr" 
                  data-id="<?php echo $row['id']; ?>">
                  <span class="fas fa-eye"></span>
                </button>
              </td>
              <td><?php echo 10000 + $transfer_id;?></td>
              <td class="align-middle <?php echo $td;?> white-space-nowrap batch">
                  <?php echo $truncatedText; ?>
              </td>
              <td class="align-middle white-space-nowrap text-center status"><?php echo $status_badge; ?></td>
              <td class="align-middle <?php echo $td;?> white-space-nowrap from_wh"><?php echo $row['from_warehouse_name']; ?></td>
              <td class="align-middle <?php echo $td;?> white-space-nowrap from_name"><?php echo $row['from_fullname']; ?></td>
              <td class="align-middle <?php echo $td;?> white-space-nowrap date_out"><?php echo $row['date_out']; ?></td>
              <td class="align-middle <?php echo $td;?> white-space-nowrap remarks_sender"><?php echo $row['remarks_sender']; ?></td>
              <td class="align-middle <?php echo $td;?> white-space-nowrap to_wh"><?php echo $row['to_warehouse_name']; ?></td>
              <td class="align-middle <?php echo $td;?> white-space-nowrap date_rec"><?php echo $row['date_received']; ?></td>
              <td class="align-middle <?php echo $td;?> white-space-nowrap receiver_name"><?php echo $row['receiver_fullname']; ?></td>
              <td class="align-middle <?php echo $td;?> white-space-nowrap remarks_rec"><?php echo $row['remarks_receiver']; ?></td>
            </tr>
            <?php 
              }
            } else {
            ?>
            <tr class="text-center">
              <td class="py-6" colspan="10">
                <h4>No Data yet</h4>
              </td>
            </tr>
            <?php 
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="tr" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">

      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
  // Select all buttons with the 'view-button' class
  const viewButtons = document.querySelectorAll(".view-button");

  viewButtons.forEach((button) => {
    button.addEventListener("click", function () {
      // Get the row ID from the data-id attribute
      const rowId = this.getAttribute("data-id");

      // Build the URL for the modal content
      const modalUrl = `modal-content.php?id=${rowId}`;

      // Select the modal body and update its content using AJAX
      const modalBody = document.querySelector("#tr .modal-body");
      
      // Show a loading spinner or placeholder while loading content
      modalBody.innerHTML = '<div class="text-center p-3"><span class="spinner-border"></span> Loading...</div>';

      // Fetch the modal content
      fetch(modalUrl)
        .then(response => response.text())
        .then(html => {
          modalBody.innerHTML = html; // Insert the fetched content
        })
        .catch(error => {
          console.error("Error loading modal content:", error);
          modalBody.innerHTML = '<div class="text-center p-3">An error occurred. Please try again.</div>';
        });
    });
  });
});

</script>