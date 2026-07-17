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


try {
    // --------------------------JSON WRITE-------------
    $json_file = "count.json";

    // Create file if it doesn't exist
    if (!file_exists($json_file)) {
        if (file_put_contents($json_file, json_encode([
            ["number" => 0]
        ], JSON_PRETTY_PRINT)) === false) {
            throw new Exception("Failed to create {$json_file}");
        }
    }

    // Open the file
    $fp = @fopen($json_file, "c+");

    if (!$fp) {
        $error = error_get_last();
        die("<pre>" . print_r($error, true) . "</pre>");
    }
    if (!$fp) {
        throw new Exception("Unable to open {$json_file}");
    }

    // Lock the file
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new Exception("Unable to lock {$json_file}");
    }

    // Read current contents
    rewind($fp);
    $json = stream_get_contents($fp);

    $data = json_decode($json, true);

    // Check JSON validity
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON Decode Error: " . json_last_error_msg());
    }

    // Initialize if empty
    if (!is_array($data) || !isset($data[0])) {
        $data = [
            ["number" => 0]
        ];
    }

    $current = isset($data[0]['number']) ? (int)$data[0]['number'] : 0;

    // Increment immediately
    $current++;
    $data[0]['number'] = $current;

    // Write back
    rewind($fp);
    ftruncate($fp, 0);

    if (fwrite($fp, json_encode($data, JSON_PRETTY_PRINT)) === false) {
        throw new Exception("Failed to write to {$json_file}");
    }

    fflush($fp);

    // Release the lock
    flock($fp, LOCK_UN);
    fclose($fp);

} catch (Exception $e) {
    if (isset($fp) && is_resource($fp)) {
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    die("<pre style='color:red;font-weight:bold;'>
JSON Counter Error:
" . $e->getMessage() . "
</pre>");
}


$letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";























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

                <select name="area" id="area" class="form-select d-none" required>
                    <option value="">Select Area</option>

                    

                    <option value="others" selected>Others</option>
                </select>

                <div class="row mt-3">
                    <div class="col-3">
                        <select name="area_code" class="form-select" id="area_code" required>
                            <option value="">Select area</option>
                            <option value="UP">UP</option>
                            <option value="BR">BR</option>
                            <option value="JP">JP</option>
                            <option value="AG">AG</option>
                            <option value="OG">OG</option>
                        </select>
                    </div>
                    <div class="col-3">
                        <select name="rack" class="form-select" id="rack" required>
                            <option value="">Select rack</option>
                            <option value="NA">NA</option>
                            <option value="R1">R1</option>
                            <option value="R2">R2</option>
                            <option value="R3">R3</option>
                            <option value="R4">R4</option>
                            <option value="R5">R5</option>
                            <option value="R6">R6</option>
                            <option value="R7">R7</option>
                            <option value="R8">R8</option>
                            <option value="R9">R9</option>
                            <option value="R10">R10</option>
                            <option value="R11">R11</option>
                            <option value="R12">R12</option>
                            <option value="WS1">WS1</option>
                            <option value="WS2">WS2</option>
                            <option value="WS3">WS3</option>
                            <option value="WS4">WS4</option>
                            <option value="WS5">WS5</option>
                            <option value="WS6">WS6</option>
                        </select>
                    </div>
                    <div class="col-3">
                        <select name="level" class="form-select" id="level" required>
                            <option value="">Select level</option>
                            <option value="NA">NA</option>
                            <option value="L1">L1</option>
                            <option value="L2">L2</option>
                            <option value="L3">L3</option>
                            <option value="L4">L4</option>
                            <option value="L5">L5</option>
                            <option value="L6">L6</option>
                            <option value="L7">L7</option>
                            <option value="L8">L8</option>
                            <option value="L9">L9</option>
                            <option value="L10">L10</option>
                            <option value="L11">L11</option>
                            <option value="L12">L12</option>
                            <option value="L13">L13</option>
                            <option value="L14">L14</option>
                            <option value="L15">L15</option>
                            <option value="L16">L16</option>
                            <option value="L17">L17</option>
                        </select>
                    </div>
                    <div class="col-3">
                        <input
                        type="text"
                        name="box_num"
                        class="form-control readonly"
                        value="<?php echo $current; ?>"
                        readonly>
                    </div>

                    
                    <!-- <input
                        type="text"
                        name="other_location"
                        id="other_location"
                        class="form-control"
                        placeholder="Enter new area/location"
                        disabled
                    > -->
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