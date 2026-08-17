<?php
/* ======================================================
   DUAL-MODE BOOTSTRAP
   ------------------------------------------------------
   Normally this file is include()'d by index.php, which
   already opened $conn, started the session, set up
   $user_warehouse_ids/$access/etc., and gated access with
   its "logistics" / Administrator check.

   The AJAX search/pagination below hits content.php
   directly as its own URL, so when that happens none of
   the above has run yet. Self-bootstrap in that case, and
   re-apply the same authorization check index.php uses -
   otherwise this endpoint would be reachable unauthenticated.
====================================================== */
$is_included = isset($conn);

if (!isset($conn)) {
    include "../config/database.php";
}
if (!isset($user_warehouse_ids)) {
    include "../config/on_session.php";
}

if (!$is_included) {
    if (!(strpos($access, "logistics") !== false || $user_position_name === "Administrator")) {
        http_response_code(403);
        exit('Not authorized.');
    }
}

// Unset the session variable if it exists
if (isset($_SESSION['outbound_id'])) {
    unset($_SESSION['outbound_id']);
}

/* ======================================================
   GET PARAMETERS
====================================================== */
$get_date         = $_GET['date_range'] ?? null;
$get_type         = $_GET['type'] ?? null;
$get_warehouse_id = $_GET['wh'] ?? null;
$search           = trim($_GET['search'] ?? '');

$is_ajax = isset($_GET['ajax']);

/* ======================================================
   PAGINATION
====================================================== */
$limit = 25;
$page  = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;

/* ======================================================
   BUILD THE SHARED WHERE CLAUSE
   (used by the COUNT query, the cursor/boundary lookup,
   and the main query - kept free of any JOIN that isn't
   needed for filtering, so none of these have to drag
   the users/warehouse tables along just to look up a page)
====================================================== */
$params = [];
$types  = "";
$where_parts = [];

/* -- Warehouse filter -- */
if (!empty($get_warehouse_id)) {
    $where_parts[] = "ol.warehouse = ?";
    $params[] = $get_warehouse_id;
    $types   .= "s";
} else {
    $warehouse_placeholders = implode(',', array_fill(0, count($user_warehouse_ids), '?'));
    $where_parts[] = "ol.warehouse IN ($warehouse_placeholders)";
    foreach ($user_warehouse_ids as $wid) {
        $params[] = $wid;
        $types   .= "s";
    }
}

/* -- Date filter -- */
if (!empty($get_date)) {
    if (strpos($get_date, "to") !== false) {
        list($start, $end) = explode(" to ", $get_date);
        $start_date = date("Y-m-d 00:00:01", strtotime(trim($start)));
        $end_date   = date("Y-m-d 23:59:59", strtotime(trim($end)));
    } else {
        $start_date = date("Y-m-d 00:00:01", strtotime($get_date));
        $end_date   = date("Y-m-d 23:59:59", strtotime($get_date));
    }
    $where_parts[] = "ol.date_sent BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types   .= "ss";
}

/* -- Search filter --------------------------------------------------
   Narrowed to what staff actually search by: Order #, Barcode,
   and (sometimes) Customer name. Dropped hashed_id / order_line_id /
   warehouse name / staff name - those were forcing a JOIN + extra
   LIKE comparisons on every row for fields nobody searches by.

   Order # and Barcode use a *prefix* match ("term%") instead of a
   "%term%" contains-match. A leading wildcard can't use an index,
   so on a 100k+ row table (outbound_content) it forces a full table
   scan on every single search - that's very likely a big chunk of
   the slowness you're seeing, independent of the pagination issue.
   Order numbers and barcodes are always typed/scanned in full from
   the start, so a prefix match still covers the real use case and
   lets MySQL use an index. Customer name stays a contains-match
   since people often only remember part of a name, and it's only
   checked against outbound_logs (not the 100k-row table).
---------------------------------------------------------------------- */
if ($search !== '') {
    $where_parts[] = "(
        ol.order_num LIKE ?
        OR ol.customer_fullname LIKE ?
        OR ol.hashed_id IN (
            SELECT oc2.hashed_id FROM outbound_content oc2
            WHERE oc2.unique_barcode LIKE ?
        )
    )";
    $params[] = $search . "%";
    $params[] = "%" . $search . "%";
    $params[] = $search . "%";
    $types   .= "sss";
}

$where_sql = $where_parts ? ("WHERE " . implode(" AND ", $where_parts)) : "";

