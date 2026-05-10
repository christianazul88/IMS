<?php
if(isset($_GET['inbound-id'])){
    $target_id = $_GET['inbound-id'];
}
?>
<input class="form-control" name="target_id" type="text" value="<?php echo $target_id; ?>" hidden>
<input class="form-control" name="to_userid" type="text" value="<?php echo $_GET['to_userid']; ?>" hidden>

<div class="row">
    <div class="col-lg-12">
        <div class="form-check form-check-inline"><input class="form-check-input" id="inlineRadio1" type="radio" name="response" value="approve" required/><label class="form-check-label" for="inlineRadio1">Approve</label></div>
        <div class="form-check form-check-inline"><input class="form-check-input" id="inlineRadio2" type="radio" name="response" value="decline" required/><label class="form-check-label" for="inlineRadio2">Decline</label></div>
    </div>
    
    <div class="col-lg-12">
        <label for="reason_admin">Reason(if Decline)</label>
        <textarea class="form-control" name="reason_admin" required></textarea>
    </div>
</div>