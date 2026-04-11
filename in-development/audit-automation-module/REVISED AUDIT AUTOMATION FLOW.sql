CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,

    audit_num INT,
    warehouse VARCHAR(100),

    audit_status ENUM('pending','active','paused','completed'),

    schedule_date DATETIME,

    total_expected_qty DECIMAL(10,2),
    total_scanned_qty DECIMAL(10,2),
    total_variance_qty DECIMAL(10,2),
    total_variance_value DECIMAL(10,2),

    remarks TEXT,

    created_by VARCHAR(100),
    updated_by VARCHAR(100),
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,
    deleted_at DATETIME NULL
);

CREATE TABLE audit_logs_timestamps (
	id INT AUTO_INCREMENT PRIMARY KEY,

	audit_id INT,
	date_time DATETIME,
	status ENUM('start', 'pause', 'resume', 'end')

);

CREATE TABLE audit_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    audit_id INT,
    user_id VARCHAR(100),

    warehouse VARCHAR(100),
    item_location VARCHAR(100),

    status ENUM('pending','in_progress','for_approval','approved','rejected'),

    date_assigned DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    approved_by VARCHAR(100),
    approved_at DATETIME,
 
    remarks TEXT, 

    FOREIGN KEY (audit_id) REFERENCES audit_logs(id)
);


CREATE TABLE audit_assignment_logs (
	id INT AUTO_INCREMENT PRIMARY KEY,

	user_id varchar(100),
	audit_assignment_id INT,
	date_time DATETIME,
	status ENUM('start', 'pause', 'resume', 'end'),

	remarks TEXT,
	FOREIGN KEY (audit_assignment_id) REFERENCES audit_assignments(id)
);

CREATE TABLE audit_items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    audit_id INT,
    parent_barcode varchar(100), -- replaced item_id because parent_barcode is what I used.

    expected_qty DECIMAL(10,2),
    scanned_qty DECIMAL(10,2),

    variance_qty DECIMAL(10,2),
    variance_value DECIMAL(10,2), -- instead of variance_qty x unit_cost, the new computation will be unit_cost - scanned_value because the capital for this item might change depending on the date it was delivered. I am referring for the unit_cost as capital right?

    unit_cost DECIMAL(10,2),
    scanned_value DECIMAL(10,2),
    item_location VARCHAR(100),

    last_scanned_at DATETIME,

    FOREIGN KEY (audit_id) REFERENCES audit_logs(id)
);

CREATE TABLE items_to_audit (
	id INT AUTO_INCREMENT PRIMARY KEY,
	audit_id int,

	unique_barcode varchar(30),
	audit_status ENUM('pending', 'scanned'),
	belong_to_other_location ENUM('yes','no'), -- this will only be yes if an item that is about to be sent to other warehouse has been scanned. or an item which is supposed to be on the other item location has been scanned.
	belong_to_type ENUM('warehouse','item_location'),-- the item can be belong to table warehouse or item_location) I add this to avoid id confusion. this is default to NULL
	belong_to_value varchar(100), -- belong to the id on warehouse table or maybe belong to id of item_location. this is default to NULL
	scanned_date DATETIME,
	-- you might notice that the capital is missing, it is because the capital is on stocks table where I will find the unique barcode to get the capital of this unique barcode. is that the correct approach?
	FOREIGN KEY (audit_id) REFERENCES audit_logs(id)

);


CREATE TABLE inventory_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    audit_id INT,
    unique_barcode varchar(30),

    adjustment_type ENUM('delete','add'),

    reason VARCHAR(255),

    approved_by VARCHAR(100),
    approved_at DATETIME,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


-- REAL WORLD FLOW BASED ON MY SYSTEM
-- 1. CREATE AUDIT -> audit_logs
-- 2. SYNC/ LOAD EXPECTED ITEMS -> audit_items & items_to_audit
-- 3. staff scans barcode -> update audit_items.scanned_qtym audit_items, scanned_value, items_to_audit. audit_status('scanned')
-- 3.1 if staff scanned an item for different rack location -> update audit_items.scanned_qty, audit_items.scanned_value, items_to_audit.audit_status('scanned'), items_to_audit.belong_to_other_location('yes'), items_to_audit.belong_to_table('item_location'), items_to_audit.belong_to_id('insert the value of item_location from table stocks WHERE items_to_audit.unique_barcode is equal to stocks.unique_barcode')
-- 3.2 if staff scanned an item that is about to be transferred to different warehouse -> update audit_items.scanned_qty, audit_items.scanned_value, items_to_audit.audit_status('scanned'), items_to_audit.belong_to_other_location('yes'), items_to_audit.belong_to_table('warehouse'), items_to_audit.belong_to_id('insert the value of the warehouse column from table stocks where items_to_audit.unique_barcode is equal to stocks.unique_barcode').
-- 4. update running totals -> audit_items
-- 5. compute variance -> audit_items
-- 6.finish audit -> update audit_logs.audit_status 
-- 7. approve audit -> insert into inventory_adjustments

-- please note that for every pause and resume or start or finish it of either the audit or the staff making an audit on a specific location, it will be inserted to table audit_assignment_logs and table audit_logs_timestamps.
-- I did not create audit_history because I am also recording the datetime the item was scanned on items_to_audit table on column scanned_date if you dont mind.
-- also, items that are not supposed to be on that specific location will be scanned thats why the are columns ('belong_to_other_location', 'belong_to_table', 'belong_to_id')