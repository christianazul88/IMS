<div class="accordion" id="incoming-stocks-accordion">
  <div class="accordion-item">
    <h2 class="accordion-header" id="incoming-stocks-heading"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#incoming-stocks-collapse" aria-expanded="false" aria-controls="incoming-stocks-collapse">Incoming Stocks</button></h2>
    <div class="accordion-collapse collapse" id="incoming-stocks-collapse" aria-labelledby="incoming-stocks-heading">
      <div class="accordion-body"><div id="incoming-stocks-content" class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary" role="status"></span><span class="ms-2">Loading incoming stocks…</span></div></div>
    </div>
  </div>
</div>
<script>
document.getElementById('incoming-stocks-collapse').addEventListener('shown.bs.collapse', function () {
  const target = document.getElementById('incoming-stocks-content');
  if (target.dataset.loaded || target.dataset.loading) return;
  target.dataset.loading = 'true';
  fetch('incoming_stocks_data.php?wh=<?php echo rawurlencode($dashboard_wh); ?>')
    .then(response => { if (!response.ok) throw new Error('Request failed'); return response.text(); })
    .then(html => { target.innerHTML = html; target.dataset.loaded = 'true'; })
    .catch(() => { target.innerHTML = '<div class="text-danger">Unable to load incoming stocks.</div>'; })
    .finally(() => { delete target.dataset.loading; });
});
</script>
