<div class="row justify-content-center align-items-center" style="min-height:100dvh;">

    <div class="col-md-7 col-lg-6">

        <div class="card border-0 shadow-lg rounded-4">

            <div class="card-body p-4 p-md-5">

                <!-- Header -->
                <div class="text-center mb-5">

                    <div class="display-3 mb-3">
                        📍
                    </div>

                    <h1 class="fw-bold">
                        Enter Area Code
                    </h1>

                    <p class="text-muted mb-0">
                        Start a new inventory count by scanning an Area Code.
                    </p>

                </div>

                <!-- Scanner -->
                <form method="POST" action="areacode.php">

                    <label class="form-label fw-semibold">
                        Area Code
                    </label>

                    <input
                        type="text"
                        id="locationInput"
                        class="form-control form-control-lg text-center py-3 border-2"
                        placeholder="Scan area code..."
                        autocomplete="off"
                        autofocus
                        inputmode="none"
                        name="areacode"
                    >

                    <button type="submit" class="d-none"></button>

                </form>

                <!-- Status -->
                <div class="text-center mt-4">

                    <span
                        id="statusBadge"
                        class="badge bg-success-subtle text-success border px-3 py-2"
                    >
                        Ready to scan
                    </span>

                </div>

                <!-- Workflow -->
                <div class="card bg-light border-0 mt-5">

                    <div class="card-body">

                        <h5 class="fw-bold mb-4">
                            Inventory Workflow
                        </h5>

                        <div class="d-flex align-items-center mb-3">

                            <div class="badge bg-success me-3">
                                1
                            </div>

                            <div class="flex-grow-1">
                                Enter Area Code
                            </div>

                            <span class="badge bg-success">
                                You are here
                            </span>

                        </div>

                        <div class="d-flex align-items-center mb-3">

                            <div class="badge bg-secondary me-3">
                                2
                            </div>

                            <div>
                                Count and Approve Items
                            </div>

                        </div>

                        <div class="d-flex align-items-center mb-3">

                            <div class="badge bg-secondary me-3">
                                3
                            </div>

                            <div>
                                Generate Inventory Control Tickets
                            </div>

                        </div>

                        <div class="d-flex align-items-center">

                            <div class="badge bg-secondary me-3">
                                4
                            </div>

                            <div>
                                Scan Approved Ticket Barcode
                            </div>

                        </div>

                    </div>

                </div>

                <!-- Information -->
                <div class="alert alert-primary border-0 mt-4">

                    <strong>What happens after scanning?</strong>

                    <div class="small mt-2">

                        The system will generate Inventory Control Tickets
                        for the selected area. These tickets are attached
                        to boxes, pallets, or batches and are later used
                        during counting and approval.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>