<div class="container mt-5">
    <h1 class="mb-4">Stocks Table</h1>

    <!-- Search Input -->
    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search...">

    <!-- Warehouse Select -->
    <select name="warehouse" id="warehouse" class="form-select mb-3">
        <!-- <option value="">All Warehouses</option> -->
        <option value="4b227777d4dd1fc61c6f884f48641d02b4d121d3fd328cb08b5531fcacdabf8a">Warehouse Sample 2</option>
        <?php 
        foreach ($user_warehouse_ids as $id) {
            $id = trim($id);
            $warehouse_info_query = "SELECT * FROM warehouse WHERE hashed_id = '$id'";
            $warehouse_info_result = mysqli_query($conn, $warehouse_info_query);
            if ($warehouse_info_result->num_rows > 0) {
                $row = $warehouse_info_result->fetch_assoc();
                $tab_warehouse_name = $row['warehouse_name'];
                echo '<option value="' . $id . '">' . $tab_warehouse_name . '</option>';
            }
        }
        ?>
    </select>

    <!-- Stocks Table -->
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th hidden>#</th> <!-- Hashed ID -->
                <th>Quantity</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Parent Barcode</th>
                <th>Created By</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody id="tableBody"></tbody>
    </table>

    <!-- Pagination and Total -->
    <div class="d-flex justify-content-between">
        <div id="pagination">

        <!-- <button class="btn btn-falcon-default btn-sm me-2" type="button" disabled="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Prev">
            <span class="fas fa-chevron-left"></span>
          </button>
          <a class="btn btn-sm btn-falcon-default text-primary me-2" href="#!">1</a>
          <a class="btn btn-sm btn-falcon-default me-2" href="#!">2</a>
          <a class="btn btn-sm btn-falcon-default me-2" href="#!">
            <span class="fas fa-ellipsis-h"></span>
          </a>
          <a class="btn btn-sm btn-falcon-default me-2" href="#!">303</a>
          <button class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Next">
            <span class="fas fa-chevron-right"></span>
          </button> -->
        </div>
        
        <div>
            Total Products: <span id="totalItems">0</span>
            | Matched Products: <span id="matchedItems">0</span>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let limit = 10;

function loadData() {
    const search = $('#searchInput').val();
    const warehouse = $('#warehouse').val(); // Get selected warehouse
    const offset = (currentPage - 1) * limit;

    $.getJSON('../config/getStockData.php', { limit, offset, search, warehouse }, function (response) {
        if (response.error) {
            console.error(response.error);
            return;
        }

        const tableBody = $('#tableBody');
        tableBody.empty();

        if (response.data.length === 0) {
            tableBody.append('<tr><td colspan="8" class="text-center">No result</td></tr>');
        } else {
            response.data.forEach((item) => {
                tableBody.append(`
                    <tr>
                        <td hidden>${item.id}</td>
                        <td>${item.quantity}</td>
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

$('#searchInput, #warehouse').on('input change', function () {
    currentPage = 1;
    loadData();
});

$(document).ready(function () {
    loadData();
});
</script>
