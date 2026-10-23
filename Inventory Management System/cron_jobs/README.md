# Daily inventory activity reports

The runner creates three separate Excel workbooks for the previous calendar
day and uploads them to the shared Drive folder:

https://drive.google.com/drive/folders/1FG7fL_0EqiExd92K4MQKOUioFftiz9Z1

- `inbound_YYYY-MM-DD.xlsx` goes in the **Inbound** folder.
- `outbound_YYYY-MM-DD.xlsx` goes in the **Outbound** folder.
- `barcode_history_YYYY-MM-DD.xlsx` goes in **Returns & Supplier RTS**. It
  records each inbound receipt, outbound shipment, customer return, supplier
  RTS request, and supplier RTS resolution as its own event row.

Each workbook has a bold, highlighted header row, frozen header, filters, and
borders around the populated cells. Google Drive stores Excel workbooks; CSV
is used only as the signed transport format between PHP and Apps Script.

## Schedule and VPS

The Windows Scheduled Task on this development PC is disabled. The Ubuntu VPS
cron entry runs the job as the locked `imsreports` account shortly after
midnight Singapore/Manila time. The runner queries the previous complete
calendar day using UTC+08:00 boundaries. For example, the October 23 run
creates reports for October 22. It does not change IMS source records or their
existing ownership and permissions.

Deploy all files in this `cron_jobs` directory to the IMS project on the VPS.
The cron entry invokes `/opt/lampp/bin/php`; its PHP CLI needs the `curl` and
`mysqli` extensions. No Apache request is involved.

The private `upload.json` belongs at
`/var/lib/ims-daily-reports/upload.json`, owned by
`imsreports:imsreports` with mode `0600`. The file contains the deployed Apps
Script `/exec` URL and HMAC secret; the same secret must remain in the Apps
Script property `IMS_REPORT_SECRET`. Do not commit this file or put it under
the web root.

The crontab command uses the project path (which contains spaces):

```bash
IMS_REPORT_DIR=/var/lib/ims-daily-reports /opt/lampp/bin/php \
  "/opt/lampp/htdocs/IMS/Inventory Management System/cron_jobs/run_daily_reports.php"
```

On a UTC droplet, 16:05 UTC is 00:05 the next day in Singapore/Manila. The
installer converts that target time to the server's current local clock.
Review the installed schedule with `crontab -u imsreports -l`. To disable only
this job, remove the line ending in `# IMS_DAILY_REPORTS`; leave unrelated
crontab lines intact.

To run an explicit date manually after deployment:

```bash
IMS_REPORT_DIR=/var/lib/ims-daily-reports /opt/lampp/bin/php \
  "/opt/lampp/htdocs/IMS/Inventory Management System/cron_jobs/daily_barcode_history.php" \
  --date=2026-10-22 --local-only
```

Use `daily_inbound.php` or `daily_outbound.php` in place of
`daily_barcode_history.php` to generate either transaction report. Logs,
private local CSV transport copies, lock file, and upload receipts are kept in
`/var/lib/ims-daily-reports`.

## Report contents and query behavior

All date filters are prepared, half-open ranges from local midnight through
but not including the next midnight. Inbound rows use `stocks.date` and link
PO data through the actual `stocks.inbound_id`; items remain in the report if
they have since been sold. Outbound rows use `outbound_logs.date_sent`, include
all order statuses, and remain present if the stock row is missing. These are
activity reports, not paid-only or net-sales reports.

Inbound and Outbound include additional columns for the latest known customer
return and supplier RTS information associated with the relevant item
lifecycle. The daily Barcode History workbook is the detailed audit trail:
each event gets its own row, with the barcode, event date/type, related
inbound/outbound/order references, return reason or RTS reason, status/outcome,
amount, and recorded-by user where available. A customer return is associated
with the most recent preceding outbound transaction. A supplier RTS request
is connected to its `rts_content` barcode and carries the original inbound ID
and latest prior outbound context where available. A resolution is a separate
event when its returned date is recorded. This avoids collapsing multiple
events for one barcode into a single history row.

Some source tables do not directly identify which outbound cycle a later
return belongs to; in those cases the report uses the nearest preceding
outbound transaction for context. Fields such as names, descriptions,
warehouses, and current statuses reflect the database at export time. Late
edits are reflected only if the affected date is rerun. Missing or reused
outbound reference IDs fail clearly when the source data cannot reliably
identify which order owns each item. The jobs never update inventory or order
records.

## Upload behavior

The Apps Script receiver must be deployed as a web app executing as the
connected Google account, with access set to `Anyone`. Requests are checked
using an HMAC signature, and the connected account must be able to create files
in each destination folder.

The runner retries dates without `.xlsx.uploaded.json` receipts, beginning
with October 22, 2026. The receiver replaces one workbook with the same exact
name in its destination folder and fails if duplicate matching titles exist.
Replaced files go to Drive trash and can be restored. Existing top-level CSV
files remain intact. Only confirmed uploads receive a local success receipt;
check `daily.log` and `cron.log` after a run. Local-only CSV generation does
not mark a date uploaded.

The receiver accepts `.xlsx` and legacy `.csv` request names during deployment;
both result in a formatted `.xlsx` workbook in Drive. It includes headers even
when a day has no transactions. CSV escaping is handled by PHP, and
formula-like text is escaped before it reaches Sheets.

## Validation

The local database was checked for October 22, 2026: 75 inbound rows, 161
outbound rows, and 236 history events. An older date with return and RTS data
was also checked. The Apps Script receiver was deployed and tested end to end
by uploading `barcode_history_2026-01-22.xlsx`; the resulting workbook opened
in Drive with the formatted `Barcode History` tab.
