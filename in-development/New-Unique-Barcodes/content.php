<?php
$unique_key = $_SESSION['unique_key'] ?? null;

if (!$unique_key) {
    echo "<div class='alert alert-danger'>Session expired or invalid. Please try again.</div>";
    exit;
}
?>
<div class="card mb-1">
    <div class="card-header bg-warning">
        <h2 class="text-white">Download Barcode/s</h2>
        <div class="row mb-3">
            <div class="col-12 text-end">
                <a class="btn btn-primary btn-sm mt-3" href="../set-item-locations/"><span class="fas fa-home me-2"></span>Set item locations.</a>
            </div>
        </div>
    </div>
    <div class="card-body overflow-hidden">
        <div class="row">
            <div class="col-lg-12">
                <div class="table-responsive">
                    <table class="table table-sm fs-10">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Sequence</th>
                                <th>Parent Barcode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Query to fetch the required data
                            $query = "SELECT 
                                        p.description,
                                        p.parent_barcode,
                                        b.brand_name,
                                        c.category_name
                                    FROM product p
                                    LEFT JOIN stocks s ON s.product_id = p.hashed_id
                                    LEFT JOIN brand b ON b.hashed_id = p.brand
                                    LEFT JOIN category c ON c.hashed_id = p.category
                                    WHERE s.unique_key = '$unique_key'
                                    GROUP BY p.parent_barcode
                            ";

                            $result = $conn->query($query);

                            // Group data by parent barcode
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $parentBarcode = $row['parent_barcode'];
                                    $brandName = $row['brand_name'];
                                    $categoryName = $row['category_name'];
                                    $description = $row['description'] . " / " . $brandName . " / " . $categoryName;
                                   

                                    $start_query = "SELECT COUNT(parent_barcode) AS item_qty, barcode_extension FROM stocks WHERE unique_key = '$unique_key' AND parent_barcode = '$parentBarcode' ORDER BY barcode_extension DESC LIMIT 1";
                                    $start_res = $conn->query($start_query);
                                    if($start_res->num_rows>0){
                                        $row=$start_res->fetch_assoc();
                                        $totalCount = $row['item_qty'];
                                        $barcode_extension = $row['barcode_extension'];
                                    } else {
                                        $totalCount = 0;
                                        $barcode_extension = 0;
                                    }


                                    $batch_size = 100;
                                    $max = $barcode_extension + $totalCount -1;
                                    for($start=$barcode_extension; $start <= $max; $start += $batch_size){
                                        $end = min($batch_size + $start -1, $max);
                                        echo "<tr>
                                                <td><a href='../config/generate-uniquebarcodes.php?success=0&barcode={$parentBarcode}&start={$start}&end={$end}'><span class='fas fa-download'></span> {$description}</a></td>
                                                <td>{$start}-{$end}</td>
                                                <td>{$parentBarcode}</td>
                                            </tr>";
                                    }
                                    
                                }
                            } else {
                                echo "<tr><td colspan='3'>tabnasda</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>    
    </div>
</div>
