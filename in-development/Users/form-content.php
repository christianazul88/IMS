<?php 
include "../config/database.php";
if(isset($_GET['id'])){
    $id = $_GET['id'];
}
$requery_modals = "SELECT hashed_id, user_position, warehouse_access FROM users WHERE hashed_id = '$id' LIMIT 1";
$result = $conn->query($requery_modals);
if($result->num_rows>0){
    $row=$result->fetch_assoc();
        $modal_id = $row['hashed_id'];
        $position_id = $row['user_position'];
        $staff_wh_access = $row['warehouse_access']; // Sample data: "asdasd, asdasdqwe, qweqdasda, asdasdasd"

        
        ?>
        <input type="text" name="user_id" value="<?php echo $modal_id;?>" hidden>
                                <label class="col-form-label" for="recipient-name">Position</label>
                                <select class="form-select" name="position" id="" required>
                                    <option value="">Select Position</option>
                                    <?php 
                                    $employee_position_query = "SELECT * FROM user_position ORDER BY position_name ASC";
                                    $epq_result = $conn->query($employee_position_query);
                                    if($epq_result->num_rows>0){
                                        while($row=$epq_result->fetch_assoc()){
                                            $position_selection = $row['position_name'];
                                            $position_selection_id = $row['hashed_id'];
                                            if($position_selection_id === $position_id){
                                                echo '<option value="' . $position_selection_id . '" selected>' . $position_selection . '</option>';
                                            } else {
                                                echo '<option value="' . $position_selection_id . '">' . $position_selection . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                                </div>
                                <div class="mb-3">
                                <label class="col-form-label" for="message-text">Message:</label>
                                <div class="row">
                                    <?php 
                                    $warehouse_access_queries = "SELECT hashed_id, warehouse_name FROM warehouse";
                                    $waq_result = $conn->query($warehouse_access_queries);
                                    if($waq_result->num_rows>0){
                                        while($row=$waq_result->fetch_Assoc()){
                                            $warehouse_selection_id = $row['hashed_id'];
                                            $warehouse_selection_name = $row['warehouse_name'];
                                    ?>
                                    <div class="col-4">
                                        <div class="form-check">
                                            <input class="form-check-input" name="warehouse_access[]" id="<?php echo $warehouse_selection_id;?>" type="checkbox" value="<?php echo $warehouse_selection_id;?>" <?php if(strpos($staff_wh_access, $warehouse_selection_id)!==false){ echo 'checked=""';}?>/>
                                            <label class="form-check-label" for="<?php echo $warehouse_selection_id;?>"><?php echo $warehouse_selection_name;?></label>
                                        </div>
                                    </div>
                                    <?php 
                                        }
                                    }
                                    ?>
                                </div>
        <?php
}
?>