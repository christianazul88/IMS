<!-- All Products Section -->
<div class="col-lg-12">
    <!-- Products Section Card -->
    <div class="card mb-3">
        <!-- Card Header -->
        <div class="card-header position-relative">
            <div class="row">
                <div class="col-4">
                    <h5 class="mb-0 mt-1">All Products</h5>
                </div>
                <div class="col-8 text-end" aria-live="polite">
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
            <form class="position-relative" onsubmit="return false;">
                <input class="form-control form-control-sm search-input lh-1 rounded-2 ps-4" id="searchInput" type="search" placeholder="Search by product, brand or category..." aria-label="Search products" autocomplete="off" />
                <div class="position-absolute top-50 start-0 translate-middle-y ms-2">
                <span class="fas fa-search text-400 fs-10"></span>
                </div>
            </form>
            </div>
            <!-- Sorting and View Options -->
            <div class="col position-sm-relative position-absolute top-0 end-0 me-3 me-sm-0 p-0">
            <div class="row g-0 g-md-3 justify-content-end">
                <div class="col-auto row gx-2">
                <form class="row gx-2" onsubmit="return false;">
                    <div class="col-md-6 d-none d-lg-block mb-3"><small class="fw-semi-bold">Warehouse:</small></div>
                    <div class="col-md-6 mb-3">
                    <!-- Warehouse Select -->
                        <select name="warehouse" id="warehouse" class="form-select form-select-sm" aria-label="Filter by warehouse">
                            <option value="">All warehouses</option>
                            <?php

                           $warehouse_info_query = "
                                SELECT DISTINCT
                                    w.hashed_id,
                                    w.warehouse_name
                                FROM warehouse w
                                INNER JOIN stocks s
                                    ON s.warehouse = w.hashed_id
                                WHERE s.item_status = 0
                                AND w.hashed_id IN ($user_warehouse_id)
                                ORDER BY w.warehouse_name
                            ";

                            $warehouse_info_result = $conn->query($warehouse_info_query);

                            if ($warehouse_info_result->num_rows > 0) {
                                while ($row = $warehouse_info_result->fetch_assoc()) {
                                    $tab_warehouse_name = $row['warehouse_name'];
                                    $id = $row['hashed_id'];

                                    echo '<option value="' . htmlspecialchars($id) . '">' . htmlspecialchars($tab_warehouse_name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                
                    <div class="col-md-6 d-none d-lg-block mb-3"><small class="fw-semi-bold">Sort:</small></div>
                    <div class="col-md-6 mb-3">
                    <!-- Sort Select -->
                        <select name="sort" id="sortBy" class="form-select form-select-sm" aria-label="Sort products">
                            <option value="date_desc">Latest delivery</option>
                            <option value="date_asc">Oldest delivery</option>
                            <option value="qty_asc">Quantity: low to high</option>
                            <option value="qty_desc">Quantity: high to low</option>
                            <option value="name_asc">Name: A&ndash;Z</option>
                        </select>
                    </div>
                </form>
                </div>
            </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Inline error banner, hidden unless a request fails -->
    <div id="listError" class="alert alert-danger d-none" role="alert"></div>

    <div id="listBody" aria-live="polite">

    </div>
    <!-- Pagination Section -->
    <div class="card">
        <div class="card-body">
        <div class="row g-3 flex-center justify-content-md-between">
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
            <div class="col-auto d-flex align-items-center gap-2" id="pagination">

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



<style>
    /* Simple skeleton placeholder so the list never looks "stuck" while loading */
    .skeleton-card .placeholder {
        background-color: var(--falcon-tertiary-bg, #e9ecef);
    }
    .stock-flag {
        font-size: 0.65rem;
        display: inline-block;
        margin-top: 0.25rem;
    }
</style>

<script>
$(document).ready(function () {
    let currentPage = 1;
    const limit = 9;
    let searchDebounceTimer = null;
    let requestSeq = 0; // guards against an older, slower request overwriting a newer one

    // --- helpers -------------------------------------------------------

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDate(dateString) {
        if (!dateString) return null;
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return null;
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    // Returns a badge describing quantity vs. safety stock, or null if the
    // backend hasn't provided a `safety` value for this item.
    function stockFlag(quantity, safety) {
        if (safety === undefined || safety === null || safety === '') return null;
        const qty = Number(quantity) || 0;
        const safetyNum = Number(safety) || 0;
        if (qty <= safetyNum) {
            return { badge: 'bg-danger', label: 'Below safety level' };
        }
        if (qty <= safetyNum * 1.2) {
            return { badge: 'bg-warning text-dark', label: 'Near safety level' };
        }
        return { badge: 'bg-success', label: 'Healthy stock' };
    }

    function skeletonCard() {
        return `
            <div class="card mb-3 overflow-hidden skeleton-card placeholder-glow">
                <div class="card-body">
                    <span class="placeholder col-2 rounded-pill me-2"></span>
                    <span class="placeholder col-2 rounded-pill"></span>
                    <h5 class="mt-3"><span class="placeholder col-4"></span></h5>
                    <span class="placeholder col-6"></span>
                </div>
            </div>`;
    }

    function showSkeleton() {
        const listBody = $('#listBody').attr('aria-busy', 'true').empty();
        for (let i = 0; i < Math.min(limit, 6); i++) {
            listBody.append(skeletonCard());
        }
    }

    function showError(message) {
        $('#listBody').removeAttr('aria-busy').empty();
        $('#listError').removeClass('d-none').text(message);
        $('#totalItems, #matchedItems').text('—');
        $('#pagination').empty();
    }

    // --- rendering -------------------------------------------------------

    function renderList(response) {
        $('#listError').addClass('d-none');
        const listBody = $('#listBody').removeAttr('aria-busy').empty();
        const items = response.data || [];

        if (items.length === 0) {
            listBody.append(`
                <div class="card">
                    <div class="card-body text-center py-5 text-600">
                        <span class="far fa-folder-open fs-4 d-block mb-2"></span>
                        No products match your search.
                    </div>
                </div>`);
        } else {
            items.forEach((item) => {
                const dateLabel = formatDate(item.created_date) || 'N/A';
                const flag = stockFlag(item.quantity, item.safety);
                const flagHtml = flag
                    ? `<span class="badge stock-flag ${flag.badge}">${escapeHtml(flag.label)}</span>`
                    : '';
                const productId = escapeHtml(item.product_id);
                const warehouseId = escapeHtml(item.warehouse);
                const collapseId = `item${productId}-${warehouseId}`;

                listBody.append(`
                    <article class="card mb-3 overflow-hidden">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col-md-12 col-lg-12 p-x1">
                                    <div class="row g-0 h-100">
                                        <div class="col-lg-8 col-xxl-9 d-flex flex-column pe-x1">
                                            <div class="d-flex gap-2 flex-wrap mb-3">
                                                <span class="badge rounded-pill badge-subtle-success">
                                                    <span class="fas fa-object-group me-1"></span>
                                                    <span>${escapeHtml(item.category)}</span>
                                                </span>
                                                <span class="badge rounded-pill badge-subtle-info">
                                                    <span class="fas fa-warehouse me-1"></span>
                                                    <span>${escapeHtml(item.wh)}</span>
                                                </span>
                                            </div>
                                            <h5 class="fs-9 mb-1">${escapeHtml(item.brand)}</h5>
                                            <h4 class="mt-1 fs-9 fs-lg-8 text-900">${escapeHtml(item.product_name)}</h4>
                                            <div class="flex-1 d-flex align-items-end fw-semi-bold fs-10">
                                                <span class="me-1 text-900">${escapeHtml(dateLabel)}</span>
                                                <span class="me-2 text-secondary">| Latest Delivery Date</span>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-xxl-3 mt-4 mt-lg-0">
                                            <div class="h-100 rounded border-lg border-1 d-flex flex-lg-column justify-content-between p-lg-3">
                                                <div class="mb-lg-4 mt-auto mt-lg-0">
                                                    <h4 class="mb-1 lh-1 fs-7 text-warning d-flex align-items-end">${Number(item.quantity) || 0}</h4>
                                                    <p class="mb-0 fs-11 text-800">Total Available Quantity</p>
                                                    ${flagHtml}
                                                </div>
                                                <div class="mt-3 d-flex flex-lg-column gap-2">
                                                    <button class="btn btn-md btn-primary fs-10"
                                                            type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#${collapseId}"
                                                            aria-expanded="false"
                                                            aria-controls="${collapseId}"
                                                            data-id="${productId}"
                                                            data-wh="${warehouseId}">
                                                        <span class="fas fa-info-circle"></span>
                                                        <span class="ms-1 d-none d-lg-inline">View details</span>
                                                    </button>
                                                    <a class="btn btn-md btn-primary fs-10"
                                                            href="../Product-list/?update=${encodeURIComponent(item.key_product)}">
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
                        <div class="collapse" id="${collapseId}"></div>
                    </article>
                `);
            });
        }

        $('#totalItems').text(response.total);
        $('#matchedItems').text(items.length);
        updatePagination(response.total);
    }

    function updatePagination(total) {
        const totalPages = Math.max(1, Math.ceil(total / limit));
        if (currentPage > totalPages) currentPage = totalPages;

        const pagination = $('#pagination').empty();
        pagination.append(`<span class="text-600 fs-11 me-2">Page ${currentPage} of ${totalPages}</span>`);

        if (currentPage > 1) {
            pagination.append(`<button class="pagination-btn btn btn-sm btn-secondary" data-page="${currentPage - 1}">Previous</button>`);
        }
        for (let i = Math.max(1, currentPage - 1); i <= Math.min(totalPages, currentPage + 1); i++) {
            pagination.append(`<button class="pagination-btn btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-primary'}" data-page="${i}">${i}</button>`);
        }
        if (currentPage < totalPages) {
            pagination.append(`<button class="pagination-btn btn btn-sm btn-secondary" data-page="${currentPage + 1}">Next</button>`);
        }
    }

    // --- data loading -------------------------------------------------------

    function loadData() {
        const search = $('#searchInput').val();
        const warehouse = $('#warehouse').val();
        const sort = $('#sortBy').val();
        const offset = (currentPage - 1) * limit;
        const thisRequest = ++requestSeq;

        showSkeleton();

        $.getJSON('../config/getStockListData.php', { limit, offset, search, warehouse, sort })
            .done(function (response) {
                if (thisRequest !== requestSeq) return; // a newer request already landed
                if (response.error) {
                    showError('Something went wrong loading products. Please try again.');
                    return;
                }
                renderList(response);
            })
            .fail(function () {
                if (thisRequest !== requestSeq) return;
                showError('Could not reach the server. Check your connection and try again.');
            });
    }

    $('#searchInput').on('input', function () {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function () {
            currentPage = 1;
            loadData();
        }, 350); // wait for the person to stop typing before hitting the server
    });

    $('#warehouse, #sortBy').on('change', function () {
        currentPage = 1;
        loadData();
    });

    $(document).on('click', '.pagination-btn', function () {
        currentPage = parseInt($(this).data('page'), 10);
        loadData();
        const listTop = $('#listBody').offset();
        if (listTop) $('html, body').animate({ scrollTop: listTop.top - 100 }, 200);
    });

    loadData();

    // --- batch detail collapse -------------------------------------------------------

    $(document).on('click', '.btn[data-bs-toggle="collapse"]', function () {
        const $btn = $(this);
        const targetDiv = $btn.data('bs-target');
        const $target = $(targetDiv);
        const itemId = $btn.data('id');
        const warehouse = $btn.data('wh');

        // `loaded` flag lets a failed request be retried on the next click,
        // instead of silently doing nothing because the div isn't empty.
        if ($target.data('loaded')) return;

        $target.html('<div class="text-center py-3"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading batch details&hellip;</div>');

        $.get('../Inventory-stock/item_details.php', { id: itemId, wh: warehouse })
            .done(function (response) {
                $target.html(response).data('loaded', true);
            })
            .fail(function () {
                $target.html('<div class="text-danger p-3">Failed to load item details. Click "View details" again to retry.</div>');
            });
    });

    // --- batch modal -------------------------------------------------------

    let modalTargetId, modalTargetPId, modalWarehouseId, modalOffset = 0;
    let barcodeSearch = '';
    let barcodeDebounceTimer = null;

    $(document).on('click', "[data-bs-toggle='modal']", function () {
        modalTargetId = $(this).attr('target-id');
        modalTargetPId = $(this).attr('target-Pid');
        modalWarehouseId = $(this).attr('target-wh');
        modalOffset = 0;
        barcodeSearch = '';

        const modalContent = $('#modal-1-display');

        modalContent.html(`
            <div class="row mb-3">
                <div class="col-12 text-start">
                    <label for="barcode-search">Search Unique Barcode</label>
                    <input class="form-control mb-3" id="barcode-search" type="text" placeholder="Enter barcode..." autocomplete="off">
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
                    <tbody id="table-body">
                        <tr><td colspan="5" class="text-center py-3"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading&hellip;</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="text-center my-3">
                <button id="load-more-btn" class="btn btn-primary d-none">Load More</button>
            </div>
        `);

        loadMoreData(true);

        $('#load-more-btn').off('click').on('click', function () {
            loadMoreData(false);
        });
    });

    function loadMoreData(isFirstLoad) {
        $('#load-more-btn').prop('disabled', true).text('Loading...');

        $.ajax({
            url: '../Inventory-stock/modal-display-1.php',
            type: 'GET',
            data: {
                target_id: modalTargetId,
                targetPId: modalTargetPId,
                warehouseID: modalWarehouseId,
                offset: modalOffset,
                search: barcodeSearch
            },
            dataType: 'json',
            success: function (response) {
                if (isFirstLoad) $('#table-body').empty();
                $('#table-body').append(response.html);
                modalOffset += 100;
                if (response.has_more) {
                    $('#load-more-btn').removeClass('d-none').prop('disabled', false).text('Load More');
                } else {
                    $('#load-more-btn').addClass('d-none');
                }
            },
            error: function () {
                if (isFirstLoad) {
                    $('#table-body').html('<tr><td colspan="5" class="text-center text-danger py-3">Failed to load batch details.</td></tr>');
                } else {
                    $('#load-more-btn').text('Error! Try Again').prop('disabled', false);
                }
            }
        });
    }

    $(document).on('input', '#barcode-search', function () {
        const value = $(this).val().trim();
        clearTimeout(barcodeDebounceTimer);
        barcodeDebounceTimer = setTimeout(function () {
            barcodeSearch = value;
            modalOffset = 0;
            $('#table-body').empty();
            loadMoreData(true);
        }, 350);
    });

    if (typeof GLightbox === 'function') {
        GLightbox({ selector: '[data-glightbox]' });
    }
});
</script>
