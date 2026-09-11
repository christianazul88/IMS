<?php
/**
 * Single source of truth for all position/access-level checkboxes.
 *
 * Both add_position.php and update_position.php loop over this same
 * array to render their checkboxes, so the two forms can never fall
 * out of sync with each other again. To add, remove, rename, or
 * re-order a permission, edit ONLY this file.
 *
 * Structure: 'Section Label' => ['checkbox_value' => 'Checkbox label text']
 */

$access_options = [
    'Dashboard' => [
        'revenue_summary'           => 'View Revenue Tracker',
        'outbound_safety_available' => 'View outbound & Inventory Summary',
        'fast_moving_product'       => 'View Fast Moving Product',
        'inbound_outbound'          => 'View Inbound & Outbound Summary',
        'fast_slow_category'        => 'View Fast and Slow Moving Categories',
        'stock_summary'             => 'View Stock Summary',
        'revenue_drop'              => 'View Revenue Dropping',
        'return_summary'            => 'View Return Summary',
        'weekly_sales'              => 'View Weekly Sales',
        'monthly_display_sales'     => 'View Monthly Sales',
        'incoming_stocks'           => 'View Incoming Stocks',
        'promotion'                 => 'View Items for Promotion',
        'under_safety'              => 'View Under Safety Items',
        'dashboard_inventory'       => 'View Inventory on Dashboard',
    ],
    'Exclusive' => [
        'amountupdate' => 'Update Amount',
    ],
    'Inbounds' => [
        'po_logs'      => 'View Purchased Order logs',
        'new_po'       => 'Create Purchased Order',
        'inbound_logs' => 'View Inbound logs',
        'new_inbound'  => 'Create and Upload Inbound',
    ],
    'Inventory Management' => [
        'stock' => 'View stock',
    ],
    'Logistics' => [
        'logistics' => 'Logistics Access',
        'void'      => 'Approve Voided Transactions',
    ],
    'Stock Transfer' => [
        'stock_transfer' => 'View and Create Stock Transfer',
        'rack_transfer'  => 'Transfer items from rack to another rack',
    ],
    'Product Returns' => [
        'returnproduct' => 'View and Create Returned Products',
    ],
    'Returns' => [
        'returns' => 'View and Create Return to Supplier',
    ],
    'Finance' => [
        'finance' => 'Access on finance module',
    ],
    'Forecasting' => [
        'forecasting' => 'View and create forecast',
    ],
    'Users' => [
        'users' => 'View and create user employee accounts',
    ],
    'Administration' => [
        'audit'               => 'View Audit/ System Logs',
        'reports'             => 'View Reports',
        'admin_category'      => 'View and Create Category',
        'admin_brand'         => 'View and Create Brand',
        'product_list'        => 'View and Create Product on Product List',
        'admin_warehouse'     => 'View and Create Warehouse',
        'admin_supplier'      => 'View and Create Supplier',
        'admin_platform'      => 'View and Create Platforms',
        'admin_courier'       => 'View and Create Couriers',
        'barcode_reprint'     => 'Barcode reprint access',
        'admin_accessess'     => 'Access Levels(Position Create) Access',
        'product_destination' => 'View and Create Item Destination',
        'view_capital'        => 'View Unit Cost',
        'view_profit'         => 'View Profit',
        'approve_inbound'     => 'Approve Inbound Delete & Outbound Delete',
    ],
];
