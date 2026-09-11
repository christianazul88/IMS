<?php
include_once __DIR__ . '/access_options.php';

$repeat = "SELECT * FROM user_position";
$result = mysqli_query($conn, $repeat);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $account_position_id     = $row['id'];
        $account_position_name   = $row['position_name'];
        $account_position_access = $row['access'];
        $is_administrator        = ($account_position_name === "Administrator");
?>
<!-- Modal -->
<div class="modal fade" id="edit-modal_<?php echo $account_position_id; ?>" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="../config/update-position.php" id="update_access" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content position-relative">
                <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                        <h4 class="mb-1" id="modalExampleDemoLabel">Update <?php echo htmlspecialchars($account_position_name); ?></h4>
                    </div>

                    <div class="row p-4 pb-0 mb-3">
                        <input type="hidden" name="position_name" value="<?php echo htmlspecialchars($account_position_name); ?>">
                        <input type="hidden" name="position_id" value="<?php echo htmlspecialchars($account_position_id); ?>">

                        <?php foreach ($access_options as $section => $items): ?>
                        <div class="col-lg-12 mb-3">
                            <label><?php echo htmlspecialchars($section); ?></label>
                            <?php foreach ($items as $value => $label):
                                // Unique per position AND per value -> no duplicate IDs
                                // anywhere on the page, even with many positions listed.
                                $checkbox_id = 'edit_' . $account_position_id . '_' . $value;
                                $is_checked  = (strpos($account_position_access, $value) !== false);
                            ?>
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    name="access[]"
                                    id="<?php echo $checkbox_id; ?>"
                                    type="checkbox"
                                    value="<?php echo htmlspecialchars($value); ?>"
                                    <?php echo $is_checked ? 'checked' : ''; ?>
                                    <?php echo $is_administrator ? 'disabled' : ''; ?>
                                />
                                <label class="form-check-label" for="<?php echo $checkbox_id; ?>"><?php echo htmlspecialchars($label); ?></label>
                            </div>
                            <?php if ($is_administrator): ?>
                            <!-- Browsers never submit disabled inputs, so Administrator's
                                 locked permissions are mirrored here to guarantee they are
                                 always saved, for every section (not just some). -->
                            <input type="hidden" name="access[]" value="<?php echo htmlspecialchars($value); ?>" />
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" id="update_btnsubmit" type="submit" disabled>Submit</button>
                    <button class="btn btn-primary" id="updateloading_btn" type="button" disabled hidden>
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Loading...
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
<?php
    }
}
?>
