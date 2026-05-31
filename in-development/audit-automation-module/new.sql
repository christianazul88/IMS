-- =====================================================
-- TABLE: audit_logs
-- =====================================================
CREATE TABLE audit_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    audit_num INT(11) NOT NULL,
    warehouse VARCHAR(100) NOT NULL,
    audit_status ENUM(
        'deleted',
        'pending',
        'active',
        'paused',
        'partially_completed',
        'completed'
    ) NOT NULL DEFAULT 'pending',
    schedule_date DATETIME DEFAULT NULL,
    total_expected_qty DECIMAL(10,2) DEFAULT 0.00,
    total_expected_amount DECIMAL(10,2) DEFAULT 0.00,
    remarks TEXT,
    created_by VARCHAR(100) DEFAULT NULL,
    updated_by VARCHAR(100) DEFAULT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_audit_num (audit_num),
    KEY idx_audit_status (audit_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- TABLE: audit_logs_timestamps
-- =====================================================
CREATE TABLE audit_logs_timestamps (
    id INT(11) NOT NULL AUTO_INCREMENT,
    audit_id INT(11) NOT NULL,
    date_time DATETIME NOT NULL,
    status ENUM(
        'start',
        'pause',
        'resume',
        'end'
    ) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_audit_id (audit_id),
    CONSTRAINT fk_audit_logs_timestamps_audit
        FOREIGN KEY (audit_id)
        REFERENCES audit_logs(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- TABLE: audit_assignments
-- =====================================================
CREATE TABLE audit_assignments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    audit_id INT(11) NOT NULL,
    warehouse VARCHAR(100) NOT NULL,
    item_location VARCHAR(100) NOT NULL,
    status ENUM(
        'pending',
        'pause',
        'in_progress',
        'for_approval',
        'approved',
        'rejected'
    ) NOT NULL DEFAULT 'pending',
    expected_qty DECIMAL(10,2) DEFAULT 0.00,
    sub_total_expected_amount DECIMAL(10,2) DEFAULT 0.00,
    approved_by VARCHAR(100) DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    remarks TEXT,
    PRIMARY KEY (id),
    KEY idx_audit_assignment_audit (audit_id),
    CONSTRAINT fk_audit_assignments_audit
        FOREIGN KEY (audit_id)
        REFERENCES audit_logs(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- TABLE: audit_assignment_staffs
-- =====================================================
CREATE TABLE audit_assignment_staffs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    audit_assignments_id INT(11) NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    date_assigned DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM(
        'pending',
        'pause',
        'in_progress',
        'for_approval',
        'approved',
        'rejected'
    ) NOT NULL DEFAULT 'pending',
    PRIMARY KEY (id),
    KEY idx_assignment_staff_assignment (audit_assignments_id),
    KEY idx_assignment_staff_user (user_id),
    CONSTRAINT fk_assignment_staff_assignment
        FOREIGN KEY (audit_assignments_id)
        REFERENCES audit_assignments(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- TABLE: audit_assignments_timestamps
-- =====================================================
CREATE TABLE audit_assignments_timestamps (
    id INT(11) NOT NULL AUTO_INCREMENT,
    audit_assignment_id INT(11) NOT NULL,
    date_time DATETIME NOT NULL,
    status ENUM(
        'start',
        'pause',
        'resume',
        'end'
    ) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_assignment_timestamp_assignment (audit_assignment_id),
    CONSTRAINT fk_assignment_timestamp_assignment
        FOREIGN KEY (audit_assignment_id)
        REFERENCES audit_assignments(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- TABLE: items_to_audit
-- =====================================================
CREATE TABLE items_to_audit (
    id INT(11) NOT NULL AUTO_INCREMENT,
    audit_id INT(11) NOT NULL,
    audit_assignment_id INT(11) NOT NULL,
    user_id VARCHAR(100) DEFAULT NULL,
    warehouse_origin VARCHAR(100) DEFAULT NULL,
    warehouse_onscanned VARCHAR(100) DEFAULT NULL,
    item_location_origin INT(12) DEFAULT NULL,
    item_location_onscanned INT(12) DEFAULT NULL,
    unique_barcode VARCHAR(30) NOT NULL,
    audit_status ENUM(
        'pending',
        'scanned'
    ) NOT NULL DEFAULT 'pending',
    scanned_date DATETIME DEFAULT NULL,
    outbounded ENUM(
        'yes',
        'no'
    ) NOT NULL DEFAULT 'no',
    belong_to_system_stocks ENUM(
        'yes',
        'no'
    ) NOT NULL DEFAULT 'yes',
    PRIMARY KEY (id),
    KEY idx_items_audit_id (audit_id),
    KEY idx_items_assignment_id (audit_assignment_id),
    KEY idx_items_barcode (unique_barcode),
    CONSTRAINT fk_items_audit
        FOREIGN KEY (audit_id)
        REFERENCES audit_logs(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_items_assignment
        FOREIGN KEY (audit_assignment_id)
        REFERENCES audit_assignments(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- TABLE: audit_positions
-- =====================================================
CREATE TABLE audit_positions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    audit_access LONGTEXT,
    status ENUM(
        'active',
        'deleted'
    ) NOT NULL DEFAULT 'active',
    user_id VARCHAR(100) DEFAULT NULL,
    audit_position_name VARCHAR(50) NOT NULL,
    date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_position_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================================================
-- TABLE: audit_users
-- =====================================================
CREATE TABLE audit_users (
    hashed_id VARCHAR(100) NOT NULL,
    audit_position INT(11) DEFAULT NULL,
    PRIMARY KEY (hashed_id),
    KEY idx_audit_position (audit_position),
    CONSTRAINT fk_audit_users_position
        FOREIGN KEY (audit_position)
        REFERENCES audit_positions(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;