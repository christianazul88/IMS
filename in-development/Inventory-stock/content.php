<div class="row g-3 mb-3">
    <!-- Main Container -->
    <div class="col-xxl-4 col-xl-4 col-4">
        <div class="card h-md-100 ecommerce-card-min-width">
            <div class="card-header pb-0">
                <h6 class="mb-0 mt-2 d-flex align-items-center">
                    Inventory Access
                    <span class="ms-1 text-400" data-bs-toggle="tooltip" data-bs-placement="top" title="all accessible warehouse/ Inventory">
                    <span class="far fa-question-circle" data-fa-transform="shrink-1"></span>
                    </span>
                </h6>
            </div>
            <a href="../Inventory-stock/">
                <div class="card-body d-flex flex-column justify-content-end">
                    <div class="row">
                        <div class="col-lg-12 mb-4 mb-lg-0">
                            <div class="row">
                                <div class="col">
                                    <p class="font-sans-serif lh-1 mb-4 fs-5 text-dark">
                                        <?php 
                                        // Split the string into an array using the comma as a delimiter
                                        $unique_warehouse_ids_array = explode(",", $user_warehouse_id);

                                        // Trim whitespace from each element in the array (optional, in case of spaces)
                                        $unique_warehouse_ids_array = array_map('trim', $unique_warehouse_ids_array);

                                        // Count the number of elements in the array
                                        $unique_count = count($unique_warehouse_ids_array);

                                        echo $unique_count;
                                        ?>
                                    </p>
                                    <span></span>
                                </div>
                            </div>   
                        </div>
                    </div>
                    <!-- -------------------- -->
                </div>
            </a>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-4 col-4">
        
                <?php include "belowsafety.php";?>
        
    </div>

    <div class="col-xxl-4 col-xl-4 col-4">
                <?php include "prolongitems.php";?>
            
    </div>


    <div class="col-xxl-14 col-xl-12 col-12">
    <!-- Courses Section Card -->
    <div class="card mb-3">
        <!-- Card Header -->
        <div class="card-header position-relative">
            <div class="row">
                <div class="col-4">
                    <h5 class="mb-0 mt-1">All Products</h5>
                </div>
                <div class="col-8 text-end">
                    <div>
                        Total Products: <span id="totalItems">0</span>
                        | Matched Products: <span id="matchedItems">0</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Search and Filter Section -->
        <div class="card-body pt-0 pt-md-3">
        <div class="row g-3 align-items-center">
            <!-- Filter Button for Smaller Screens -->
            <div class="col-auto d-xl-none">
            <button class="btn btn-sm p-0 btn-link position-relative" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas" aria-controls="filterOffcanvas">
                <span class="fas fa-filter fs-9 text-700"></span>
            </button>
            </div>
            <!-- Search Bar -->
            <div class="col">
            <form class="position-relative">
                <input class="form-control form-control-sm search-input lh-1 rounded-2 ps-4" id="searchInput" type="search" placeholder="Search..." aria-label="Search" />
                <div class="position-absolute top-50 start-0 translate-middle-y ms-2">
                <span class="fas fa-search text-400 fs-10"></span>
                </div>
            </form>
            </div>
            <!-- Sorting and View Options -->
            <div class="col position-sm-relative position-absolute top-0 end-0 me-3 me-sm-0 p-0">
            <div class="row g-0 g-md-3 justify-content-end">
                <!-- Sort By Dropdown -->
                <div class="col-auto row gx-2">
                <form class="row gx-2">
                    <div class="col-auto d-none d-lg-block"><small class="fw-semi-bold">warehouse:</small></div>
                    <div class="col-auto">
                    <!-- Warehouse Select -->
                        <select name="warehouse" id="warehouse" class="form-select form-select-sm">
                            <!-- <option value="">All Warehouses</option> -->
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
                    </div>
                </form>
                </div>
            </div>
            </div>
        </div>
        </div>
    </div>
    <div id="listBody">
    
    </div>
    <!-- Pagination Section -->
    <div class="card">
        <div class="card-body">
        <div class="row g-3 flex-center justify-content-md-between">
            <!-- Items Per Page Dropdown -->
            <div class="col-auto">
            <form class="row gx-2" hidden>
                <div class="col-auto"><small>Show:</small></div>
                <div class="col-auto">
                <select class="form-select form-select-sm" aria-label="Show courses">
                    <option selected="selected" value="9">9</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
                </div>
            </form>
            </div>
            <!-- Pagination Controls -->
            <div class="col-auto" id="pagination">
            
            </div>
        </div>
        </div>
    </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="firstModal" aria-hidden="true" aria-labelledby="exampleModalToggleLabel" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalToggleLabel">Batch Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Content will be loaded dynamically here -->
        <div id="modal-1-display" class="text-center">
          <!-- Initially Empty (Progress Bar will appear) -->
        </div>
      </div>
    </div>
  </div>
