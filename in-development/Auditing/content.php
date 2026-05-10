
<div class="row mb-3">
  <div class="col-12">
    <h3 class="mb-3">Auditing</h3>
  </div>

  <div class="col-12">
    <ul class="nav nav-tabs" id="myTab" role="tablist">
      <li class="nav-item"><a class="nav-link active" id="transact-tab" data-bs-toggle="tab" href="#tab-transact" role="tab" aria-controls="tab-transact" aria-selected="true">Transaction</a></li>
      <li class="nav-item"><a class="nav-link" id="pending-tab" data-bs-toggle="tab" href="#tab-pending" role="tab" aria-controls="tab-pending" aria-selected="false">Pending and Unmatched Records</a></li>
      <li class="nav-item"><a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#tab-history" role="tab" aria-controls="tab-history" aria-selected="false">History</a></li>
    </ul>
  </div>

  <div class="col-12">
    <div class="tab-content border border-top-0 p-3" id="myTabContent">
      <div class="tab-pane fade show active" id="tab-transact" role="tabpanel" aria-labelledby="transact-tab">
        <div class="row">
          <div class="col-md-6 col-lg-4 mb-2">
            <input type="file" class="form-control" id="csvFile" accept=".csv">
          </div>


          <div class="col-lg-4 mb-2">
            <button class="btn btn-primary w-100 d-none" type="button" id="viewBtn" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
              Launch static backdrop modal
            </button>
          </div>
          <div class="col-lg-12">
            <p class="text-muted small mb-4">
              Please verify each record carefully before submission. Ensure the Order Number, Order Line ID, Warehouse, Client, and Fulfillment Status are accurate and complete.
              Any discrepancies should be resolved prior to finalizing to maintain audit integrity and operational accuracy.
            </p>
            <div id="preview"></div>
          </div>
        </div>
      </div>
      <div class="tab-pane fade p-0" id="tab-pending" role="tabpanel" aria-labelledby="pending-tab">
        <div class="card m-0">
          <div class="card-header bg-info">
              <h2>Pending and Unmatched Records</h2>
          </div>
          <div class="card-body">
              <div class="row">
                  <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                    </div>
                  </div>

                  <div class="col-xxl-12">
                      <div class="table-responsive">
                          <table class="table table-sm">
                              <thead class="table-dark">
                                <tr>
                                    <th class="fs-11"></th>
                                    <th class="fs-11 text-end">Order No.</th>
                                    <th class="fs-11 text-end">Order Line ID</th>
                                    <th class="fs-11">Client</th>
                                    <th class="fs-11">Warehouse</th>
                                    <th class="fs-11">Staff</th>
                                    <th class="fs-11">Date</th>
                                    <th class="fs-11">Status</th>
                                    <th class="fs-11 text-end">Expected Amount</th>
                                </tr>
                              </thead>

                              <tbody id="audit-table-body">
                                  <!-- Data will be loaded here -->
                              </tbody>
                          </table>
                      </div>
                  </div>
                  <div class="col-xxl-12 text-end">
                    <button id="loadMoreBtn" class="btn btn-secondary">Load More</button>
                  </div>
              </div>
          </div>
        </div>
      </div>
      <div class="tab-pane fade" id="tab-history" role="tabpanel" aria-labelledby="history-tab">
        <div class="card">
          <div class="card-header bg-warning">
            <h2>CSV Files</h2>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-xxl-4 col-lg-6">
                <input type="text" id="historySearchInput" class="form-control mb-2" placeholder="Search by filename...">
              </div>
              <div class="col-xxl-12">
                <div class="table-responsive">
                  <table class="table table-bordered table-sm">
                    <thead>
                      <tr>
                        <th>Filename</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody id="history-table-body">
                      <!-- history rows go here -->
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="col-xxl-12 text-center">
                <button id="loadMoreHistoryBtn" class="btn btn-secondary">Load More</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
