<?php 
include "../config/database.php";
include "../config/on_session.php";

$file = 'assorted-items.csv';

if (!file_exists($file)) {
    die("File not found.");
}

?>
<?php
if (($handle = fopen($file, "r")) !== FALSE) {

    // Get header row
    $headers = fgetcsv($handle);

    // Map CSV headers → clean variable keys
    $headerMap = [
        'DESCRIPTION' => 'description',
        'Keyword'     => 'keyword',
        'Qty'         => 'qty',
        'Price'       => 'price',
        'Supplier'    => 'supplier',
        'BARCODE'     => 'barcode',
        'Batch code'  => 'batch_code',
        'Brand'       => 'brand',
        'Category'    => 'category',
        'Safety'      => 'safety'
    ];

    while (($row = fgetcsv($handle)) !== FALSE) {

        // Combine headers with row values
        $data = array_combine($headers, $row);

        // Assign to variables (easy to modify later)
        $description = $data['DESCRIPTION'] ?? '';
        $keyword     = $data['Keyword'] ?? '';
        $qty         = $data['Qty'] ?? 0;
        $price       = $data['Price'] ?? 0;
        $supplier    = $data['Supplier'] ?? '';
        $barcode     = $data['BARCODE'] ?? '';
        $batch_code  = $data['Batch code'] ?? '';
        $brand       = $data['Brand'] ?? '';
        $category    = $data['Category'] ?? '';
        $safety      = $data['Safety'] ?? '';

        $sql = "SELECT p.description, p.keyword, s.capital, sup.supplier_name, s.unique_barcode, s.batch_code, b.brand_name, c.category_name, p.safety
                FROM product p
                LEFT JOIN brand b ON p.brand = b.hashed_id
                LEFT JOIN category c ON p.category = c.hashed_id
                LEFT JOIN stocks s ON p.hashed_id = s.product_id
                LEFT JOIN supplier sup ON s.supplier = sup.hashed_id
                WHERE s.unique_barcode = '$barcode' LIMIT 1";
        $result = $conn->query($sql);
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $product_description = $row['description'];
            $product_keyword = $row['keyword'];
            $product_capital = $row['capital'];
            $product_supplier = $row['supplier_name'];  
            $product_barcode = $row['unique_barcode'];
            $product_batch_code = $row['batch_code'];
            $product_brand = $row['brand_name'];
            $product_category = $row['category_name'];
            $product_safety = $row['safety'];
            
        } else {
            $product_description = $description;
            $product_keyword = $keyword; 
            $product_capital = $price;
            $product_supplier = $supplier;
            $product_barcode = $barcode;
            $product_batch_code = $batch_code;
            $product_brand = $brand;
            $product_category = $category;
            $product_safety = $safety;
        }

    
            echo $product_description . ","; 
            echo $product_keyword . ","; 
    
            echo $product_capital . ",";
            echo $product_supplier . ",";
            echo $product_barcode . ",";
            echo $product_batch_code . ",";
            echo $product_brand . ",";
            echo $product_category . ",";
            echo $product_safety . "<br>";

        

        // // Debug (optional)
        // echo "<pre>";
        // print_r([
        //     'description' => $description,
        //     'keyword' => $keyword,
        //     'qty' => $qty,
        //     'price' => $price,
        //     'supplier' => $supplier,
        //     'barcode' => $barcode,
        //     'batch_code' => $batch_code,
        //     'brand' => $brand,
        //     'category' => $category,
        //     'safety' => $safety
        // ]);
        // echo "</pre>";
    }

    fclose($handle);
}
?>