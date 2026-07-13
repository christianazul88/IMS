<form action="outbound-process.php" id="myForm" method="POST">
    <div class="row">
        <div class="col-8">
            <input type="text" name="barcode" id="barcode" class="form-control" placeholder="Barcode" required>
        </div>
        <!-- <div class="col-4">
            <input type="number" name="selling_price" class="form-control" placeholder="Selling Price" step="0.01" required>
        </div> -->
        <div class="col-2">
            <button class="btn btn-info" id="btn-submit" type="submit">Submit</button>
        </div>
        <div class="col-2">
            <a href="local_config.php?change_warehouse=true" class="btn btn-warning fs-11 mb-3">Change Warehouse</a>
            <button class="btn btn-light fs-11" type="button" data-bs-toggle="modal" data-bs-target="#error-modal">View Summary</button>
        </div>
    </div>
</form>
