<?php 
// Initialize sales data
$sales = [
    "previous_week" => 0,
    "current_week" => 0
];

// Get start and end of current and previous week (Sunday - Saturday)
$current_week_start = strtotime("last sunday");
$current_week_end = strtotime("next saturday midnight") - 1;

$previous_week_start = strtotime("-1 week last sunday");
$previous_week_end = strtotime("last sunday midnight") - 1;


// Debug timestamps
// echo "Previous Week: " . date("Y-m-d H:i:s", $previous_week_start) . " to " . date("Y-m-d H:i:s", $previous_week_end) . "\n";
// echo "Current Week: " . date("Y-m-d H:i:s", $current_week_start) . " to " . date("Y-m-d H:i:s", $current_week_end) . "\n";

$sql = "SELECT ol.hashed_id, ol.date_sent FROM outbound_logs ol ORDER BY ol.date_sent DESC";
$res = $conn->query($sql);

if (!$res) {
    die("SQL Error (outbound_logs): " . $conn->error);
}

while ($row = $res->fetch_assoc()) {
    $outbound_id = $row['hashed_id'];
    $outbound_out = strtotime($row['date_sent']);

    // Debugging: Print each row's date
    // echo "Outbound Date: " . $row['date_sent'] . " (" . date("Y-m-d H:i:s", $outbound_out) . ")\n";

    // Determine which week this sale belongs to
    if ($outbound_out >= $previous_week_start && $outbound_out <= $previous_week_end) {
        $week_key = "previous_week";
    } elseif ($outbound_out >= $current_week_start && $outbound_out <= $current_week_end) {
        $week_key = "current_week";
    } else {
        continue;
    }

    $query = "SELECT oc.sold_price
              FROM outbound_content oc
              LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
              LEFT JOIN product p ON p.hashed_id = s.product_id
              WHERE oc.hashed_id = '$outbound_id'";

    $result = $conn->query($query);

    if (!$result) {
        die("SQL Error (outbound_content): " . $conn->error);
    }

    while ($prod = $result->fetch_assoc()) {
        $sales[$week_key] += $prod['sold_price'];
    }
}

// Display results
$previous_week = number_format($sales['previous_week'], 2);
$current_week = number_format($sales['current_week'], 2);

// Function to format numbers into K, M, B
function formatAmount($number) {
    if ($number >= 1_000_000_000) {
        return number_format($number / 1_000_000_000, 2) . "B";
    } elseif ($number >= 1_000_000) {
        return number_format($number / 1_000_000, 2) . "M";
    } elseif ($number >= 1_000) {
        return number_format($number / 1_000, 2) . "K";
    }
    return number_format($number, 2);
}

// Create new variables for formatted sales values
$formatted_current_week = formatAmount($sales['current_week']); // Use the raw numeric value


// Calculate percentage change
if ($sales['previous_week'] > 0) {
    $change = (($sales['current_week'] - $sales['previous_week']) / $sales['previous_week']) * 100;
} else {
    // If previous week sales are zero and current week has sales, it's a 100% increase
    // If both are zero, it's a 0% change
    $change = $sales['current_week'] > 0 ? 100 : 0;
}

// Display percentage change
$weekly_sales_percentage =  ($change >= 0 ? "+" : "") . number_format($change, 2);
if(strpos($weekly_sales_percentage, "+")!==false){
    $formatted_weekly_sales_percentage = '<span class="badge badge-subtle-success rounded-pill fs-11">' . $weekly_sales_percentage . '%</span>';
} else {
    $formatted_weekly_sales_percentage = '<span class="badge badge-subtle-danger rounded-pill fs-11">' . $weekly_sales_percentage . '%</span>';
}
?>
<div class="col-md-6">
    <div class="card h-md-100 ecommerce-card-min-width">
    <div class="card-header pb-0">
        <h6 class="mb-0 mt-2 d-flex align-items-center">Weekly Sales<span class="ms-1 text-400" data-bs-toggle="tooltip" data-bs-placement="top" title="Calculated according to last week's sales"><span class="far fa-question-circle" data-fa-transform="shrink-1"></span></span></h6>
    </div>
    <div class="card-body d-flex flex-column justify-content-end">
        <div class="row">
        <div class="col">
            <p class="font-sans-serif lh-1 mb-1 fs-7">₱<?php echo $formatted_current_week;?></p><?php echo $formatted_weekly_sales_percentage;?>
        </div>
        <div class="col-auto ps-0">
            <div class="echart-bar-weekly-sales h-100 echart-bar-weekly-sales-smaller-width"></div>
        </div>
        </div>
    </div>
    </div>
</div>