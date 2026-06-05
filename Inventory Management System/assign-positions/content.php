<?php

if (isset($_POST['update_position'])) {

    $hashed_id = $_POST['hashed_id'];
    $audit_position = (int) $_POST['audit_position'];

    $stmt = $conn->prepare("
        UPDATE audit_users
        SET audit_position = ?
        WHERE hashed_id = ?
    ");

    $stmt->bind_param("is", $audit_position, $hashed_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>
        alert('Position updated successfully.');
        window.location.href = window.location.pathname;
    </script>";
    exit;
}  

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

        <div class="assignment-header"   type="button" data-bs-toggle="collapse" data-bs-target=".multi-collapse" aria-expanded="false" aria-controls="multiCollapseExample1 multiCollapseExample2">
            <h4 class="mb-1">
                <i class="bi bi-people-fill me-2"></i>
                Audit Team Assignment
            </h4>
            <p class="mb-0 opacity-75">
                Assign management personnel and audit staff members.
            </p>
        </div>


        <div class="card-body p-4 collapse multi-collapse" id="multiCollapseExample1">

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


<div class="row mt-3">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-people me-2"></i>
                    Personnel List
                </h5>
            </div>

            <div class="card-body">

                <?php
                $staff_list_query = "
                    SELECT 
                        pos.hashed_id,
                        u.user_fname,
                        u.user_lname,
                        pos.audit_position
                    FROM audit_users pos
                    LEFT JOIN users u ON pos.hashed_id = u.hashed_id
                    ORDER BY u.user_lname ASC
                ";

                $staff_list_result = mysqli_query($conn, $staff_list_query);

                if (mysqli_num_rows($staff_list_result) > 0) {
                ?>

                    <div class="list-group">

                        <?php while ($staff = mysqli_fetch_assoc($staff_list_result)) : ?>

                            <?php
                            $position_label = ($staff['audit_position'] == 1)
                                ? 'Management'
                                : 'Staff';

                            $badge_class = ($staff['audit_position'] == 1)
                                ? 'bg-primary'
                                : 'bg-success';

                            $next_position = ($staff['audit_position'] == 1)
                                ? 2
                                : 1;

                            $next_label = ($next_position == 1)
                                ? 'Management'
                                : 'Staff';
                            ?>

                            <div class="list-group-item">

                                <div class="d-flex justify-content-between align-items-center">

                                    <div>
                                        <div class="fw-semibold">
                                            <?php echo htmlspecialchars($staff['user_fname'] . ' ' . $staff['user_lname']); ?>
                                        </div>

                                        <small class="text-muted">
                                            Current Position:
                                            <?php echo $position_label; ?>
                                        </small>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 pe-3">

                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $position_label; ?>
                                        </span>

                                        <button
                                            type="button"
                                            class="btn btn-outline-warning btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#updatePositionModal<?php echo $staff['hashed_id']; ?>"
                                            title="Update Position">

                                            <i class="far fa-edit"></i>
                                        </button>

                                    </div>

                                </div>

                            </div>

                            <!-- Update Position Modal -->
                            <div class="modal fade"
                                id="updatePositionModal<?php echo $staff['hashed_id']; ?>"
                                tabindex="-1">

                                <div class="modal-dialog">
                                    <div class="modal-content">

                                        <form method="POST">

                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    Update Personnel Position
                                                </h5>

                                                <button
                                                    type="button"
                                                    class="btn-close"
                                                    data-bs-dismiss="modal">
                                                </button>
                                            </div>

                                            <div class="modal-body">

                                                <input
                                                    type="hidden"
                                                    name="hashed_id"
                                                    value="<?php echo htmlspecialchars($staff['hashed_id']); ?>">

                                                <input
                                                    type="hidden"
                                                    name="audit_position"
                                                    value="<?php echo $next_position; ?>">

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Personnel
                                                    </label>

                                                    <div class="fw-semibold">
                                                        <?php echo htmlspecialchars($staff['user_fname'] . ' ' . $staff['user_lname']); ?>
                                                    </div>
                                                </div>

                                                <div class="alert alert-info mb-0">
                                                    Change position from
                                                    <strong><?php echo $position_label; ?></strong>
                                                    to
                                                    <strong><?php echo $next_label; ?></strong>?
                                                </div>

                                            </div>

                                            <div class="modal-footer">

                                                <button
                                                    type="button"
                                                    class="btn btn-secondary"
                                                    data-bs-dismiss="modal">
                                                    Cancel
                                                </button>

                                                <button
                                                    type="submit"
                                                    name="update_position"
                                                    class="btn btn-warning">

                                                    <i class="bi bi-arrow-repeat me-1"></i>
                                                    Update Position

                                                </button>

                                            </div>

                                        </form>

                                    </div>
                                </div>

                            </div>

                        <?php endwhile; ?>

                    </div>

                <?php
                } else {
                    echo "<p class='text-muted mb-0'>No personnel assigned yet.</p>";
                }
                ?>

            </div>
        </div>
    </div>
</div>