<div class="text-center bg-white" id="outbound-008-container">
    <div id="outbound-008-spinner" class="spinner-border spinner-border-sm text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>
<div id="outbound-008"></div>

<script>
    $(document).ready(function () {
        // Show spinner on page load
        $('#outbound-008-spinner').show();

        // Wait 2 seconds, then load content
        setTimeout(function () {
            $('#outbound-008').load('outbound-008.php?wh=<?php echo $dashboard_wh; ?>', function () {
                // Hide spinner after content loads
                $('#outbound-008-spinner').hide();
            });
        }, 2000);
    });
</script>

