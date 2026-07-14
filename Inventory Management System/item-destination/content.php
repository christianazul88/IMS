<?php 
$warehouse_selected = "All";
$warehouse_selected_name = "All";
// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $warehouse_selected = $_POST['warehouse'];

    $warehouse_query = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_selected' LIMIT 1";
    $warehouse_Res = $conn->query($warehouse_query);
    if($warehouse_Res->num_rows>0){
        $row=$warehouse_Res->fetch_assoc();
        $warehouse_selected_name = $row['warehouse_name'];
    } 
}
?>
<div class="card shadow-sm border-0">

    <!-- Header -->
    <div class="card-header bg-white py-3">
        <div class="row align-items-center">

            <div class="col-lg-6 mb-3 mb-lg-0">
                <h4 class="mb-1 fw-bold">
                    <i class="bi bi-geo-alt-fill text-primary"></i>
                    Item Destination / Location
                </h4>

                <small class="text-muted mb-3">
                    Warehouse:
                    <span class="badge bg-primary">
                        <?php echo $warehouse_selected_name; ?>
                    </span>
                </small>


            </div>

            <div class="col-lg-6">

                <form action="../item-destination/" method="POST" id="warehouseForm">
                    <div class="row">
                        <div class="col-3 text-end">
                            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#warehouse_modal">
                                                <span class="fas fa-plus"></span> New
                                            </button>
                        </div>

                        <div class="col-9">

                        

                                <select
                                    class="form-select"
                                    name="warehouse"
                                    id="warehouseSelect">
                                    <?php 
                                    if($warehouse_selected_name === "All"){
                                    ?>
                                    <option value="">Select Warehouse</option>
                                    <?php 
                                    } else {
                                    ?>
                                    <option value="" selected><?php echo $warehouse_selected_name;?></option>
                                    <?php
                                    }
                                    ?>

                                    <?php echo implode($warehouse_options2); ?>

                                </select>

                        </div>
                    </div>

                    

                </form>

            </div>

        </div>
    </div>

    <!-- Body -->
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h6 class="mb-0">
                <i class="bi bi-list-ul"></i>
                Location List
            </h6>

            <input
                type="text"
                id="searchLocation"
                class="form-control w-auto"
                placeholder="Search location...">

        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle" id="locationTable">

                <thead class="table-light">

                    <tr>
                        <th width="45%">Location / Area</th>
                        <th width="35%">Warehouse</th>
                        <th width="20%" class="text-center">Action</th>
                    </tr>

                </thead>

                <tbody>

<?php

if(isset($warehouse_selected) && $warehouse_selected !== "All"){
    $additional_query = "WHERE il.warehouse='$warehouse_selected'";
}else{
    $additional_query = "";
}

$item_location_query = "
SELECT
    il.*,
    w.warehouse_name,
    w.hashed_id AS warehouse_id
FROM item_location il
LEFT JOIN warehouse w
ON w.hashed_id=il.warehouse
$additional_query
ORDER BY il.location_name ASC
";

$item_location_res=$conn->query($item_location_query);

if($item_location_res->num_rows>0){

while($row=$item_location_res->fetch_assoc()){

?>

<tr>

    <td>

        <div class="fw-semibold">
            <?php echo htmlspecialchars($row['location_name']); ?>
        </div>

        

    </td>

    <td>

        <span class="badge bg-secondary">
            <?php echo htmlspecialchars($row['warehouse_name']); ?>
        </span>

    </td>

    <td class="text-center">

        <button
            class="btn btn-sm btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#updateLocationModal"

            data-id="<?php echo $row['id']; ?>"

            data-location="<?php echo htmlspecialchars($row['location_name']); ?>"

            data-warehouse="<?php echo $row['warehouse']; ?>">

            <i class="bi bi-pencil-square"></i>
            Update

        </button>

    </td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="3" class="text-center py-5">

<i class="bi bi-inbox fs-1 text-muted"></i>

<p class="mt-2 text-muted">
No locations found.
</p>

</td>

</tr>

<?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<div class="modal fade" id="updateLocationModal" tabindex="-1">

    <div class="modal-dialog">

        <form action="../config/update-admin.php?type=location" method="POST">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Update Location
                    </h5>

                    <button
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="id"
                        id="modal_location_id">

                    <div class="mb-3">

                        <label class="form-label d-none">
                            Warehouse
                        </label>

                        <select
                            class="form-select d-none"
                            name="warehouse"
                            id="modal_warehouse">

                            <?php echo implode($warehouse_options2); ?>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Location Name
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="location_name"
                            id="modal_location_name"
                            required>

                        

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                        type="button">

                        Cancel

                    </button>

                    <button
                        class="btn btn-primary"
                        type="submit">

                        <i class="bi bi-check-circle"></i>

                        Save Changes

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>







<!-- Modal for Adding New Item Location -->
    <div class="modal fade" id="warehouse_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <form action="../config/add-item_location.php" method="POST">
            <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
                <div class="modal-content position-relative">
                    <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                            <h4 class="mb-1" id="modalExampleDemoLabel">Add a New Item Location to</h4>
                        </div>
                        <div class="p-4 pb-0">
                            <div class="mb-3">
                                <label class="col-form-label" for="item_location-name">Warehouse:</label>
                                <select class="form-select" name="warehouse" id="">
                                    <option value="">Select Warehouse</option>
                                    <?php 
                                    echo implode($warehouse_options2);
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="p-4 pb-0">
                            <div class="mb-3">
                                <label class="col-form-label" for="item_location-name">Item Location Name:</label>
                                <input class="form-control" id="item_location-name" name="item_location_name" type="text" />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary" type="submit">Submit</button>
                    </div>
                </div>
            </div>
        </form>
    </div>



<script>
    // Warehouse selection
document.getElementById("warehouseSelect").addEventListener("change", function () {

    if (this.value !== "") {
        document.getElementById("warehouseForm").submit();
    }

});

// Populate modal
const updateModal = document.getElementById('updateLocationModal');

updateModal.addEventListener('show.bs.modal', function (event) {

    const button = event.relatedTarget;

    document.getElementById('modal_location_id').value =
        button.getAttribute('data-id');

    document.getElementById('modal_location_name').value =
        button.getAttribute('data-location');

    document.getElementById('modal_warehouse').value =
        button.getAttribute('data-warehouse');

});

// Search table
document.getElementById("searchLocation").addEventListener("keyup", function(){

    let value = this.value.toLowerCase();

    document.querySelectorAll("#locationTable tbody tr").forEach(function(row){

        row.style.display = row.innerText.toLowerCase().includes(value)
            ? ""
            : "none";

    });

});
</script>