/* ======================================================
   COUNT QUERY (no user/warehouse-name JOIN needed anymore,
   since search no longer filters on those)
====================================================== */
$count_sql = "SELECT COUNT(*) FROM outbound_logs ol $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($params) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_stmt->bind_result($total_rows);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = max(1, (int) ceil($total_rows / $limit));
if ($page > $total_pages) $page = $total_pages;

/* ======================================================
   KEYSET (CURSOR) PAGINATION
   ------------------------------------------------------
   Instead of LIMIT ? OFFSET ?, which forces MySQL to walk
   through and discard every row before the requested page
   (getting slower the deeper you page), we remember - per
   filter combination, in the session - the id boundary that
   starts each page the user has already reached. Moving to
   the next/previous page then becomes a plain "WHERE id < ?
   ORDER BY id DESC LIMIT 25", which is index-only regardless
   of how deep you are.

   Jumping to a page that was never visited in this session
   (e.g. typing ?page=400 directly) still needs a one-time
   lookup, but that lookup only touches ol.id (no joins, no
   row hydration) - and the result is cached, so revisiting
   that page (or paging on from it) is instant afterwards.
====================================================== */
$filter_signature = md5(json_encode([$get_date, $get_warehouse_id, $search, $user_warehouse_ids]));

if (!isset($_SESSION['outbound_cursors'])) {
    $_SESSION['outbound_cursors'] = [];
}
// Cap how many distinct filter combinations we cache per session
// (keeps session storage bounded if someone runs a lot of different searches)
if (!isset($_SESSION['outbound_cursors'][$filter_signature])) {
    if (count($_SESSION['outbound_cursors']) >= 5) {
        array_shift($_SESSION['outbound_cursors']);
    }
    $_SESSION['outbound_cursors'][$filter_signature] = [1 => null]; // page 1 needs no cursor
}
$cursor_cache = &$_SESSION['outbound_cursors'][$filter_signature];

$before_id = null;
if ($page > 1) {
    if (array_key_exists($page, $cursor_cache)) {
        $before_id = $cursor_cache[$page];
    } else {
        // Cold jump - locate the boundary id with a lightweight, join-free query
        $skip = ($page - 1) * $limit - 1;
        $boundary_sql = "SELECT ol.id FROM outbound_logs ol $where_sql ORDER BY ol.id DESC LIMIT 1 OFFSET " . (int) $skip;
        $boundary_stmt = $conn->prepare($boundary_sql);
        if ($params) $boundary_stmt->bind_param($types, ...$params);
        $boundary_stmt->execute();
        $boundary_stmt->bind_result($boundary_id);
        if ($boundary_stmt->fetch()) {
            $before_id = (int) $boundary_id;
        }
        $boundary_stmt->close();
        $cursor_cache[$page] = $before_id;
    }
}

/* ======================================================
   MAIN QUERY
====================================================== */
$main_params = $params;
$main_types  = $types;
$id_clause   = "";
if ($before_id !== null) {
    $id_clause = "AND ol.id < ?";
    $main_params[] = $before_id;
    $main_types   .= "i";
}
$main_params[] = $limit;
$main_types   .= "i";

$main_sql = "
    SELECT
        ol.*,
        u.user_fname,
        u.user_lname,
        w.warehouse_name
    FROM outbound_logs ol
    LEFT JOIN users u ON u.hashed_id = ol.user_id
    LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
    $where_sql
    $id_clause
    ORDER BY ol.id DESC
    LIMIT ?
";

$main_stmt = $conn->prepare($main_sql);
$main_stmt->bind_param($main_types, ...$main_params);
$main_stmt->execute();
$result = $main_stmt->get_result();

/* ======================================================
   STORE ROWS + CACHE THE NEXT PAGE'S CURSOR
====================================================== */
$outbound_rows = [];
$outbound_ids  = [];
while ($row = $result->fetch_assoc()) {
    $outbound_rows[] = $row;
    $outbound_ids[]  = $row['hashed_id'];
}
$main_stmt->close();

if (count($outbound_rows) === $limit) {
    $last_row = end($outbound_rows);
    $cursor_cache[$page + 1] = (int) $last_row['id'];
}
// Keep the cache from growing unbounded within one filter context
if (count($cursor_cache) > 60) {
    $cursor_cache = array_slice($cursor_cache, -60, null, true);
}

