<?php
function formatNumber($num) {
    if ($num >= 1000000000) { // Billions
        return round($num / 1000000000, 1) . 'B';
    } elseif ($num >= 1000000) { // Millions
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) { // Thousands
        return round($num / 1000, 1) . 'K';
    }
    return $num; // Keep as-is if less than 1000
}

$monthly_outbound_query = "
    SELECT 
        DATE_FORMAT(date_sent, '%b') AS month_name,
        COUNT(hashed_id) AS outbound_count
    FROM outbound_logs 
    WHERE date_sent >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 3 MONTH), '%Y-%m-01')
    AND warehouse IN ($imploded_warehouse_ids)
    GROUP BY YEAR(date_sent), MONTH(date_sent)
    ORDER BY YEAR(date_sent) DESC, MONTH(date_sent) DESC
";

$monthly_outbound_res = $conn->query($monthly_outbound_query);

$monthly_outbound = [];
$monthly_outbound_count = [];

// Store results in associative arrays
if ($monthly_outbound_res->num_rows > 0) {
    while ($row = $monthly_outbound_res->fetch_assoc()) {
        $monthly_outbound[$row['month_name']] = $row['outbound_count'];
    }
}

// Ensure all four months are included, even if missing from DB
$months = [];
$counts = [];
for ($i = 3; $i >= 0; $i--) {
    $month_name = date('M', strtotime("-$i months"));
    $months[] = "\"$month_name\"";  // Add quotes for PHP array format
    $counts[] = isset($monthly_outbound[$month_name]) ? $monthly_outbound[$month_name] : 0;
}

// Store as PHP variables in required format
$monthly_outbound = implode(',', $months);
$monthly_outbound_count = implode(',', $counts);

// Assign individual variables
list($previous3_month, $previous2_month, $previous_month, $current_month) = $months;
list($previous3_count, $previous2_count, $previous_count, $current_count) = $counts;

// Convert counts to K/M/B format
$current_count_fmt = formatNumber($current_count);
// $previous_count_fmt = formatNumber($previous_count);
// $previous2_count_fmt = formatNumber($previous2_count);
// $previous3_count_fmt = formatNumber($previous3_count);

// Output for debugging (can be removed)
// echo "\$monthly_outbound = $monthly_outbound;<br>";
// echo "\$monthly_outbound_count = $monthly_outbound_count;<br>";

// echo "\$current_month = $current_month;<br>";
// echo "\$current_count = $current_count;<br>";
// echo "\$current_count_fmt = $current_count_fmt;<br>";
// echo "\$previous_month = $previous_month;<br>";
// echo "\$previous_count = $previous_count;<br>";
// echo "\$previous_count_fmt = $previous_count_fmt;<br>";
// echo "\$previous2_month = $previous2_month;<br>";
// echo "\$previous2_count = $previous2_count;<br>";
// echo "\$previous2_count_fmt = $previous2_count_fmt;<br>";
// echo "\$previous3_month = $previous3_month;<br>";
// echo "\$previous3_count = $previous3_count;<br>";
// echo "\$previous3_count_fmt = $previous3_count_fmt;<br>";

// Calculate percentage change
if ($previous_count > 0) {
  $outbound_change = (($current_count - $previous_count) / $previous_count) * 100;
} else {
  // If previous week sales are zero and current week has sales, it's a 100% increase
  // If both are zero, it's a 0% change
  $outbound_change = $current_count > 0 ? 100 : 0;
}

// Display percentage change
$monthly_outbound_percentage =  ($outbound_change >= 0 ? "+" : "") . number_format($outbound_change, 2);
if(strpos($monthly_outbound_percentage, "+")!==false){
  $formatted_monthly_outbound_percentage = '<span class="badge rounded-pill fs-11 bg-200 text-primary"><span class="fas fa-caret-up me-1"></span>' . $monthly_outbound_percentage . '</span>' ;
} else {
  $formatted_monthly_outbound_percentage = '<span class="badge rounded-pill fs-11 bg-200 text-danger"><span class="fas fa-caret-down me-1"></span>' . $monthly_outbound_percentage . '</span>' ;
}

?>

<div class="col-md-6">
    <div class="card h-md-100">
        <div class="card-header pb-0">
            <h6 class="mb-0 mt-2">
                Total Outbound (<?php echo date("M"); ?>)
            </h6>
        </div>
        <div class="card-body d-flex flex-column justify-content-end">
            <div class="row justify-content-between">
                <div class="col-auto align-self-end">
                    <div class="fs-5 fw-normal font-sans-serif text-700 lh-1 mb-1">
                        <?php echo $current_count_fmt;?>
                    </div>
                    <?php echo $formatted_monthly_outbound_percentage;?>
                </div>
                <div class="col-auto ps-0 mt-n4">
                    <div class="echart-default-total-order" 
                        data-echarts='{
                            "tooltip": {
                                "trigger": "axis",
                                "formatter": "{b0} : {c0}"
                            },
                            "xAxis": {
                                "data": [<?php echo $monthly_outbound;?>]
                            },
                            "series": [{
                                "type": "line",
                                "data": [<?php echo $monthly_outbound_count;?>],
                                "smooth": true,
                                "lineStyle": {
                                    "width": 3
                                }
                            }],
                            "grid": {
                                "bottom": "2%",
                                "top": "2%",
                                "right": "10px",
                                "left": "10px"
                            }
                        }' 
                        data-echart-responsive="true">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
