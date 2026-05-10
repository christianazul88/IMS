<?php
include "../config/database.php";

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = 20;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$sql = "SELECT 
            fa.id, 
            fa.order_number, 
            fa.order_line_id, 
            fa.client, 
            fa.expected_amount, 
            fa.warehouse,
            fa.date,
            fa.status,
            u.user_fname, 
            u.user_lname
        FROM finance_audit fa
        LEFT JOIN users u ON u.hashed_id = fa.user_id
        WHERE fa.status != 'PAID' ";
if ($search !== '') {
    $sql .= "AND
                ( 
                fa.order_number LIKE '%$search%' 
                OR fa.order_line_id LIKE '%$search%'
                OR fa.client LIKE '%$search%'
                OR fa.warehouse LIKE '%$search%'
                OR u.user_fname LIKE '%$search%'
                OR u.user_lname LIKE '%$search%'
                OR CONCAT(u.user_fname, ' ', u.user_lname) LIKE '%$search%'
                OR fa.status LIKE '%$search%'
                OR fa.expected_amount LIKE '%$search%')";

}
$sql .= " ORDER BY fa.date DESC LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);
$rows = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format status badge
        $status = strtoupper($row['status']); // make sure status is consistent
        $statusBadgeClass = 'badge-secondary'; // default
        if ($status === 'PAID') $statusBadgeClass = 'badge-subtle-success';
        elseif ($status === 'UNPAID') $statusBadgeClass = 'badge-subtle-danger';
        elseif ($status === 'PENDING') $statusBadgeClass = 'badge-subtle-warning';

        // Format date
        $formattedDate = date("M j, Y", strtotime($row['date']));

        // Build row
        $rows[] = '
            <tr>
                <td class="fs-11">
                    <button class="btn btn-primary fs-11 view-details-btn" type="button" data-bs-toggle="modal" target-id="' . $row['order_number'] . '" data-bs-target="#staticBackdrop">Details</button>
                </td>
                <td class="fs-11 text-end">' . $row['order_number'] . '</td>
                <td class="fs-11 text-end">' . $row['order_line_id'] . '</td>
                <td class="fs-11">' . $row['client'] . '</td>
                <td class="fs-11">' . $row['warehouse'] . '</td>
                <td class="fs-11">' . $row['user_fname'] . ' ' . $row['user_lname'] . '</td>
                <td class="fs-11">' . $formattedDate . '</td>
                <td class="fs-11 text-center"><span class="badge rounded-pill ' . $statusBadgeClass . '">' . $status . '</span></td>
                <td class="fs-11 text-end">' . $row['expected_amount'] . '</td>
            </tr>
        ';
    }
}


echo json_encode($rows);
?>