/* ======================================================
   BARCODE MAP (only for the ~25 rows on this page - cheap)
====================================================== */
$barcode_map = [];
if (!empty($outbound_ids)) {
    $placeholders = implode(',', array_fill(0, count($outbound_ids), '?'));
    $bc_sql = "SELECT hashed_id, unique_barcode FROM outbound_content WHERE hashed_id IN ($placeholders)";
    $bc_stmt = $conn->prepare($bc_sql);
    $bc_types = str_repeat("s", count($outbound_ids));
    $bc_stmt->bind_param($bc_types, ...$outbound_ids);
    $bc_stmt->execute();
    $bc_res = $bc_stmt->get_result();
    while ($row = $bc_res->fetch_assoc()) {
        $barcode_map[$row['hashed_id']][] = $row['unique_barcode'];
    }
    $bc_stmt->close();
}

/* ======================================================
   ROW RANGE
====================================================== */
$start_row = $total_rows === 0 ? 0 : ($page - 1) * $limit + 1;
$end_row   = min($page * $limit, $total_rows);

/* ======================================================
   RENDER - the "results" fragment (count line, table,
   pagination) is captured on its own so it can be sent back
   as-is for AJAX refreshes, without touching the search form
   around it (so the input never loses focus while typing).
====================================================== */
ob_start();
?>
<div class="px-3 mb-2">
    <small class="text-muted">
        <?php if ($total_rows === 0): ?>
            No results found.
        <?php else: ?>
            Showing <?= $start_row ?>–<?= $end_row ?> of <?= number_format($total_rows) ?> results
        <?php endif; ?>
    </small>
</div>

<div class="row justify-content-between gx-3 gy-0 px-3">
    <div class="table-responsive scrollbar">
        <table class="table mb-0 fs-10">
            <thead class="bg-200">
                <tr>
                    <th class="text-900 sort" data-sort="outbound_no">Outbound no.</th>
                    <th class="text-900 sort" data-sort="outbound_status">Fulfillment Status</th>
                    <th class="text-900 sort text-end" data-sort="order_no">Order #</th>
                    <th class="text-900 sort text-end" data-sort="order_line">Order Line ID</th>
                    <th class="text-900 sort" data-sort="warehouse">Warehouse</th>
                    <th class="text-900 sort" data-sort="date">Date</th>
                    <th class="text-900 sort" data-sort="receiver">Client</th>
                    <th class="text-900 sort" data-sort="outbounder">Staff</th>
                    <th class="d-none">Barcodes</th>
                </tr>
            </thead>
            <tbody class="list">
                <?php if (empty($outbound_rows)): ?>
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        No outbound records match your filters.
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($outbound_rows as $row):
                    $outbound_id        = $row['hashed_id'];
                    $outbound_warehouse = $row['warehouse_name'];
                    $outbound_date      = $row['date_sent'];
                    $outbound_receiver  = $row['customer_fullname'];
                    $order_no           = $row['order_num'];
                    $order_line         = $row['order_line_id'];
                    $outbounder         = trim(($row['user_fname'] ?? '') . " " . ($row['user_lname'] ?? ''));

                    $status_map = [
                        0 => '<span class="badge rounded-pill badge-subtle-success">Paid</span>',
                        1 => '<span class="badge rounded-pill badge-subtle-success">Paid w/ return</span><span class="badge rounded-pill badge-subtle-danger">-1</span>',
                        2 => '<span class="badge rounded-pill badge-subtle-danger">Returned</span>',
                        3 => '<span class="badge rounded-pill badge-subtle-danger">Void Requested</span>',
                        4 => '<span class="badge rounded-pill badge-subtle-primary">Voided</span>',
                        5 => '<span class="badge rounded-pill badge-subtle-danger">Void Rejected</span>',
                        6 => '<span class="badge rounded-pill badge-subtle-info">Outbounded</span>',
                    ];
                    $outbound_status = $status_map[$row['status']] ?? '<span class="badge rounded-pill badge-subtle-secondary">Unknown</span>';
                ?>
                <tr>
                    <td class="outbound_no">
                        <a type="button" data-bs-toggle="modal" data-bs-target="#view-modal" target-id="<?= htmlspecialchars($outbound_id) ?>">
                            <?= htmlspecialchars($outbound_id) ?>
                        </a>
                    </td>
                    <td class="outbound_status"><?= $outbound_status /* fixed set of safe HTML above, not user input */ ?></td>
                    <td class="order_no text-end"><?= htmlspecialchars((string) $order_no) ?></td>
                    <td class="order_line text-end"><?= htmlspecialchars((string) $order_line) ?></td>
                    <td class="warehouse"><?= htmlspecialchars((string) $outbound_warehouse) ?></td>
                    <td class="date"><?= htmlspecialchars((string) $outbound_date) ?></td>
                    <td class="receiver"><?= htmlspecialchars((string) $outbound_receiver) ?></td>
                    <td class="outbounder"><?= htmlspecialchars($outbounder) ?></td>
                    <td class="d-none">
                        <?= htmlspecialchars(isset($barcode_map[$outbound_id]) ? implode(",", $barcode_map[$outbound_id]) : '') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row mt-3 px-3">
    <div class="col">
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <?php
                $query = $_GET;
                unset($query['ajax']);
                $range = 2;
                $start = max(1, $page - $range);
                $end   = min($total_pages, $page + $range);

                if ($page > 1) {
                    $query['page'] = $page - 1;
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($query) . '">Previous</a></li>';
                }

                if ($start > 1) {
                    $query['page'] = 1;
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($query) . '">1</a></li>';
                    if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                for ($i = $start; $i <= $end; $i++) {
                    $query['page'] = $i;
                    $active = ($i == $page) ? "active" : "";
                    echo '<li class="page-item ' . $active . '"><a class="page-link" href="?' . http_build_query($query) . '">' . $i . '</a></li>';
                }

                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    $query['page'] = $total_pages;
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($query) . '">' . $total_pages . '</a></li>';
                }

                if ($page < $total_pages) {
                    $query['page'] = $page + 1;
                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($query) . '">Next</a></li>';
                }
                ?>
            </ul>
        </nav>
    </div>
