#!/usr/bin/env bash
set -euo pipefail

if [[ "$(id -u)" -eq 0 ]]; then
    echo "Run this as the existing Linux account that can read the IMS config and connect to MySQL, not as root." >&2
    exit 1
fi

if [[ $# -ne 1 ]]; then
    echo "Usage: bash install_daily_reports_cron.sh /absolute/path/to/IMS" >&2
    exit 2
fi

project_root=$(cd -- "$1" && pwd -P)
runner="$project_root/cron_jobs/run_daily_reports.php"
data_dir="${HOME:?HOME is not set}/.local/share/ims-daily-reports"
config_file="$data_dir/upload.json"

for file in "$runner" \
    "$project_root/cron_jobs/daily_inbound.php" \
    "$project_root/cron_jobs/daily_outbound.php" \
    "$project_root/cron_jobs/daily_barcode_history.php" \
    "$project_root/cron_jobs/report_common.php"; do
    [[ -r "$file" ]] || { echo "Required report file is missing or unreadable: $file" >&2; exit 1; }
done

php_bin=${PHP_BIN:-$(command -v php || true)}
[[ -n "$php_bin" && -x "$php_bin" ]] || { echo "PHP CLI was not found; set PHP_BIN to its executable path." >&2; exit 1; }
modules=$("$php_bin" -m)
for module in curl mysqli; do
    grep -qi "^${module}$" <<<"$modules" || {
        echo "PHP CLI is missing the required $module extension." >&2
        exit 1
    }
done
for file in "$runner" \
    "$project_root/cron_jobs/daily_inbound.php" \
    "$project_root/cron_jobs/daily_outbound.php" \
    "$project_root/cron_jobs/daily_barcode_history.php" \
    "$project_root/cron_jobs/report_common.php"; do
    "$php_bin" -l "$file" >/dev/null
done

# Create only the new private directory. If it already exists, keep its mode and owner.
if [[ -L "$data_dir" ]]; then
    echo "Refusing to use a symlink for the private report directory: $data_dir" >&2
    exit 1
fi
if [[ ! -e "$data_dir" ]]; then
    (umask 077 && mkdir -p -- "$data_dir")
fi
[[ -d "$data_dir" && -w "$data_dir" ]] || {
    echo "The private report directory is not a writable directory: $data_dir" >&2
    exit 1
}
[[ -r "$config_file" ]] || {
    echo "Drive upload config is missing or unreadable: $config_file" >&2
    echo "Copy the private upload.json from your Windows report storage to this new directory first." >&2
    exit 1
}
"$php_bin" -r '$c=json_decode(file_get_contents($argv[1]),true); exit(is_array($c) && preg_match("~^https://script\\.google\\.com/macros/s/[a-zA-Z0-9_-]+/exec$~", $c["url"] ?? "") && is_string($c["secret"] ?? null) && ($c["secret"] ?? "") !== "" ? 0 : 1);' "$config_file" || {
    echo "Drive upload config is invalid: $config_file" >&2
    exit 1
}

# Convert 00:05 in Manila/Singapore to the server's current local clock time.
# DigitalOcean Ubuntu droplets normally use UTC, which yields 16:05.
target_utc="$(date -u '+%Y-%m-%d') 16:05:00 UTC"
read -r cron_minute cron_hour < <(date -d "$target_utc" '+%M %H')
printf -v quoted_data '%q' "$data_dir"
printf -v quoted_php '%q' "$php_bin"
printf -v quoted_runner '%q' "$runner"
printf -v quoted_log '%q' "$data_dir/cron.log"
cron_line="$cron_minute $cron_hour * * * IMS_REPORT_DIR=$quoted_data $quoted_php $quoted_runner >> $quoted_log 2>&1 # IMS_DAILY_REPORTS"

tmp_dir=$(mktemp -d)
trap 'rm -rf -- "$tmp_dir"' EXIT
current="$tmp_dir/current"
error="$tmp_dir/crontab-error"
if crontab -l >"$current" 2>"$error"; then
    :
elif [[ ! -s "$current" ]] && grep -qi 'no crontab' "$error"; then
    :
else
    cat "$error" >&2
    echo "Could not read the current crontab; leaving it unchanged." >&2
    exit 1
fi
awk '!/# IMS_DAILY_REPORTS$/' "$current" >"$tmp_dir/updated"
printf '%s\n' "$cron_line" >>"$tmp_dir/updated"
crontab "$tmp_dir/updated"

echo "Installed/updated only the IMS_DAILY_REPORTS entry in this user's crontab."
echo "Runs daily at server-local $cron_hour:$cron_minute, corresponding to 00:05 Asia/Singapore for the server's current UTC offset."
echo "Existing crontab entries and existing file permissions were left in place."
echo "Private report files: $data_dir"
crontab -l | tail -n 5
