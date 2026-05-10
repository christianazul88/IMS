<div class="card">
    <div class="card-header">
        <h2>OUTBOUND FIX</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-10">
                <form id="csvForm" enctype="multipart/form-data">
                    <label for="csv">Upload CSV:</label>
                    <input type="file" name="csv" id="csv" class="form-control" accept=".csv">
                    <button type="submit" class="btn btn-primary mt-2">Upload</button>
                </form>
            </div>
            <form action="process_update.php" method="post">
            <div class="col-12 mt-3">
                <div class="table-responsive">
                    <div id="contents"></div>
                </div>
            </div>
            <div class="col-l2 mt-3 text-end">
                <button class="btn btn-primary" type="submit">Submit</button>
            </div>
            </form>
        </div>
    </div>
</div>


<script>
$('#csvForm').on('submit', function (e) {
    e.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: 'process_csv.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
            $('#contents').html(response);
        },
        error: function () {
            alert('Failed to upload file.');
        }
    });
});
</script>