</div>
<?php
$results_fragment = ob_get_clean();

/* ======================================================
   AJAX REQUEST -> just the fragment, nothing else
====================================================== */
if ($is_ajax) {
    echo $results_fragment;
    return;
}
?>

<div class="card">
    <div class="card-header bg-primary bg-gradient">
        <h2 class="text-white">Outbound Logs <?= htmlspecialchars((string) $get_date) ?></h2>
    </div>

    <div class="card-body overflow-hidden py-6 px-0">
        <!-- Search Form -->
        <div class="row">
            <div class="col-4 mb-3 mx-3">
                <form method="GET" id="outbound-search-form">
                    <input type="hidden" name="date_range" value="<?= htmlspecialchars((string) $get_date) ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars((string) $get_type) ?>">
                    <input type="hidden" name="wh" value="<?= htmlspecialchars((string) $get_warehouse_id) ?>">
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search"
                               placeholder="Search by Order #, Barcode, or Customer Name"
                               autocomplete="off"
                               value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-outline-secondary d-none" type="button" id="outbound-search-clear" title="Clear search">
                            <span class="fas fa-times"></span>
                        </button>
                        <button class="btn btn-primary" type="submit" id="outbound-search-submit">Search</button>
                    </div>
                    <div class="form-text">Searches Order #, Barcode, and Customer Name.</div>
                </form>
            </div>
        </div>

        <div id="outbound-results">
            <?= $results_fragment ?>
        </div>
    </div>
</div>

<!-- Modal for Viewing Content -->
<div class="modal fade" id="view-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div id="target-id"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php
if (isset($_GET['notnot'])) {
    // Prepared statement instead of manual real_escape_string + string-built query
    $notnot_stmt = $conn->prepare("SELECT `message` FROM `notification` WHERE SHA2(`id`, 256) = ?");
    $notnot_stmt->bind_param("s", $_GET['notnot']);
    $notnot_stmt->execute();
    $not_result = $notnot_stmt->get_result();

    $number = null;
    if ($not_result && $not_result->num_rows > 0) {
        $row = $not_result->fetch_assoc();
        $notification_message = $row['message'];
        if (preg_match('/\d+/', $notification_message, $matches)) {
            $number = $matches[0];
        }
    }
    $notnot_stmt->close();

    if ($number !== null) {
    ?>
    <!-- Auto trigger script -->
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const targetId = <?= json_encode($number) ?>;
        if (targetId) {
          const anchor = document.querySelector(`a[target-id="${targetId}"]`);
          if (anchor) {
            anchor.click();
          }
        }
      });
    </script>
    <?php
    }
}
?>

<script>
// Load content into modal on click
$(document).on("click", "a[data-bs-toggle='modal']", function() {
    var targetId = $(this).attr("target-id"); // Get unique key
    $("#target-id").load("form-content.php?id=" + targetId); // Load content
});

