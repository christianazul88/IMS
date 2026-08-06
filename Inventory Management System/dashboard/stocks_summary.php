<div class="accordion" id="stock-summary-container">
  <div class="accordion-item">
    <h2 class="accordion-header" id="stock-summary-heading">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#stock-summary-collapse" aria-expanded="false" aria-controls="stock-summary-collapse">
        Stock Summary
      </button>
    </h2>
    <div id="stock-summary-collapse" class="accordion-collapse collapse" aria-labelledby="stock-summary-heading">
      <div class="accordion-body">
        <div id="stock-summary" class="text-center py-3" aria-live="polite">
          <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
          <span class="ms-2">Loading stock summary…</span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('stock-summary-collapse').addEventListener('shown.bs.collapse', function () {
    const summary = document.getElementById('stock-summary');

    // Keep the result available while the panel is reopened during this visit.
    if (summary.dataset.loaded === 'true' || summary.dataset.loading === 'true') {
        return;
    }

    summary.dataset.loading = 'true';
    fetch('stock-summary_jav.php?wh=<?php echo rawurlencode($dashboard_wh); ?>')
        .then(function (response) {
            if (!response.ok) throw new Error('Request failed');
            return response.text();
        })
        .then(function (html) {
            summary.innerHTML = html;
            summary.dataset.loaded = 'true';

            // The loaded table can use the dashboard's existing DataTables setup.
            if (window.jQuery && jQuery.fn.DataTable) {
                jQuery('#stock-summary .data-table').DataTable({
                    paging: false,
                    scrollY: '500px',
                    scrollCollapse: true
                });
            }
        })
        .catch(function () {
            summary.innerHTML = '<div class="text-danger">Unable to load the stock summary. Please try again.</div>';
        })
        .finally(function () {
            delete summary.dataset.loading;
        });
});
</script>
