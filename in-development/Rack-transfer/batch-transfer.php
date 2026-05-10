<form id="batch" action="../config/batch-rack-transfer.php">
    <div class="row">
        <div class="col-12 mb-3">
            <input type="text" class="form-control" name="parent-barcode" id="parent-barcode" placeholder="Enter Parent Barcode...">
        </div>
        <div class="col-6 mb-3">
            <label for="from">From sequence</label>
            <input min="0" type="number" class="form-control" name="start-sequence">
        </div>
        <div class="col-6 mb-3">
            <label for="to">to sequence</label>
            <input min="2" type="number" class="form-control" name="last-sequence">
        </div>
        <button class="col-12 btn btn-primary">Submit</button>
        
    </div>
</form>