$(document).on('submit', '.void-form', function(e) {
    e.preventDefault(); // Stop default submission
    const $form = $(this);
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you really want to submit this form?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, submit it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: $form.attr('action'),
                type: $form.attr('method'),
                data: $form.serialize(),
                success: function(response) {
                    Swal.fire({
                        title: 'Success!',
                        text: response,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error!',
                        text: xhr.responseText || 'Something went wrong.',
                        icon: 'error'
                    });
                }
            });
        }
    });
});


$(document).on('submit', '.void-decision', function(e) {
    e.preventDefault(); // Prevent default form submission
    const $decisionForm = $(this);

    Swal.fire({
        title: 'Are you sure?',
        text: "Do you really want to submit this form?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, submit it!',
        cancelButtonText: 'Cancel'
    }).then((confirmation) => {
        if (confirmation.isConfirmed) {
            $.ajax({
                url: $decisionForm.attr('action'),
                type: $decisionForm.attr('method'),
                data: $decisionForm.serialize(),
                success: function(serverResponse) {
                    Swal.fire({
                        title: 'Success!',
                        text: serverResponse,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                },
                error: function(errorResponse) {
                    Swal.fire({
                        title: 'Error!',
                        text: errorResponse.responseText || 'Something went wrong.',
                        icon: 'error'
                    });
                }
            });
        }
    });
});

$(document).on("click", ".paid_btn", function () {
    const outboundId = $(this).data("targetid");

    Swal.fire({
        title: 'Mark as Paid?',
        text: "Are you sure you want to mark this as paid?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, mark as paid'
    }).then((result) => {
        if (result.isConfirmed) {
            $.get("../config/paid_outbound.php", { name: outboundId })
                .done(function (data) {
                    Swal.fire('Success!', 'The record has been marked as paid.', 'success');
                    window.location.href = window.location.href;
                })
                .fail(function () {
                    Swal.fire('Error!', 'There was a problem processing your request.', 'error');
                });
        }
    });
});

/* ======================================================
   AJAX SEARCH + PAGINATION
   ------------------------------------------------------
   Only the #outbound-results panel is ever replaced - the
   search form/input above it is never touched by innerHTML,
   so the input never loses focus or cursor position while
   the user is typing.

   NOTE: this table's <tbody> has class="list" and the <th>
   cells carry data-sort attributes, which usually means a
   library like List.js is wired up to it elsewhere in your
   layout (header.php / footer_main.php, which weren't part
   of what I reviewed). If that's the case here, it will need
   to be re-indexed after each AJAX swap below (list.reIndex()
   or equivalent) or its client-side sort/search will go stale
   against the old rows. Send me those includes and I'll wire
   it in properly - for now this degrades gracefully to a full
   page reload if anything above throws.
====================================================== */
(function () {
    const $panel  = $('#outbound-results');
    const $form   = $('#outbound-search-form');
    const $search = $('#search');
    const $clear  = $('#outbound-search-clear');
    const $submit = $('#outbound-search-submit');
    let searchTimer = null;
    let currentRequest = null;

    function toggleClearButton() {
        $clear.toggleClass('d-none', $search.val().length === 0);
    }
    toggleClearButton();

    function loadResults(page) {
        const params = $form.serializeArray();
        params.push({ name: 'page', value: page });

        const queryString = $.param(params);

        if (currentRequest) currentRequest.abort();
        $panel.css('opacity', 0.5);
        $submit.prop('disabled', true);

        currentRequest = $.get('content.php?' + queryString + '&ajax=1')
            .done(function (html) {
                $panel.html(html);
                history.pushState(null, '', '?' + queryString);
            })
            .fail(function (xhr) {
                if (xhr.statusText === 'abort') return;
                // Fall back to a normal navigation if the AJAX call fails
                window.location.href = '?' + queryString;
            })
            .always(function () {
                $panel.css('opacity', 1);
                $submit.prop('disabled', false);
            });
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        clearTimeout(searchTimer);
        loadResults(1);
    });

    $search.on('keyup', function () {
        toggleClearButton();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            loadResults(1);
        }, 350);
    });

    $clear.on('click', function () {
        $search.val('');
        toggleClearButton();
        clearTimeout(searchTimer);
        loadResults(1);
    });

    $(document).on('click', '#outbound-results .pagination a', function (e) {
        e.preventDefault();
        const url = new URL(this.href, window.location.origin);
        const page = url.searchParams.get('page') || 1;
        clearTimeout(searchTimer);
        loadResults(page);
    });
})();
</script>
