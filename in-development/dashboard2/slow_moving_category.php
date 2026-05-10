<div class="card h-100" id="slow-cat-container">
    <div class="card-header">
        <h6>Top 10 Slow Moving Category</h6>
    </div>
    <div class="card-body text-center py-6">
        <!-- Load Button -->
        <button id="load-slow-cat-btn" class="btn btn-secondary mb-3">
            Load now
        </button>

        <!-- Loading Button (hidden initially) -->
        <button id="loading-slow-cat-btn" class="btn btn-secondary mb-3" type="button" disabled style="display: none;">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Loading...
        </button>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#load-slow-cat-btn').click(function () {
        $('#load-slow-cat-btn').hide();
        $('#loading-slow-cat-btn').show();

        $('#slow-cat-container').load('slow-cat.php?wh=<?php echo htmlspecialchars($dashboard_wh, ENT_QUOTES, "UTF-8"); ?>', function (response, status, xhr) {
            if (status === "error") {
                $('#slow-cat-container').html("<div class='card-body text-center'><p>Error loading content. Please try again later.</p></div>");
                console.error("Error:", xhr.status, xhr.statusText);
            }
        });
    });
});
</script>
