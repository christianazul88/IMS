<?php
include "../config/database.php";
include "../config/on_session.php";


if ($_FILES['csv']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['csv']['tmp_name'];
    $rows = file($file);

    if (!$rows) {
        echo 'Failed to read the CSV file.';
        exit;
    }
    ?>

    <table class="table table-sm bordered-table">
        <thead>
            <tr>
                <th>OUTBOUND ID</th>
                <th>WAREHOUSE</th>
                <th>ID</th>
                <th>HASHED ID</th>
                <th>CUSTOMER</th>
                <th>ORDER NUMBER</th>
                <th>ORDER LINE ID</th>
                <th>PLATFORM</th>
                <th>COURIER</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $bg = "table-secondary";
            for ($i = 1; $i < count($rows); $i++){
                $line = trim($rows[$i]);

                $data = str_getcsv($line);

                // Assign variables to columns
                $outbound_id   = $data[0] ?? '';
                $warehouse     = $data[1] ?? '';
                $id            = $data[2] ?? '';
                $hashed_id     = $data[3] ?? '';
                $customer      = $data[4] ?? '';
                $order_number  = $data[5] ?? '';
                $order_line_id = $data[6] ?? '';
                $platform      = $data[7] ?? '';
                $courier       = $data[8] ?? '';

                if(!empty($outbound_id) && $outbound_id !== 'Barcode' && !empty($warehouse) && $warehouse !== 'Product' && !empty($id) && $id !== 'Category' && !empty($hashed_id) && $hashed_id !== 'Brand' && !empty($customer) && $customer !== 'Sold Price' && empty($order_number) && empty($order_line_id) && empty($platform) && empty($courier)){
                    $session_outbound_id = $_SESSION['out_id'];
                    $session_out_warehouse_name = $_SESSION['out_warehouse'];
                    $session_out_identity = $_SESSION['out_identity'];
                    $session_outhashed_id = $_SESSION['outhashed_id'];
                    $session_customer = $_SESSION['customer'];
                    $session_order_no = $_SESSION['order_no'];
                    $session_order_line = $_SESSION['order_line']; 
                    $session_out_platform = $_SESSION['out_platform'];
                    $session_courier = $_SESSION['out_courier'];
                    ?>
                    <tr class="<?php echo $bg;?>">
                        <td>
                            <input type="text" name="outbound_id[]" value="<?php echo $session_outbound_id; ?>" readonly hidden>
                            <input type="text" name="warehouse_name[]" value="<?php echo $session_out_warehouse_name;?>" readonly hidden>
                            <input type="text" name="id[]" value="<?php echo $session_out_identity;?>">
                            <input type="text" name="hashed_id[]" value="<?php echo $session_outhashed_id;?>">
                            <input type="text" name="customer[]" value="<?php echo $session_customer;?>" readonly hidden>
                            <input type="text" name="order_no[]" value="<?php echo $session_order_no;?>" readonly hidden>
                            <input type="text" name="order_line_id[]" value="<?php echo $session_order_line;?>" readonly hidden>
                            <input type="text" name="platform[]" value="<?php echo $session_out_platform;?>">
                            <input type="text" name="courier[]" value="<?php echo $session_courier;?>">
                            <input type="text" name="barcode[]" value="<?php echo $outbound_id;?>" readonly hidden>
                            <input type="text" name="sold_amount[]" value="<?php echo $customer;?>" readonly hidden>
                            <?php echo $outbound_id; ?>
                        </td>
                        <td><?php echo $warehouse; ?></td>
                        <td><?php echo $id; ?></td>
                        <td><?php echo $hashed_id; ?></td>
                        <td><?php echo $customer; ?></td>
                        <td><?php echo $order_number; ?></td>
                        <td><?php echo $order_line_id; ?></td>
                        <td><?php echo $platform; ?></td>
                        <td><?php echo $courier; ?></td>
                    </tr>
                    <?php
                } elseif($outbound_id !== '' && $warehouse !== '' && $id !== '' && $hashed_id !== '' && $customer !== '' && $outbound_id !== 'Outbound ID' && $warehouse !== 'Warehouse' && $id !== 'ID' && $hashed_id !== 'Hashed ID' && $customer !== 'Customer' && $order_number !== 'Order #' && $order_line_id !== 'Order Line ID' && $platform !== 'Platform' && $courier !== 'Courier' && !($outbound_id === 'Barcode' && $warehouse === 'Product' && $id === 'Category' && $hashed_id === 'Brand' && $customer === 'Sold Price')){
                    $check_if_first = "";
                    if(!isset($_SESSION['out_id']) && !isset($_SESSION['customer']) && !isset($_SESSION['order_no']) && !isset($_SESSION['order_line'])){
                        $_SESSION['out_id'] = $outbound_id;
                        $_SESSION['out_warehouse'] = $warehouse;
                        $_SESSION['out_identity'] = $id;
                        $_SESSION['outhashed_id'] = $hashed_id;
                        $_SESSION['customer'] = $customer;
                        $_SESSION['order_no'] = $order_number;
                        $_SESSION['order_line'] = $order_line_id;
                        $_SESSION['out_platform'] = $platform;
                        $_SESSION['out_courier'] = $courier;
                    } else {
                        if($outbound_id !== $_SESSION['out_id'] && $customer !== $_SESSION['customer'] && $order_number !== $_SESSION['order_no'] && $order_line_id !== $_SESSION['order_line']){
                            $_SESSION['out_id'] = $outbound_id;
                            $_SESSION['out_identity'] = $id;
                            $_SESSION['outhashed_id'] = $hashed_id;
                            $_SESSION['customer'] = $customer;
                            $_SESSION['order_no'] = $order_number;
                            $_SESSION['order_line'] = $order_line_id;
                            $_SESSION['out_platform'] = $platform;
                            $_SESSION['out_courier'] = $courier;

                            if($bg === "table-secondary"){
                                $bg = "table-warning";
                            } elseif($bg === "table-warning"){
                                $bg = "table-secondary";
                            }
                        }
                    }
                    
                    ?>
                    <tr class="<?php echo $bg;?>">
                        <td><?php echo $outbound_id; ?></td>
                        <td><?php echo $warehouse; ?></td>
                        <td><?php echo $id; ?></td>
                        <td><?php echo $hashed_id; ?></td>
                        <td><?php echo $customer; ?></td>
                        <td><?php echo $order_number; ?></td>
                        <td><?php echo $order_line_id; ?></td>
                        <td><?php echo $platform; ?></td>
                        <td><?php echo $courier; ?></td>
                    </tr>
                <?php
                }
                
            }
            ?>
        </tbody>
    </table>

<?php
} else {
    echo 'Error uploading the file.';
}
?>
