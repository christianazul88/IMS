<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "../config/database.php";

$batch_code = $_GET['target_id'] ?? '';
$limit = 100; // Load 100 records per request
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

if (!empty($batch_code)) {
    $query = "SELECT 
                s.unique_barcode, 
                s.item_status, 
                il.location_name, 
                s.capital, 
                ol.sold_price 
              FROM stocks s 
              LEFT JOIN item_location il ON il.id = s.item_location 
              LEFT JOIN outbound_content ol ON ol.unique_barcode = s.unique_barcode 
              WHERE s.batch_code = ? 
              ORDER BY s.barcode_extension ASC 
              LIMIT ?, ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("sii", $batch_code, $offset, $limit);
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

            $output .= "
                <tr>
                    <td>
                        <a href='../Product-info/?prod=" . htmlspecialchars($row['unique_barcode']) . "' class='text-decoration-none text-dark'>
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
        }

        // Check if more data exists
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
