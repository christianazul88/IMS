# Daily inbound and outbound reports

The report jobs create separate CSVs for the previous calendar day and upload
them to the shared Drive folder:

https://drive.google.com/drive/folders/1FG7fL_0EqiExd92K4MQKOUioFftiz9Z1

The Windows Scheduled Task on this development PC has been disabled. For
production, run the cron installer on the Ubuntu VPS as the existing Linux
account that can read the IMS database configuration and connect to MySQL.
The installer only adds or updates the `IMS_DAILY_REPORTS` line in that
account's crontab. It preserves the other crontab entries. It does not edit
existing files, change existing ownership or permissions, restart services, or
modify system-wide cron settings. It creates the private report directory only
if it does not already exist; newly created directories use mode `0700`.

## Ubuntu VPS setup

Deploy the updated `cron_jobs` files to the IMS project on the VPS. The PHP CLI
used by cron needs the `curl` and `mysqli` extensions. Do not run the installer
as root; run it as the existing app/deployment user that can read the database
configuration. No Apache request is involved.

Transfer the private `upload.json` from
`C:\xampp\IMS-private\IMS-daily-reports\upload.json` over SSH/SFTP into the
new VPS directory `$HOME/.local/share/ims-daily-reports/upload.json`. Create
the destination only if it does not already exist, and set mode `0600` on this
newly copied file. Do not commit it or put it under the web root. The file
contains the deployed Apps Script `/exec` URL and HMAC secret; the same secret
must remain in the Apps Script property `IMS_REPORT_SECRET`.

From the deployed `cron_jobs` directory, run the installer with the actual
project root (quote the path if it contains spaces):

```bash
bash install_daily_reports_cron.sh "/absolute/path/to/Inventory Management System"
```

On a UTC DigitalOcean droplet, the entry runs at 16:05 UTC, which is 00:05 the
next day in Singapore/Manila. The installer converts that target time to the
server's current local clock. The PHP report code explicitly uses UTC+08:00
for database boundaries, so it queries yesterday's complete local calendar
day. For example, the October 23 run exports `inbound_2026-10-22.csv` and
`outbound_2026-10-22.csv`.

Review the installed user's crontab with `crontab -l`. To run an explicit
date manually after setup:

```bash
IMS_REPORT_DIR="$HOME/.local/share/ims-daily-reports" php \
  "/absolute/path/to/Inventory Management System/cron_jobs/daily_inbound.php" \
  --date=2026-10-22 --local-only
```

Remove only the line ending in `# IMS_DAILY_REPORTS` to disable the VPS job;
leave unrelated crontab lines in place. Logs, local CSV copies, lock file, and
upload receipts are stored in `$HOME/.local/share/ims-daily-reports`.

## Upload behavior

- The Apps Script receiver must be deployed as a web app executing as the
  connected Google account, with access set to `Anyone`. Each request is still
  checked using an HMAC signature, and the Drive account must be able to create
  files in the destination folder.
- The runner catches up unreceipted dates starting **2026-10-22**. The Drive
  receiver updates a single existing file with the exact report filename and
  fails if duplicate matching filenames exist. The first VPS run can therefore
  safely refresh already uploaded dates; it does not require migrating the
  Windows success receipts.
- Only confirmed Drive uploads receive a local success receipt. A later run
  retries dates without receipts. Check `daily.log` and `cron.log` after the
  first run. Local-only CSV generation does not mark a date uploaded.
- Both CSVs include headers when there are no transactions. CSV escaping is
  handled once by PHP, and formula-like text is escaped. Import identifier
  columns as text in Sheets/Excel to preserve leading zeros.

## Query behavior and limits

- Prepared half-open filters include timestamps `>= date 00:00:00` and
  `< next date 00:00:00`. IMS timestamps use Asia/Manila (UTC+08:00).
- Inbound selects stock items by `stocks.date`, links PO data through the
  actual `stocks.inbound_id`, and includes stocks that have since been sold.
  Missing PO data is labeled `No linked PO`, not assumed to be a CSV import.
- Outbound selects item rows through `outbound_logs.date_sent`, includes all
  statuses (including paid, returned, voided), and preserves outbound rows even
  if the corresponding stock record is missing. It is an activity report, not
  a net-sales or paid-only report.
- Dates containing missing or reused outbound reference IDs fail clearly
  because the data cannot reliably identify which order owns each item. No
  source inventory/order records are changed by these jobs.
- RTS fields describe the latest recorded supplier-return row for the barcode,
  not a return linked specifically to that outbound transaction. The schema
  does not provide that relationship. One RTS content ID is chosen to prevent
  duplicate report rows.
- Names, capital, warehouses and statuses reflect the database at export time.
  Deleted transactions cannot be recovered by an overnight query, and late
  edits do not automatically revise completed days. Explicit-date reruns can
  refresh a report.

## Validation already completed

The query and CSV behavior was tested against the configured local database on
October 22, 2026: 75 inbound rows and 161 outbound rows; timestamps and source
row counts matched. Boundary checks included midnight and excluded adjacent
days. The Google Apps Script upload was also verified end to end for both
October 22 reports. VPS cron delivery still needs its first server run checked
in `daily.log` and in the shared Drive folder.
