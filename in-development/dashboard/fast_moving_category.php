<div class="accordion" id="fastmovingcategorycontainer">
  <div class="accordion-item">
    <h2 class="accordion-header" id="heading4"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fastmovingcategory" aria-expanded="true" aria-controls="fastmovingcategory">Fast Moving Category</button></h2>
    <div class="accordion-collapse collapse" id="fastmovingcategory" aria-labelledby="heading4" data-bs-parent="#accordionExample">
      <div class="accordion-body">
        <div class="accordion-header">
            <h6>Fast Moving Category</h6>
        </div>
        <div id="fast-cat-container">
            
            <div class="py-6 text-center">
                <!-- Load Button -->
                <button id="load-now-btn" class="btn btn-primary mb-3">
                    Load now
                </button>

                <!-- Loading Button (hidden initially) -->
                <button id="loading-btn" class="btn btn-primary mb-3" type="button" disabled style="display: none;">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Loading...
                </button>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>




<script>
$(document).ready(function () {
    $('#load-now-btn').click(function () {
        // Swap buttons
        $('#load-now-btn').hide();
        $('#loading-btn').show();

        // Load entire card
        $('#fast-cat-container').load('fast-cat.php?wh=<?php echo htmlspecialchars($dashboard_wh, ENT_QUOTES, 'UTF-8'); ?>', function (response, status, xhr) {
            if (status === "error") {
                $('#fast-cat-container').html("<div class='card-body text-center'><p>Error loading content. Please try again later.</p></div>");
                console.error("Error:", xhr.status, xhr.statusText);
            }
        });
    });
});
</script>
