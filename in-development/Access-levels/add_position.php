<!-- Modal -->
<div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="../config/add-position.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
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
                            <label class="col-form-label" for="position-name">position Name:</label>
                            <input class="form-control" name="position-name" id="position-name" type="text" />
                            <div class="valid-feedback">Looks good!</div>
                            <div class="invalid-feedback">position name already exist</div>
                        </div>

                    </div>
                    <div class="row p-4 pb-0 mb-3">
                        <div class="col-lg-12 mb-3">
                            <label for="">Dashboard</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="revenue_summary" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Revenue Tracker</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="outbound_safety_available" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View outbound & Inventory Summary</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="fast_moving_product" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Fast Moving Product</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="inbound_outbound" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Inbound & Outbound Summary</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="fast_slow_category" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Fast and Slow Moving Categories</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="stock_summary" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Stock Summary</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="revenue_drop" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Revenue Dropping</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="return_summary" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Return Summary</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="weekly_sales" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Weekly Sales</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="monthly_display_sales" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Monthly Sales</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="incoming_stocks" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Incoming Stocks</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="promotion" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Items for Promotion</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="under_safety" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Under Safety Items</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="dashboard_inventory" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Inventory on Dashboard</label>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="">Exclusive</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="amountupdate" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">Update Amount</label>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="">Inbounds</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="po_logs" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Purchased Order logs</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="new_po" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">Create Purchased Order</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="inbound_logs" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Inbound logs</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="new_inbound" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">Create and Upload Inbound</label>
                            </div>
                            
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="">Inventory Management</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="stock" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View stock</label>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="">Logistics</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="logistics" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">Logistics Access</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="void" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">Approve Voided Transactions</label>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="">Stock Transfer</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="stock_transfer" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and Create Stock Transfer</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="rack_transfer" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">Transfer items from rack to another rack</label>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="">Product Returns</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="returnproduct" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and Create Returned Products</label>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="">Returns</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="returns" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and Create Return to Supplier</label>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="">Finance</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="finance" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">Access on finance module</label>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="">Forecasting</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="forecasting" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and create forecast</label>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="">Users</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="users" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View  and create user employee accounts</label>
                            </div>
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label for="">administration</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="audit" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Audit/ System Logs</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="reports" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Reports</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="admin_category" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and Create Category</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="admin_brand" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and Create Brand</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="product_list" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and Create Product on Product List</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="admin_warehouse" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and Create Warehouse</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="admin_supplier" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and Create Supplier</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="admin_platform" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and Create Platforms</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="admin_courier" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and Create Couriers</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="barcode_reprint" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">Barcode reprint access</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="admin_accessess" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">Access Levels(Position Create) Access</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="product_destination" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">View and Create Item Destination</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="view_capital"/>
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Unit Cost</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="view_profit"/>
                                <label class="form-check-label" for="flexSwitchCheckDefault">View Profit</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input"  name="access[]" id="flexSwitchCheckDefault" type="checkbox" value="approve_inbound" />
                                <label class="form-check-label" for="flexSwitchCheckDefault">Approve Inbound Delete & Outbound Delete</label>
                            </div>

                        </div>

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