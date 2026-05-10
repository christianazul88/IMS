<?php 
$rowz = [];

// Updated query: count logs and basic user/warehouse info
$staff_query = "SELECT u.*, 
    COUNT(CASE WHEN l.title = 'INBOUND DELETE' THEN 1 END) AS inbound_deleted, 
    COUNT(CASE WHEN l.title = 'OUTBOUND VOID' THEN 1 END) AS voided_outbound 
    FROM users u 
    LEFT JOIN logs l ON l.user_id = u.hashed_id 
    GROUP BY u.hashed_id";

$staff_result = $conn->query($staff_query);

if ($staff_result && $staff_result->num_rows > 0) {
    while ($row = $staff_result->fetch_assoc()) {
        // Profile image fallback
        if(empty($row['pfp'])){
            $staff_pfp = 'def_pfp.png';
        } else {
            $staff_pfp = basename($row['pfp']);
        }

        // Full name
        $staff_name = $row['user_fname'] . " " . $row['user_lname'];

        // Process warehouse_access
        $access_ids = explode(',', $row['warehouse_access']);
        $access_ids_quoted = array_map(function($id) use ($conn) {
            return "'" . $conn->real_escape_string(trim($id)) . "'";
        }, $access_ids);

        $staff_warehouse = '';
        if (!empty($access_ids_quoted)) {
            $id_list = implode(',', $access_ids_quoted);
            $w_query = "SELECT warehouse_name FROM warehouse WHERE hashed_id IN ($id_list)";
            $w_result = $conn->query($w_query);

            $warehouse_badges = [];
            if ($w_result && $w_result->num_rows > 0) {
                while ($w_row = $w_result->fetch_assoc()) {
                    $warehouse_badges[] = '<span class="badge badge-subtle-secondary">' . htmlspecialchars($w_row['warehouse_name']) . '</span>';
                }
            }

            $staff_warehouse = implode(' ', $warehouse_badges);
        }

        // Counts
        $inbound_delete = $row['inbound_deleted'];
        $outbound_void = $row['voided_outbound'];

        // Store for display
        $rowz[] = '
        <tr>
            <td><img class="img" style="height: 30px;" src="../../assets/img/' . htmlspecialchars($staff_pfp) . '" alt=""></td>
            <td>' . htmlspecialchars($staff_name) . '</td>
            <td>' . $staff_warehouse . '</td>
            <td>' . $inbound_delete . '</td>
            <td>' . $outbound_void . '</td>
        </tr>';
    }
}

if (count($rowz) > 0) {
?>
<div class="card">
    <div class="card-header">
        <h3>Staff Error Log Summary</h3>
    </div>
    <div class="card-body overflow-hidden">
        <div class="row g-2">
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Fullname</th>
                                <th>Warehouse</th>
                                <th>Inbound Deleted</th>
                                <th>Voided Outbound</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach($rowz as $row){
                                echo $row;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}
?>
