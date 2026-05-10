<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "../config/database.php";
include "../config/on_session.php";

$batch_code = $_GET['target_id'] ?? '';
$product_id = $_GET['targetPId'];
$warehouse = $_GET['warehouseID'];
$search = $_GET['search'] ?? ''; // <-- NEW
$limit = 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

if (!empty($batch_code)) {
    $search_sql = "";
    $params = [$batch_code, $product_id, $warehouse];
    $types = "sss";

    if (!empty($search)) {
        $search_sql = " AND s.unique_barcode LIKE ?";
        $params[] = "%" . $search . "%";
        $types .= "s";
    }

    $query = "SELECT DISTINCT
            s.unique_barcode, 
            s.item_status, 
            il.location_name, 
            s.capital, 
            ol.sold_price 
        FROM stocks s 
        LEFT JOIN item_location il ON il.id = s.item_location 
        LEFT JOIN (
            SELECT o1.*
            FROM outbound_content o1
            INNER JOIN (
                SELECT unique_barcode, MAX(id) as max_id
                FROM outbound_content
                GROUP BY unique_barcode
            ) o2 ON o1.unique_barcode = o2.unique_barcode AND o1.id = o2.max_id
        ) ol ON ol.unique_barcode = s.unique_barcode 
        WHERE s.batch_code = ? AND s.product_id = ? AND s.warehouse = ? 
        $search_sql
        ORDER BY s.barcode_extension ASC 
        LIMIT ?, ?";

    $params[] = $offset;
    $params[] = $limit;
    $types .= "ii";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        $output = "";

        while ($row = $res->fetch_assoc()) {
            $location_name = !empty($row['location_name']) 
                ? '<span class="badge bg-primary">' . htmlspecialchars($row['location_name']) . '</span>'
                : '<span class="badge bg-warning text-dark">For SKU</span>';

            $status_map = [
                0 => 'success|Available',
                1 => 'danger|Sold',
                2 => 'primary|Enroute',
                3 => 'warning|For Enroute'
            ];
            $status = $status_map[$row['item_status']] ?? 'warning|Returned';
            list($status_class, $status_text) = explode('|', $status);

            if ($user_position_name === "Administrator" || strpos($access, "stock") !== false) {
                $output .= "
                    <tr>
                        <td>
                            <a href='../Product-info/?prod=" . htmlspecialchars($row['unique_barcode']) . "' target='_blank' rel='noopener noreferrer' class='text-decoration-none text-dark'>
                                <small>LPO " . htmlspecialchars($row['unique_barcode']) . "</small>
                            </a>
                        </td>
                        <td class='text-center'>
                            <span class='badge bg-{$status_class}'>{$status_text}</span>
                        </td>
                        <td class='text-end'><small>" . $row['capital'] . "</small></td>
                        <td class='text-end'><small>" . $row['sold_price'] . "</small></td>
                        <td>" . $location_name . "</td>
                    </tr>
                ";
            } else {
                $output .= "
                    <tr>
                        <td>
                            <a href='#' class='text-decoration-none text-dark'>
                                <small>LPO " . htmlspecialchars($row['unique_barcode']) . "</small>
                            </a>
                        </td>
                        <td class='text-center' colspan='3'>
                            <span class='badge bg-{$status_class}'>{$status_text}</span>
                        </td>
                        <td>" . $location_name . "</td>
                    </tr>
                ";
            }
        }

        $has_more = $res->num_rows >= $limit ? 1 : 0;

        echo json_encode(["html" => $output, "has_more" => $has_more]);

        $stmt->close();
    } else {
        echo json_encode(["html" => "<tr><td colspan='5' class='text-center text-danger'>Error preparing query.</td></tr>", "has_more" => 0]);
    }
} else {
    echo json_encode(["html" => "<tr><td colspan='5' class='text-center text-danger'>No batch code provided.</td></tr>", "has_more" => 0]);
}
?>