</div>

<script>
    let currentPage = 1;
    let limit = 9;

    $(document).ready(function () {
        let targetId, targetPId, warehouseID, offset = 0;
        let barcodeSearch = '';

        $(document).on("click", "[data-bs-toggle='modal']", function () {
            targetId = $(this).attr("target-id");
            targetPId = $(this).attr("target-Pid");
            warehouseID = $(this).attr("target-wh");
            offset = 0;

            let modalContent = $("#modal-1-display");

            modalContent.html(`
                <div class="row mb-3">
                    <div class="col-12 text-start">
                        <label for="barcode-search">Search Unique Barcode</label>
                        <input class="form-control mb-3" id="barcode-search" type="text" placeholder="Enter barcode...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                        <thead class="table-dark">
                            <tr>
                                <th>Unique Barcode</th>
                                <th>Status</th>
                                <th>Unit Cost</th>
                                <th>Sold Amount</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody id="table-body"></tbody>
                    </table>
                </div>
                <div class="text-center my-3">
                    <button id="load-more-btn" class="btn btn-primary d-none">Load More</button>
                </div>
            `);

            loadMoreData();

            $("#load-more-btn").click(function () {
                loadMoreData();
            });
        });

        function loadMoreData() {
            $("#load-more-btn").prop("disabled", true).text("Loading...");
            $.ajax({
                url: "modal-display-1.php",
                type: "GET",
                data: {
                    target_id: targetId,
                    targetPId: targetPId,
                    warehouseID: warehouseID,
                    offset: offset,
                    search: barcodeSearch
                },
                dataType: "json",
                success: function (response) {
                    $("#table-body").append(response.html);
                    offset += 100;
                    if (response.has_more) {
                        $("#load-more-btn").removeClass("d-none").prop("disabled", false).text("Load More");
                    } else {
                        $("#load-more-btn").addClass("d-none");
                    }
                },
                error: function () {
                    $("#load-more-btn").text("Error! Try Again").prop("disabled", false);
                }
            });
        }

        $(document).on('input', '#barcode-search', function () {
            barcodeSearch = $(this).val().trim();
            offset = 0;
            $("#table-body").empty();
            loadMoreData();
        });

        let lightbox = GLightbox({ selector: '[data-glightbox]' });

        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', options);
        }

        function loadData() {
            const search = $('#searchInput').val();
            const warehouse = $('#warehouse').val();
            const offset = (currentPage - 1) * limit;

            $.getJSON('../config/getStockListData.php', { limit, offset, search, warehouse }, function (response) {
                if (response.error) {
                    console.error(response.error);
                    return;
                }

                const listBody = $('#listBody');
                listBody.empty();

                if (response.data.length === 0) {
                    listBody.append('<div class="text-center py-5">No results found.</div>');
                } else {
                    response.data.forEach((item) => {
                        listBody.append(`
                            <article class="card mb-3 overflow-hidden">
                                <div class="card-body p-0">
                                    <div class="row g-0">
                                        <div class="col-md-4 col-lg-3">
                                            <div class="hoverbox h-md-100">
                                                <a class="text-decoration-none" 
                                                    href="${item.product_img || '../../assets/img/def_img.png'}" 
                                                    data-gallery="gallery-2" 
                                                    data-glightbox>
                                                    <img class="h-100 w-100 object-fit-cover" 
                                                        src="${item.product_img || 'def_img.png'}" 
                                                        alt="${item.product_name || 'No Image'}" />
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-lg-9 p-x1">
                                            <div class="row g-0 h-100">
                                                <div class="col-lg-8 col-xxl-9 d-flex flex-column pe-x1">
                                                    <div class="d-flex gap-2 flex-wrap mb-3">
                                                        <span class="badge rounded-pill badge-subtle-success">
                                                            <span class="fas fa-object-group me-1"></span>
                                                            <span>${item.category}</span>
                                                        </span>
                                                        <span class="badge rounded-pill badge-subtle-info">
                                                            <span class="fas fa-warehouse me-1"></span>
                                                            <span>${item.wh}</span>
                                                        </span>
                                                    </div>
                                                    <h5 class="fs-9"><a href="#">${item.brand}</a></h5>
                                                    <h4 class="mt-3 mt-sm-0 fs-9 fs-lg-8">
                                                        <a class="text-900" href="#">${item.product_name}</a>
                                                    </h4>
                                                    <div class="flex-1 d-flex align-items-end fw-semi-bold fs-10">
                                                        <span class="me-1 text-900">${formatDate(item.created_date) || 'N/A'}</span>
                                                        <span class="me-2 text-secondary">| Latest Delivery Date</span>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-xxl-3 mt-4 mt-lg-0">
                                                    <div class="h-100 rounded border-lg border-1 d-flex flex-lg-column justify-content-between p-lg-3">
                                                        <div class="mb-lg-4 mt-auto mt-lg-0">
                                                            <h4 class="mb-1 lh-1 fs-7 text-warning d-flex align-items-end">${item.quantity || 0}</h4>
                                                            <p class="mb-0 fs-11 text-800">Total Available Quantity</p>
                                                        </div>
                                                        <div class="mt-3 d-flex flex-lg-column gap-2">
                                                            <button class="btn btn-md btn-primary fs-10" 
                                                                    type="button" 
                                                                    data-bs-toggle="collapse" 
                                                                    data-bs-target="#item${item.product_id}-${item.warehouse}" 
                                                                    aria-expanded="false" 
                                                                    aria-controls="item${item.product_id}"
                                                                    data-wh="${item.warehouse}">
                                                                <span class="fas fa-info-circle"></span>
                                                                <span class="ms-1 d-none d-lg-inline">View details</span>
                                                            </button>
                                                            <a class="btn btn-md btn-primary fs-10" 
                                                                    href="../Product-list/?update=${item.key_product}">
                                                                <span class="fas fa-pen-square"></span>
                                                                <span class="ms-1 d-none d-lg-inline">Edit product details</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="collapse" id="item${item.product_id}-${item.warehouse}"></div>
                            </article>
                        `);
                    });

                    GLightbox({ selector: '[data-glightbox]' });
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
                pagination.append(`<button class="pagination-btn btn btn-sm btn-secondary me-1" data-page="${currentPage - 1}">Previous</button>`);
            }

            for (let i = Math.max(1, currentPage - 1); i <= Math.min(totalPages, currentPage + 1); i++) {
                pagination.append(`<button class="pagination-btn btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-primary'} me-1" data-page="${i}">${i}</button>`);
            }

            if (currentPage < totalPages) {
                pagination.append(`<button class="pagination-btn btn btn-sm btn-secondary" data-page="${currentPage + 1}">Next</button>`);
            }
        }

        $(document).on('click', '.pagination-btn', function () {
            currentPage = parseInt($(this).data('page'));
            loadData();
        });

        $('#searchInput, #warehouse').on('input change', function () {
            currentPage = 1;
            loadData();
        });

        loadData();

        $(document).on('click', '.btn[data-bs-toggle="collapse"]', function () {
            const itemId = $(this).data('bs-target').replace('#item', '');
            const targetDiv = $(this).data('bs-target');
            const warehouse = $(this).data('wh');

            if ($(targetDiv).is(':empty')) {
                $.get(`item_details.php?id=${itemId}&wh=${warehouse}`, function (response) {
                    $(targetDiv).html(response);
                }).fail(function () {
                    $(targetDiv).html('<div class="text-danger p-3">Failed to load item details.</div>');
                });
            }
        });
    });
</script>

