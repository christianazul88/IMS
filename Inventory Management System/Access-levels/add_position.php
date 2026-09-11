<?php include_once __DIR__ . '/access_options.php'; ?>
<!-- Modal -->
<div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="../config/add-position.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>" />
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content position-relative">
                <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                        <h4 class="mb-1" id="modalExampleDemoLabel">Add a new position</h4>
                    </div>
                    <div class="p-4 pb-0">
                        <div class="mb-3">
                            <label class="col-form-label" for="position-name">Position Name:</label>
                            <input class="form-control" name="position-name" id="position-name" type="text" />
                            <div class="valid-feedback">Looks good!</div>
                            <div class="invalid-feedback">Position name already exists</div>
                        </div>
                    </div>

                    <div class="row p-4 pb-0 mb-3">
                        <?php foreach ($access_options as $section => $items): ?>
                        <div class="col-lg-12 mb-3">
                            <label><?php echo htmlspecialchars($section); ?></label>
                            <?php foreach ($items as $value => $label):
                                // Unique per checkbox on this page -> label clicks always
                                // toggle the correct checkbox.
                                $checkbox_id = 'add_' . $value;
                            ?>
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    name="access[]"
                                    id="<?php echo $checkbox_id; ?>"
                                    type="checkbox"
                                    value="<?php echo htmlspecialchars($value); ?>"
                                />
                                <label class="form-check-label" for="<?php echo $checkbox_id; ?>"><?php echo htmlspecialchars($label); ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" id="btnsubmit" type="submit" disabled>Submit</button>
                </div>
            </div>
        </div>
    </form>
</div>