<div class="modal fade" id="staticBackdrop" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl mt-6" role="document">
    <div class="modal-content border-0">
      <div class="modal-header bg-info">
        
          <h4 class="mb-1" id="staticBackdropLabel">Details</h4>
        <!-- Close Button -->
        <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
          <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>

      

      <!-- Modal Body -->
      <div class="modal-body p-0">


        <!-- Content -->
        <div class="px-0 py-4">
          <div class="row">
            <!-- Left Column -->
            <div class="col-lg-12">
              <div id="orderInfo"></div>
            </div>

            

          </div>
        </div>
      </div>
    </div>
  </div>
</div>



<script>
  document.getElementById('csvFile').addEventListener('change', function () {
    const file = this.files[0];
    const preview = document.getElementById('preview');

    if (!file || !file.name.toLowerCase().endsWith('.csv')) {
      alert('Please upload a valid CSV file.');
      return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
      const text = e.target.result;

      // Split into rows by line breaks (handle different line endings)
      // Filter out empty lines just in case
      const rows = text.split(/\r\n|\n|\r/).filter(row => row.trim() !== '');

      if (rows.length > 5001) {
        Swal.fire({
          icon: 'error',
          title: 'Too Many Rows',
          text: 'CSV file exceeds maximum allowed 5000 rows. Please upload a smaller file.'
        });
        document.getElementById('csvFile').value = '';
        preview.innerHTML = '';
        return;
      }


      // Show spinner while uploading
      preview.innerHTML = `
        <div class="d-flex justify-content-center py-4">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      `;

      const formData = new FormData();
      formData.append('file', file);

      const startTime = performance.now();

      fetch('csv_upload.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(html => {
        const endTime = performance.now();
        const duration = endTime - startTime;
        const delay = duration < 2000 ? 2000 - duration : 0;

        setTimeout(() => {
          preview.innerHTML = html;

          // Reinitialize Sortable
          document.querySelectorAll('[data-sortable="data-sortable"]').forEach(container => {
            new Sortable(container, {
              animation: 150,
              group: 'shared',
              ghostClass: 'sortable-ghost'
            });
          });

          // ⚠️ ✅ ADD THIS TO ENABLE SUBMIT CONFIRMATION
          bindFormSubmitConfirmation();

          // Show SweetAlert2 notice
          Swal.fire({
            icon: 'info',
            title: 'Notice',
            html: 'Rows highlighted in <span style="color:red;"><b>red</b></span> are data that the system does not know the unit cost.<br>If you choose to save it, it will be saved only to this module for you to see.',
            confirmButtonText: 'OK'
          });

        }, delay);
      })
      .catch(error => {
        preview.innerHTML = 'Error uploading file.';
        console.error('Upload failed:', error);
      });
    };

    reader.readAsText(file);
  });
</script>
<script>
document.addEventListener('click', function(e) {
  if (e.target && e.target.classList.contains('view-details-btn')) {
    const targetId = e.target.getAttribute('target-id');
    const orderInfo = document.getElementById('orderInfo');

    if (targetId && orderInfo) {
      orderInfo.innerHTML = `
        <div class="d-flex justify-content-center py-4">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      `;

      fetch(`order_details.php?id=${encodeURIComponent(targetId)}`)
        .then(response => response.text())
        .then(html => {
          orderInfo.innerHTML = html;
        })
        .catch(error => {
          orderInfo.innerHTML = '<div class="text-danger">Failed to load order details.</div>';
          console.error('Error loading details:', error);
        });
    }
  }
});
</script>

<script>
function bindFormSubmitConfirmation() {
  const form = document.getElementById('financeForm');

  if (!form || form.dataset.bound === "true") return;
  form.dataset.bound = "true";

  form.addEventListener('submit', function(e) {
    e.preventDefault(); // Stop normal form submission

    Swal.fire({
      title: 'Submit records?',
      text: 'Are you sure you want to send these for processing?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, submit',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData(form);

        fetch(form.action, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json()) // Expecting JSON from PHP
        .then(data => {
          if (data.success) {
            // Redirect on success
            window.location.href = "../Auditing/?csv=success";
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Submission Failed',
              text: data.message || 'Something went wrong during submission.'
            });
          }
        })
        .catch(error => {
          Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Failed to submit the form. Please try again.'
          });
          console.error('Submission error:', error);
        });
      }
    });
  });
}
</script>
<script>
let offset = 0;
let currentSearch = '';
let typingTimer;
const typingDelay = 300; // milliseconds

