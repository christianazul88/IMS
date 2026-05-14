<div class="card bg-danger text-white mb-3">
    <div class="card-body">
        <h5 class="card-title">Audit Automation Module</h5>
        <p class="card-text">This module is currently in development. It will allow users to automate their audit processes, making it easier to identify and address potential issues in a timely manner.</p>
    </div>
</div>


<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">Audit Automation Module</h5>
        <div class="text-end">
            <button class="btn btn-primary">Get Started</button>
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#schedaudit">Schedule Audit</button>
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#staticBackdrop">Audit Now</button>
        </div>
    </div>
</div>


<div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
        <div>
          <h5 class="card-title mb-1">Audit Logs</h5>
          <p class="text-muted small mb-0">Live audit schedule tracking with quick update access.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span id="rowCount" class="badge bg-primary rounded-pill py-2 px-3">0 records</span>
          <div class="input-group input-group-sm" style="min-width: 260px;">
            <span class="input-group-text bg-white border-end-0">🔍</span>
            <input type="text" id="search" class="form-control border-start-0" placeholder="Search audit logs...">
          </div>
        </div>
      </div>
      <div class="table-responsive border rounded-4 overflow-hidden shadow-sm table-card">
        <table id="audit-log-table" class="table table-borderless table-hover table-striped align-middle mb-0">
          <thead class="text-white">
            <tr>
              <th class="sortable text-white" onclick="setSort('audit_num')">Audit Number</th>
              <th class="sortable text-white" onclick="setSort('warehouse_name')">Warehouse</th>
              <th class="sortable text-white" onclick="setSort('schedule_date')">Schedule Date</th>
              <th class="sortable text-white" onclick="setSort('date_created')">Date Created</th>
              <th class="sortable text-white" onclick="setSort('user_fname')">Created By</th>
              <th class="sortable text-white" onclick="setSort('updated_at')">Date Updated</th>
              <th class="sortable text-white" onclick="setSort('updater_fname')">Updated By</th>
              <th class="sortable text-white" onclick="setSort('audit_status')">Audit Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="table-body">
            <!-- Data will be loaded here -->
          </tbody>
        </table>
      </div>
      <div id="pagination" class="d-flex justify-content-center mt-3">
        <!-- Pagination buttons will be loaded here -->
      </div>
    </div>
</div>

<script>
let currentPage = 1;
const limit = 10;
let sortField = 'id';
let sortOrder = 'desc';
let tableData = [];

