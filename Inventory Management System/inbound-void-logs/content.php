<style>
    .card{
        transition:.2s;
    }

    .card:hover{
        box-shadow:0 .5rem 1rem rgba(0,0,0,.08)!important;
    }

    .table thead th{
        font-size:.82rem;
        text-transform:uppercase;
        letter-spacing:.05em;
        white-space:nowrap;
    }

    .table tbody td{
        vertical-align:middle;
    }

    .table tbody tr{
        transition:.15s;
    }

    .table tbody tr:hover{
        background:#f8f9fa;
    }

    .badge{
        font-weight:500;
        border-radius:20px;
    }

    code{
        font-size:.85rem;
        color:#0d6efd;
    }
</style>
<div class="card shadow-sm border-0 rounded-3">

    <!-- Header -->
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0 fw-bold">
                <i class="fas fa-history text-primary me-2"></i>
                Inbound Void Logs
            </h4>
            <small class="text-muted">
                History of all inbound void requests.
            </small>
        </div>

        <span class="badge bg-primary fs-10">
            <?php echo $result->num_rows; ?> Records
        </span>
    </div>

    <!-- Body -->
    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0 table-sm" id="inbound-void-logs-table">

                <thead class="table-light">
                    <tr>
                        <th width="70">Ref #</th>
                        <th width="120">PO #</th>
                        <th>Remarks</th>
                        <th width="160">Inbound Ref #</th>
                        <th width="180">Requested By</th>
                        <th width="120">Status</th>
                        <th width="180">Requested Date</th>
                        <th width="100" class="text-center">Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                // Fetch inbound void logs from the database
                $stmt = $conn->prepare("SELECT 
                                            vl.id, 
                                            vl.po_id, 
                                            vl.unique_key, 
                                            vl.requested_by, 
                                            vl.status, 
                                            vl.created_at,
                                            vl.remarks,
                                            u.user_fname,
                                            u.user_lname 
                                        FROM void_logs vl
                                        LEFT JOIN users u ON vl.requested_by = u.hashed_id
                                        WHERE vl.request_type = 'void' 
                                        ORDER BY vl.created_at DESC");
                $stmt->execute();
                $result = $stmt->get_result();

                if($result->num_rows > 0){

                    while ($row = $result->fetch_assoc()) {

                        switch(strtolower($row['status'])){

                            case "pending":
                                $status = '<span class="badge bg-warning text-dark px-3 py-2 fs-11">Pending</span>';
                                break;

                            case "approved":
                                $status = '<span class="badge bg-success px-3 py-2 fs-11">Approved</span>';
                                break;

                            case "rejected":
                                $status = '<span class="badge bg-danger px-3 py-2 fs-11">Rejected</span>';
                                break;

                            default:
                                $status = '<span class="badge bg-secondary px-3 py-2 fs-11">'.htmlspecialchars($row['status']).'</span>';
                        }

                        echo "
                        <tr>

                            <td class='fw-semibold'>#".htmlspecialchars($row['id'])."</td>

                            <td>
                                <span class='fw-semibold text-primary'>
                                    ".htmlspecialchars($row['po_id'])."
                                </span>
                            </td>

                            <td>".nl2br(htmlspecialchars($row['remarks']))."</td>

                            <td>
                                <code>".htmlspecialchars($row['unique_key'])."</code>
                            </td>

                            <td>
                                <div class='fw-semibold'>
                                    ".htmlspecialchars($row['user_fname']." ".$row['user_lname'])."
                                </div>
                            </td>

                            <td>$status</td>

                            <td class='text-muted'>
                                ".date("M d, Y h:i A", strtotime($row['created_at']))."
                            </td>

                            <td class='text-center'>

                                <a href='../inbound-void2/?unique_key=" . urlencode($row['unique_key']) . "&po_no=" . urlencode($row['po_id']) . "'
                                class='btn btn-sm btn-outline-primary fs-11'
                                title='View Details'>

                                    <i class='fas fa-eye me-1'></i> View

                                </a>

                            </td>

                        </tr>";
                    }

                }else{

                    echo '
                    <tr>
                        <td colspan="7">
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-secondary mb-3"></i>
                                <h5 class="mb-1">No Void Logs Found</h5>
                                <p class="text-muted mb-0">
                                    There are currently no inbound void requests.
                                </p>
                            </div>
                        </td>
                    </tr>';
                }

                ?>

                </tbody>

            </table>

        </div>

    </div>

</div>