function loadMoreAuditData(reset = false) {
    if (reset) {
        offset = 0;
        document.getElementById('audit-table-body').innerHTML = '';
        document.getElementById('loadMoreBtn').disabled = false;
        document.getElementById('loadMoreBtn').innerText = 'Load More';
    }

    fetch(`pending.php?offset=${offset}&search=${encodeURIComponent(currentSearch)}`)
        .then(response => response.json())
        .then(rows => {
            if (rows.length === 0 && offset === 0) {
                document.getElementById('audit-table-body').innerHTML = '<tr><td colspan="8" class="text-center">No records found</td></tr>';
                document.getElementById('loadMoreBtn').style.display = 'none';
                return;
            }

            if (rows.length === 0) {
                document.getElementById('loadMoreBtn').disabled = true;
                document.getElementById('loadMoreBtn').innerText = 'No More Records';
                return;
            }

            const tbody = document.getElementById('audit-table-body');
            rows.forEach(rowHtml => {
                const temp = document.createElement('tbody');
                temp.innerHTML = rowHtml;
                tbody.appendChild(temp.firstElementChild);
            });

            offset += 20;
            document.getElementById('loadMoreBtn').style.display = 'inline-block';
        })
        .catch(err => console.error('Error loading data:', err));
}

// Initial data load
loadMoreAuditData();

// Load more on button click
document.getElementById('loadMoreBtn').addEventListener('click', () => {
    loadMoreAuditData(false);
});

// Live search: run on typing (with debounce)
document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        currentSearch = document.getElementById('searchInput').value.trim();
        loadMoreAuditData(true);
    }, typingDelay);
});
</script>

<script>
let historyOffset = 0;
let historySearch = '';
let historyTypingTimer;
const historyTypingDelay = 300;
const historyLimit = 20;

function loadMoreHistoryData(reset = false) {
  if (reset) {
    historyOffset = 0;
    document.getElementById('history-table-body').innerHTML = '';
    document.getElementById('loadMoreHistoryBtn').disabled = false;
    document.getElementById('loadMoreHistoryBtn').innerText = 'Load More';
  }

  fetch(`history.php?offset=${historyOffset}&search=${encodeURIComponent(historySearch)}&limit=${historyLimit}`)
    .then(response => response.json())
    .then(rows => {
      const tbody = document.getElementById('history-table-body');

      if (rows.length === 0 && historyOffset === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center">No records found</td></tr>';
        document.getElementById('loadMoreHistoryBtn').style.display = 'none';
        return;
      }

      if (rows.length === 0) {
        document.getElementById('loadMoreHistoryBtn').disabled = true;
        document.getElementById('loadMoreHistoryBtn').innerText = 'No More Records';
        return;
      }

      rows.forEach(rowHtml => {
        const temp = document.createElement('tbody');
        temp.innerHTML = rowHtml;
        tbody.appendChild(temp.firstElementChild);
      });

      historyOffset += historyLimit;
      document.getElementById('loadMoreHistoryBtn').style.display = 'inline-block';
    })
    .catch(err => console.error('Error loading history:', err));
}

// Load initial 20
loadMoreHistoryData();

// "Load More" button
document.getElementById('loadMoreHistoryBtn').addEventListener('click', () => {
  loadMoreHistoryData(false);
});

// Live search
document.getElementById('historySearchInput').addEventListener('input', () => {
  clearTimeout(historyTypingTimer);
  historyTypingTimer = setTimeout(() => {
    historySearch = document.getElementById('historySearchInput').value.trim();
    loadMoreHistoryData(true);
  }, historyTypingDelay);
});
</script>
<?php if (isset($_GET['csv']) && $_GET['csv'] === 'success'): ?>
<script>
  window.addEventListener('DOMContentLoaded', function() {
    var tab = document.getElementById('pending-tab');
    if (tab) {
      tab.click();
    }
  });
</script>
<?php endif; ?>
