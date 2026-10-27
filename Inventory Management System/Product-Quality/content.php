<div class="card">
    <div class="card-header text-center bg-primary text-white">
        <h2 class="mb-0">Product Quality</h2>
    </div>
    <div class="card-body">
        <p class="mb-2 fs-11">
            Generates a downloadable summary of returned products for one day or a selected date range. The report has two sections:
        </p>
        <p class="mb-1 fs-11">
            <span class="fw-semibold">Defective Returns (Local)</span> – includes product description, brand, category, supplier, quantity, and total value.
        </p>
        <p class="mb-3 fs-11">
            <span class="fw-semibold">Delivery Failed Returns (International)</span> – includes product description, brand, category, logistic platform, quantity, and total value.
        </p>
        <p class="mb-4 fs-11">
            It helps track product quality issues, monitor supplier performance, and identify return patterns for better inventory and operational decisions.
        </p>

        <form action="../Product-Quality/index" method="POST">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="timepicker2" class="form-label">Select Date Range</label>
                    <input 
                        type="text" 
                        id="timepicker2" 
                        name="date_between" 
                        class="form-control datetimepicker" 
                        placeholder="dd/mm/yy or dd/mm/yy to dd/mm/yy"
                        data-options='{"mode":"range","dateFormat":"d/m/y","disableMobile":true}' 
                        value="<?php echo htmlspecialchars(is_string($_POST['date_between'] ?? null) ? $_POST['date_between'] : '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    />
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>

        <?php
        $reportDateError = '';
        $startDateFormatted = null;
        $endDateFormatted = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && array_key_exists('date_between', $_POST)) {
            $dateBetween = is_string($_POST['date_between'])
                ? trim($_POST['date_between'])
                : '';
            $dateParts = preg_split('/\s+to\s+/i', $dateBetween);

            if ($dateBetween === '' || $dateParts === false || count($dateParts) > 2) {
                $reportDateError = 'Please select one date or a valid date range.';
            } else {
                $startStr = trim($dateParts[0]);
                $endStr = trim($dateParts[1] ?? $startStr);
                $startDate = DateTimeImmutable::createFromFormat('!d/m/y', $startStr);
                $startErrors = DateTimeImmutable::getLastErrors();
                $endDate = DateTimeImmutable::createFromFormat('!d/m/y', $endStr);
                $endErrors = DateTimeImmutable::getLastErrors();

                $startHasErrors = is_array($startErrors)
                    && ($startErrors['warning_count'] > 0 || $startErrors['error_count'] > 0);
                $endHasErrors = is_array($endErrors)
                    && ($endErrors['warning_count'] > 0 || $endErrors['error_count'] > 0);

                if (
                    !$startDate
                    || !$endDate
                    || $startHasErrors
                    || $endHasErrors
                    || $startDate->format('d/m/y') !== $startStr
                    || $endDate->format('d/m/y') !== $endStr
                ) {
                    $reportDateError = 'The selected date is invalid. Please choose a date from the calendar.';
                } elseif ($endDate < $startDate) {
                    $reportDateError = 'The end date must be the same as or later than the start date.';
                } else {
                    $startDateFormatted = $startDate->format('Y-m-d') . ' 00:00:00';
                    $endDateFormatted = $endDate->format('Y-m-d') . ' 23:59:59';
                }
            }
        }

        if ($reportDateError !== '') {
        ?>
        <div class="alert alert-danger mt-3" role="alert">
            <?php echo htmlspecialchars($reportDateError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php
        } elseif ($startDateFormatted !== null && $endDateFormatted !== null) {
            $downloadQuery = http_build_query([
                'start' => $startDateFormatted,
                'end' => $endDateFormatted,
            ]);
        ?>
        <div class="text-center mt-4">
            <a 
                href="download-local.php?<?php echo htmlspecialchars($downloadQuery, ENT_QUOTES, 'UTF-8'); ?>"
                class="btn btn-outline-primary d-inline-flex align-items-center"
            >
                <i class="fas fa-file-alt me-2"></i> Download CSV
            </a>
        </div>
        <?php } ?>
    </div>
</div>
