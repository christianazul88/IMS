<div class="text-center bg-white" id="stock-summary-container">
    <div id="stock-summary-spinner" class="spinner-border spinner-border-sm text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>
<div id="stock-summary"></div>


<script>
    $(document).ready(function () {
        // Show spinner
        $('#stock-summary-spinner').show();

        // Load the summary after 2 seconds (optional delay)
        setTimeout(function () {
            $('#stock-summary').load('stock-summary_jav.php?wh=<?php echo $dashboard_wh; ?>', function () {
                $('#stock-summary-spinner').hide();

                // Destroy all existing DataTables instances before reinitializing
                $('.data-table').each(function () {
                    if ($.fn.DataTable.isDataTable(this)) {
                        $(this).DataTable().destroy();
                    }
                });

                // Reinitialize
                $('.data-table').DataTable({
                    paging: false,
                    scrollY: '300px',
                    scrollCollapse: true
                });
            });

        }, 2000);
    });
</script>

