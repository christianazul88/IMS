<h4 class="mb-4">Product Table</h4>
<div class="row">
  <div class="col-auto">
    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search...">
  </div>
</div>
    
<div class="card">
  <div class="card-body">
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th hidden>#</th> <!-- Hashed ID -->
                <th>Product Name</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Parent Barcode</th>
                <th>Created By</th>
                <th>Date</th> <!-- If date field does not exist, comment it out -->
            </tr>
        </thead>
        <tbody id="tableBody"></tbody>
    </table>
    <div class="d-flex justify-content-between">
        <div id="pagination"></div>
        <div>
            Total Items: <span id="totalItems">0</span>
            | Matched Items: <span id="matchedItems">0</span>
        </div>
    </div>
  </div>
</div>

<script>
let currentPage = 1;
let limit = 10;

function loadData() {
    const search = $('#searchInput').val();
    const offset = (currentPage - 1) * limit;

    $.getJSON('../config/getData.php', { limit, offset, search }, function (response) {
        if (response.error) {
            console.error(response.error);
            return;
        }

        const tableBody = $('#tableBody');
        tableBody.empty();

        if (response.data.length === 0) {
            // Display 'No result' message
            tableBody.append(`
                <tr>
                    <td colspan="7" class="text-center">No result</td>
                </tr>
            `);
        } else {
            response.data.forEach((item) => {
                tableBody.append(`
                    <tr>
                        <td hidden>${item.id}</td>
                        <td>${item.product_name}</td>
                        <td>${item.category}</td>
                        <td>${item.brand}</td>
                        <td>${item.parent_barcode}</td>
                        <td>${item.created_by}</td>
                        <td>${item.created_date || 'N/A'}</td>
                    </tr>
                `);
            });
        }

        $('#totalItems').text(response.total);
        $('#matchedItems').text(response.data.length);
        updatePagination(response.total);
    });
}

function updatePagination(total) {
    const totalPages = Math.ceil(total / limit);
    const pagination = $('#pagination');
    pagination.empty();

    if (currentPage > 1) {
        pagination.append(`<button class="btn btn-sm btn-secondary me-1" onclick="changePage(${currentPage - 1})">Previous</button>`);
    }

    for (let i = Math.max(1, currentPage - 1); i <= Math.min(totalPages, currentPage + 1); i++) {
        pagination.append(`<button class="btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-primary'} me-1" onclick="changePage(${i})">${i}</button>`);
    }

    if (currentPage < totalPages) {
        pagination.append(`<button class="btn btn-sm btn-secondary" onclick="changePage(${currentPage + 1})">Next</button>`);
    }
}

function changePage(page) {
    currentPage = page;
    loadData();
}

$('#searchInput').on('input', function () {
    currentPage = 1; // Reset to first page on search input change
    loadData();      // Reload data based on the current search
});

$(document).ready(function () {
    loadData(); // Load the initial data
});
</script>
