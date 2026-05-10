<?php
if(isset($_GET['target-id'])){
    $target_id = $_GET['target-id'];
}
?>
<input class="form-control" name="target_id" type="text" value="<?php echo $target_id; ?>" hidden>
<div class="row">
    <label for="reason_staff">Reason for deleting</label>
    <div class="col-lg-12">
        <textarea class="form-control" name="reason_staff" required></textarea>
    </div>
</div>