function getStatusBadge(status) {
    const statusClasses = {
        'pending': 'bg-secondary',
        'active': 'bg-success',
        'paused': 'bg-warning text-dark',
        'completed': 'bg-info'
    };
    const badgeClass = statusClasses[status] || 'bg-light text-dark';
    return `<span class="badge status-badge ${badgeClass}">${status ? status.charAt(0).toUpperCase() + status.slice(1) : ''}</span>`;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatDateTime(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

function renderTable() {
    const tbody = document.getElementById('table-body');
    let html = '';
    tableData.forEach(row => {
        const pending = row.audit_status === 'pending';
        const updateButton = `<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill ${pending ? '' : 'disabled'}" ${pending ? `onclick="openUpdateModal(${row.id}, '${row.schedule_date || ''}')"` : 'disabled'}>${pending ? 'Update' : 'Locked'}</button>`;
        html += `<tr>
            <td><a href="../audit-dashboard/?audit_id=${row.id}" class="text-decoration-none">${row.audit_num || ''}</a></td>
            <td>${row.warehouse_name || ''}</td>
            <td>${formatDate(row.schedule_date)}</td>
            <td>${formatDateTime(row.date_created)}</td>
            <td>${row.user_fname || ''} ${row.user_lname || ''}</td>
            <td>${formatDateTime(row.updated_at)}</td>
            <td>${row.updater_fname || ''} ${row.updater_lname || ''}</td>
            <td>${getStatusBadge(row.audit_status)}</td>
            <td>${updateButton}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function compareValues(a, b, field) {
    const aValue = a[field] || '';
    const bValue = b[field] || '';

    if (field === 'date_created' || field === 'schedule_date' || field === 'updated_at') {
        return new Date(aValue) - new Date(bValue);
    }

    return String(aValue).localeCompare(String(bValue), 'en', { numeric: true, sensitivity: 'base' });
}

function sortData() {
    tableData.sort((a, b) => {
        const result = compareValues(a, b, sortField);
        return sortOrder === 'asc' ? result : -result;
    });
    document.querySelectorAll('.sortable').forEach(th => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        if (th.getAttribute('onclick') === `setSort('${sortField}')`) {
            th.classList.add(sortOrder === 'asc' ? 'sorted-asc' : 'sorted-desc');
        }
    });
}

function setSort(field) {
    if (sortField === field) {
        sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
    } else {
        sortField = field;
        sortOrder = 'asc';
    }
    sortData();
    renderTable();
}

function loadData() {
    const search = document.getElementById('search').value;
    fetch(`get_audit_logs.php?page=${currentPage}&limit=${limit}&search=${encodeURIComponent(search)}`)
        .then(response => response.json())
        .then(data => {
            tableData = data.data || [];
            sortData();
            renderTable();
            document.getElementById('rowCount').textContent = data.total > 0 ? `${data.total.toLocaleString()} records` : 'No records found';

            // Build pagination
            const totalPages = Math.ceil(data.total / limit);
            let pagHtml = `<span class="me-3 text-muted">Page ${currentPage} of ${totalPages || 1}</span>`;
            if (currentPage > 1) {
                pagHtml += '<button class="btn btn-outline-primary me-2" onclick="changePage(-1)">Previous</button>';
            }
            if (currentPage < totalPages) {
                pagHtml += '<button class="btn btn-outline-primary" onclick="changePage(1)">Next</button>';
            }
            document.getElementById('pagination').innerHTML = pagHtml;
        })
        .catch(error => console.error('Error loading data:', error));
}

function changePage(direction) {
    currentPage += direction;
    loadData();
}

function openUpdateModal(id, scheduleDate) {
    document.getElementById('updateAuditId').value = id;
    document.getElementById('updateScheduleDate').value = scheduleDate ? scheduleDate.split(' ')[0] : '';
    const updateModal = new bootstrap.Modal(document.getElementById('updateScheduleModal'));
    updateModal.show();
}

document.getElementById('search').addEventListener('input', () => {
    currentPage = 1; // Reset to first page on search
    loadData();
});

// Load data on page load
document.addEventListener('DOMContentLoaded', loadData);
</script>

<style>
  #audit-log-table thead {
    background: linear-gradient(135deg,#1868ff 0%,#3c8dff 100%);
  }
  #audit-log-table thead th {
    border-bottom: 0;
    padding: 1rem 0.75rem;
    font-size: 0.9rem;
    letter-spacing: 0.02em;
  }
  #audit-log-table tbody tr {
    transition: transform 0.16s ease, background-color 0.16s ease;
  }
  #audit-log-table tbody tr:hover {
    background-color: rgba(13,110,253,0.08);
    transform: translateX(1px);
  }
  #audit-log-table td {
    vertical-align: middle;
    padding: 0.92rem 0.75rem;
  }
  #audit-log-table a.text-decoration-none {
    color: #0d6efd;
    font-weight: 600;
  }
  #audit-log-table a.text-decoration-none:hover {
    color: #0a58ca;
    text-decoration: underline;
  }
  .sortable {
    cursor: pointer;
    position: relative;
    white-space: nowrap;
  }
  .sortable:after {
    content: '↕';
    display: inline-block;
    margin-left: 0.35rem;
    font-size: 0.8rem;
    color: rgba(255,255,255,0.75);
  }
  .sortable.sorted-asc:after {
    content: '↑';
  }
  .sortable.sorted-desc:after {
    content: '↓';
  }
  .badge.status-badge {
    min-width: 92px;
  }
  .btn.disabled, .btn:disabled {
    opacity: 0.6;
    pointer-events: none;
    cursor: not-allowed;
  }
  .table-card {
    background-color: #fff;
  }
</style>

<!-- update schedule modal -->
<div class="modal fade" id="updateScheduleModal" tabindex="-1" aria-labelledby="updateScheduleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md mt-6" role="document">
    <div class="modal-content border-0 shadow-sm">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title" id="updateScheduleModalLabel">Update Audit Schedule</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="../config/update_schedule_audit.php" method="POST">
        <div class="modal-body p-4">
          <input type="hidden" name="audit_id" id="updateAuditId">
          <div class="mb-3">
            <label for="updateScheduleDate" class="form-label">New Schedule Date</label>
            <input type="date" class="form-control" id="updateScheduleDate" name="schedule_date" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- schedule audit modal -->
<div class="modal fade" id="schedaudit" data-bs-keyboard="false" data-bs-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg mt-6" role="document">
    <div class="modal-content border-0 shadow-sm">

      <!-- ✅ Header -->
      <div class="modal-header bg-primary text-white">
        <div>
          <h5 class="modal-title mb-1" id="staticBackdropLabel">Schedule Audit</h5>
          <small class="opacity-75">Create a new audit schedule for a selected warehouse</small>
        </div>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- ✅ Body -->
      <div class="modal-body p-4">
        <form action="../config/schedule_audit.php" method="POST">

          <!-- Description -->
          <div class="alert alert-info">
            Please select the warehouse and the audit start date. This will be used to track and manage audit activities.
          </div>

          <div class="row g-3">

            <!-- Warehouse -->
            <div class="col-md-6">
              <label class="form-label">Warehouse <span class="text-danger">*</span></label>
              <select name="warehouse" id="warehouse" class="form-select" required>
                <option value="">Select Warehouse</option>
                <?php echo implode("\n", $warehouse_options2); ?>
              </select>
            </div>

            <!-- Start Date -->
            <div class="col-md-6">
              <label class="form-label" for="datepicker">Start Date <span class="text-danger">*</span></label>
              <input class="form-control datetimepicker" id="datepicker" type="text" name="start_date"
                     placeholder="dd/mm/yy"
                     data-options='{"disableMobile":true}' required />
              <small class="text-muted">Select the date when the audit will begin.</small>
            </div>

          </div>

          <!-- ✅ Footer Buttons -->
          <div class="d-flex justify-content-end mt-4 gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              Cancel
            </button>
            <button type="submit" class="btn btn-success px-4">
              Schedule Audit
            </button>
          </div>

        </form>
      </div>

    </div>
  </div>
</div>