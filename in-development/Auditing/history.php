<?php
include "../config/database.php";
include "../config/on_session.php";

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$searchClause = '';
if (!empty($search)) {
    $safeSearch = $conn->real_escape_string($search);
    $searchClause = "AND filename LIKE '%$safeSearch%'";
}

$query = "SELECT id, filename, date FROM csv_auditing 
          WHERE user_id = '$user_id' AND status = 2 $searchClause 
          ORDER BY date DESC 
          LIMIT $offset, $limit";

$result = $conn->query($query);
$rows = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = '
        <tr>
            <td><a href="download_csv.php?id=' . $row['id'] . '" target="_blank">' . htmlspecialchars($row['filename']) . '</a></td>
            <td>' . htmlspecialchars($row['date']) . '</td>
        </tr>';
    }
}

header('Content-Type: application/json');
echo json_encode($rows);
?>
