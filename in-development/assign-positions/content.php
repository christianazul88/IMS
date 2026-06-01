<?php 
$users_query = "SELECT hashed_id, user_fname, user_lname FROM users";
$result = mysqli_query($conn, $users_query);

$user_options = [];
while ($row = mysqli_fetch_assoc($result)) {
    $user_options[] = '<option value="' . htmlspecialchars($row['hashed_id']) . '">' .
                      htmlspecialchars($row['user_fname'] . ' ' . $row['user_lname']) .
                      '</option>';
}
?>

<style>
.assignment-card {
    border: none;
    border-radius: 1rem;
    overflow: hidden;
}

.assignment-header {
    background: linear-gradient(135deg, #0d6efd, #0b5ed7);
    color: #fff;
    padding: 1.25rem;
}

.role-section {
    border: 1px solid #e9ecef;
    border-radius: .75rem;
    padding: 1rem;
    background: #fff;
    transition: all .2s ease;
}

.role-section:hover {
    box-shadow: 0 .25rem .75rem rgba(0,0,0,.08);
}

.role-title {
    font-weight: 600;
    margin-bottom: .25rem;
}

.role-description {
    color: #6c757d;
    font-size: .875rem;
}

.management-badge {
    background: rgba(13,110,253,.1);
    color: #0d6efd;
}

.staff-badge {
    background: rgba(25,135,84,.1);
    color: #198754;
}

.form-select,
.choices__inner {
    min-height: 48px;
}

.helper-text {
    font-size: .85rem;
    color: #6c757d;
    margin-top: .5rem;
}

.card-footer-custom {
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}
</style>

<form action="assign_positions.php" method="POST">

    <div class="card shadow-sm assignment-card">

        <div class="assignment-header">
            <h4 class="mb-1">
                <i class="bi bi-people-fill me-2"></i>
                Audit Team Assignment
            </h4>
            <p class="mb-0 opacity-75">
                Assign management personnel and audit staff members.
            </p>
        </div>

        <div class="card-body p-4">

            <!-- MANAGEMENT -->
            <div class="role-section mb-4">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="role-title">
                            Management Team
                        </div>
                        <div class="role-description">
                            Can schedule audits, approve/reject requests, and close audits.
                        </div>
                    </div>

                    <span class="badge management-badge px-3 py-2">
                        Full Access
                    </span>
                </div>

                <label for="manage" class="form-label fw-semibold">
                    Select Management Personnel
                </label>

                <select
                    class="form-select js-choice"
                    id="manage"
                    multiple
                    size="1"
                    name="manage[]"
                    data-options='{"removeItemButton":true,"placeholder":true}'
                >
                    <?php echo implode('', $user_options); ?>
                </select>

                <div class="helper-text">
                    Users selected here will be able to manage the entire audit process.
                </div>

            </div>

            <!-- STAFF -->
            <div class="role-section">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="role-title">
                            Audit Staff
                        </div>
                        <div class="role-description">
                            Can perform inventory scanning and audit execution tasks.
                        </div>
                    </div>

                    <span class="badge staff-badge px-3 py-2">
                        Scan Access
                    </span>
                </div>

                <label for="staff" class="form-label fw-semibold">
                    Select Audit Staff
                </label>

                <select
                    class="form-select js-choice"
                    id="staff"
                    multiple
                    size="1"
                    name="staff[]"
                    data-options='{"removeItemButton":true,"placeholder":true}'
                >
                    <?php echo implode('', $user_options); ?>
                </select>

                <div class="helper-text">
                    Users selected here will only have scanning and inventory-counting permissions.
                </div>

            </div>

        </div>

        <div class="card-footer card-footer-custom text-end p-3">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-circle me-1"></i>
                Save Assignment
            </button>
        </div>

    </div>

</form>