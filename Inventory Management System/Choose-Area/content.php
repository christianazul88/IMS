<?php
$audit_id = $_SESSION['audit_id'];

// Fetch audit details
$audit_query = "SELECT 
                    al.*, w.warehouse_name 
                FROM audit_logs al 
                LEFT JOIN warehouse w 
                    ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci 
                WHERE al.id = ?";
$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$audit = $stmt->get_result()->fetch_assoc();
$stmt->close();

$today = date('Y-m-d');
$schedule_date = date('Y-m-d', strtotime($audit['schedule_date']));

if ($today < $schedule_date) {
    echo "<div class='alert alert-warning'>
            Audit is scheduled for " . date('M d, Y', strtotime($audit['schedule_date'])) . ". You cannot start it today.
          </div>";
    exit;
}

$warehouse_id_audit = $audit['warehouse'];
?>

<div class="card bg-primary text-white mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-10">
                <h5 class="card-title">Audit Dashboard</h5>
                <p class="card-text">Real-time monitoring for Audit #<?php echo $audit['audit_num']; ?> - <?php echo $audit['warehouse_name']; ?></p>
            </div>
        </div>
        
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">

        <!-- ✅ Header -->
        <div class="mb-3">
            <h4 class="card-title mb-1">Select Area to Scan</h4>
            <p class="text-muted mb-0">
                Choose the assigned warehouse area before starting the audit scanning process.
            </p>
        </div>

        <hr>

        <!-- ✅ Form -->
        <form action="../Scan/index.php" method="POST">

            <div class="mb-3">
                <label for="area" class="form-label fw-semibold">
                    Assigned Areas
                </label>

                <select name="area" id="area" class="form-select" required>
                    <option value="">Select Area</option>

                    <?php
                    $assignment_query = "SELECT
                                            il.location_name,
                                            aa.item_location
                                        FROM audit_assignment_staffs aas
                                        LEFT JOIN audit_assignments aa
                                        ON aas.audit_assignments_id = aa.id
                                        LEFT JOIN item_location il
                                        ON il.id = aa.item_location
                                        WHERE aas.user_id = '$user_id'
                                        AND (aas.status = 'idle' OR aas.status = 'rejected')";

                    $assignment_result = $conn->query($assignment_query);

                    while ($row = $assignment_result->fetch_assoc()) {
                        echo "<option value='" . $row['item_location'] . "'>"
                            . htmlspecialchars($row['location_name']) .
                            "</option>";
                    }
                    ?>

                    <option value="others">Others</option>
                </select>

                <div class="mt-3">
                    <input
                        type="text"
                        name="other_location"
                        id="other_location"
                        class="form-control"
                        placeholder="Enter new area/location"
                        disabled
                    >
                </div>

                <small class="text-muted">
                    Only pending assigned locations are shown.
                </small>
            </div>

            <!-- ✅ Submit Button -->
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    Start Scanning
                </button>
            </div>

        </form>

    </div>
</div>

<script>
document.getElementById('area').addEventListener('change', function () {
    const otherField = document.getElementById('other_location');

    if (this.value === 'others') {
        otherField.disabled = false;
        otherField.required = true;
    } else {
        otherField.disabled = true;
        otherField.required = false;
        otherField.value = '';
    }
});
</script>