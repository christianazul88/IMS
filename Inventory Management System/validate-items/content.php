<?php
$audit_id = $_SESSION['audit_id'];
$selected_area = $_SESSION['area_code'];
$selected_area_name = $_SESSION['location_name'];
$audit_assignment_id = $_SESSION['audit_assignment_id'];

// Fetch audit details
$audit_query = "SELECT al.*, w.warehouse_name FROM audit_logs al LEFT JOIN warehouse w ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci WHERE al.id = ?";
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

<form id="audit-form" method="POST" action="save_audit_results.php">

    <div class="card border-0 shadow-sm mb-3">

        <div class="card-body">

            <div class="row align-items-center">

                <div class="col-lg-8">

                    <div class="d-flex align-items-center">

                        <div class="me-3">
                            <i class="fas fa-clipboard-check fa-2x text-primary"></i>
                        </div>

                        <div>
                            <h4 class="mb-0 fw-bold">
                                <?= htmlspecialchars($selected_area_name) ?>
                            </h4>

                            <small class="text-muted">
                                Audit Area
                            </small>
                        </div>

                    </div>

                </div>

                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#error-modal">

                        <i class="fas fa-map-marker-alt me-2"></i>
                        Update Location

                    </button>

                </div>

            </div>

        </div>

    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white">

            <div class="row align-items-center">

                <div class="col-md-6">
                    <h5 class="mb-0">
                        Items To Audit
                    </h5>
                </div>

                <div class="col-md-6">

                    <div class="input-group">

                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>

                        <input
                            type="text"
                            id="searchBarcode"
                            class="form-control"
                            placeholder="Search barcode, description, brand...">

                    </div>

                </div>

            </div>

        </div>

        <div class="card-body p-0">

            <div style="height:70vh; overflow-y:auto;">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light sticky-top">

                        <tr>
                            <th width="180">Barcode</th>
                            <th>Description / Brand</th>
                            <th>Category</th>
                            <th class="text-center">
                                Audit Result
                            </th>
                        </tr>

                    </thead>

                    <tbody id="auditTable" class="fs-11">

                    <?php

                    $items_to_audit_query = "
                        SELECT 
                            ia.unique_barcode,
                            p.description,
                            b.brand_name,
                            c.category_name
                        FROM items_to_audit ia
                        INNER JOIN stocks s
                            ON s.unique_barcode = ia.unique_barcode
                        LEFT JOIN product p
                            ON p.hashed_id = s.product_id
                        LEFT JOIN brand b
                            ON b.hashed_id = p.brand
                        LEFT JOIN category c
                            ON c.hashed_id = p.category
                        WHERE ia.item_location_origin = '$selected_area'
                        AND ia.audit_id = '$audit_id'
                        AND ia.audit_status = 'pending'
                        ORDER BY ia.unique_barcode ASC
                    ";

                    $ia_res = $conn->query($items_to_audit_query);

                    while($row = $ia_res->fetch_assoc()) {

                        $barcode = $row['unique_barcode'];
                        $description = $row['description'];
                        $brand = $row['brand_name'];
                        $category = $row['category_name'];

                        $hash = md5($barcode);
                    ?>

                        <tr class="fs-11">

                            <td class="fs-11">

                                <span class="badge bg-dark fs-11">
                                    <?= htmlspecialchars($barcode) ?>
                                </span>

                                <input
                                    type="hidden"
                                    name="barcode[]"
                                    value="<?= htmlspecialchars($barcode) ?>">

                            </td class="fs-11">

                            <td class="fs-11">

                                <div class="fw-semibold">
                                    <?= htmlspecialchars($description) ?>
                                </div>

                                <small class="text-muted">
                                    <?= htmlspecialchars($brand) ?>
                                </small>

                            </td>
                            
                            <td>
                                <?= htmlspecialchars($category) ?>
                            </td>

                            <td class="text-center">

                                <div class="btn-group" role="group">

                                    <input
                                        type="radio"
                                        class="btn-check fs-11"
                                        name="status[<?= htmlspecialchars($barcode) ?>]"
                                        value="scanned"
                                        checked
                                        id="scanned_<?= $hash ?>">

                                    <label
                                        class="btn btn-outline-success fs-11"
                                        for="scanned_<?= $hash ?>">

                                        <i class="fas fa-check me-1"></i>
                                        Scanned

                                    </label>

                                    <input
                                        type="radio"
                                        class="btn-check fs-11"
                                        name="status[<?= htmlspecialchars($barcode) ?>]"
                                        value="missing"
                                        id="missing_<?= $hash ?>">

                                    <label
                                        class="btn btn-outline-danger fs-11"
                                        for="missing_<?= $hash ?>">

                                        <i class="fas fa-times me-1"></i>
                                        Missing

                                    </label>

                                </div>

                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="card-footer bg-white sticky-bottom">

            <div class="d-flex justify-content-between align-items-center">

                <small class="text-muted">
                    Review missing items before submitting.
                </small>

                <button
                    type="submit"
                    class="btn btn-success btn-lg fs-9">

                    <i class="fas fa-save me-2"></i>
                    Submit Audit

                </button>

            </div>

        </div>

    </div>

</form>



<div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
    <div class="modal-content position-relative">
        <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="update-location-form" method="POST" action="update_location.php">
        <div class="modal-body p-0">
            <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
            <h4 class="mb-1" id="modalExampleDemoLabel">Update Location/Area Name of <?php echo htmlspecialchars($selected_area_name); ?></h4>
            </div>
            <div class="p-4 pb-0">
                <div class="mb-3">
                    <label class="col-form-label" for="recipient-name">Location/ Area Name:</label>
                    <input class="form-control"
                    id="location-name"
                    name="location_name"
                    type="text"
                    value="<?php echo htmlspecialchars($selected_area_name); ?>" />
                    <input type="hidden" name="location_id" value="<?= $selected_area ?>">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-primary" type="submit">Update Location</button>
        </div>
        
        </form>
    </div>
  </div>
</div>

<script>
document.getElementById('update-location-form').addEventListener('submit', async function(e) {

    e.preventDefault();

    const form = this;
    const formData = new FormData(form);

    const oldLocation = <?php echo json_encode($selected_area_name); ?>;
    const newLocation = document.getElementById('location-name').value.trim();

    if (!newLocation) {
        Swal.fire({
            icon: 'warning',
            title: 'Location name required',
            text: 'Please enter a location name.'
        });
        return;
    }

    // First confirmation
    const firstConfirm = await Swal.fire({
        title: 'Update Location?',
        html: `
            You are about to rename:<br><br>
            <b>${oldLocation}</b><br>
            to<br>
            <b>${newLocation}</b>
            <br><br>
            This may affect audit records and staff assignments that reference this location.
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Update Location',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
    });

    if (!firstConfirm.isConfirmed) {
        return;
    }

    // Second confirmation
    const secondConfirm = await Swal.fire({
        title: 'Are you absolutely sure?',
        html: `
            The location name <b>${oldLocation}</b> will be changed to
            <b>${newLocation}</b>.
            <br><br>
            Please confirm this is the correct location name.
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Save Changes',
        cancelButtonText: 'No'
    });

    if (!secondConfirm.isConfirmed) {
        return;
    }

    Swal.fire({
        title: 'Updating Location...',
        text: 'Please wait.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {

        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });

        const result = await response.text();

        Swal.fire({
            icon: response.ok ? 'success' : 'error',
            title: response.ok ? 'Location Updated' : 'Update Failed',
            html: result
        });

        // Optional: update title in modal
        document.querySelector('#modalExampleDemoLabel').innerHTML =
            `Update Location/Area Name of ${newLocation}`;

    } catch (error) {

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Unable to communicate with the server.'
        });

        console.error(error);
    }

});
</script>

<script>
document.getElementById('searchBarcode').addEventListener('keyup', function () {

    const value = this.value.toLowerCase();

    document.querySelectorAll('#auditTable tr').forEach(row => {

        const text = row.textContent.toLowerCase();

        row.style.display =
            text.includes(value) ? '' : 'none';

    });

});
</script>