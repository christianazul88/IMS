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
    // The JSON value is the last successfully assigned number. Rendering this
    // page must never reserve or advance a number.
    $json_file = __DIR__ . "/count.json";
    if (!file_exists($json_file)) {
        file_put_contents($json_file, json_encode([["number" => 0]], JSON_PRETTY_PRINT), LOCK_EX);
    }

    $data = json_decode((string) file_get_contents($json_file), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data) || !isset($data[0])) {
        throw new Exception("Invalid counter data in count.json");
    }

    $current = isset($data[0]['number']) ? (int) $data[0]['number'] : 0;
    $next_box_number = $current + 1;
} catch (Exception $e) {
    die("<pre style='color:red;font-weight:bold;'>JSON Counter Error: " . htmlspecialchars($e->getMessage()) . "</pre>");
}

$letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";



















if(isset($_SESSION['NEW_LOCATION_NAME'])){
?>
<div class="card border-warning shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-start">
            <div class="me-3">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-1"></i>
            </div>

            <div class="flex-grow-1">
                <h5 class="card-title text-warning fw-bold mb-2">
                    Unfinished Scan Detected
                </h5>

                <p class="card-text text-muted mb-3">
                    The system detected that you have an <strong>unfinished scanning session</strong>.
                    Please complete your current scan before starting a new one.
                </p>

                <ol class="text-muted mb-3 ps-3">
                    <li>Go back to the <strong>Audit Dashboard</strong>.</li>
                    <li>Select the item location you are currently scanning.<br>(on audit assignments select the idle)</li>
                    <li>Continue scanning until the audit is completed.</li>
                    <li>If you are not currently scanning, click this <a href="end.php">link</a> to start a new scanning session.</li>
                </ol>

                <a href="../audit-dashboard/" class="btn btn-warning">
                    <i class="bi bi-arrow-left-circle me-1"></i>
                    Return to Audit Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
<?php
} else {



?>

<?php if (!empty($_SESSION['choose_area_error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($_SESSION['choose_area_error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['choose_area_error']); ?>
    </div>
<?php endif; ?>

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
        <form action="start_scan.php" method="POST" id="choose-area-form">

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
                        id="box_num"
                        class="form-control"
                        value="<?php echo htmlspecialchars((string) $next_box_number, ENT_QUOTES, 'UTF-8'); ?>"
                        inputmode="numeric"
                        pattern="[0-9]+"
                        required>
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
const form = document.getElementById('choose-area-form');

form.addEventListener('submit', async function (event) {
    event.preventDefault();

    const areaCode = document.getElementById('area_code').value.trim();
    const rack = document.getElementById('rack').value.trim();
    const level = document.getElementById('level').value.trim();
    const boxNumber = document.getElementById('box_num').value.trim();

    if (!areaCode || !rack || !level || !/^\d+$/.test(boxNumber)) {
        form.reportValidity();
        return;
    }

    const locationName = `${areaCode}-${rack}-${level}-${boxNumber}`;
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;

    try {
        const response = await fetch('check_location.php?location_name=' + encodeURIComponent(locationName));
        const result = await response.json();

        if (!response.ok || !result.available) {
            alert(result.message || `${locationName} already exists. Please enter another number.`);
            return;
        }

        form.submit();
    } catch (error) {
        alert('Unable to verify the location name. Please try again.');
    } finally {
        submitButton.disabled = false;
    }
});
</script>
<?php
}