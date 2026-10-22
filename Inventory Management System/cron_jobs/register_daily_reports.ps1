$ErrorActionPreference = 'Stop'
$runner = Join-Path $PSScriptRoot 'run_daily_reports.php'
$action = New-ScheduledTaskAction -Execute 'C:\xampp\php\php.exe' -Argument ('"' + $runner + '"') -WorkingDirectory $PSScriptRoot
$trigger = New-ScheduledTaskTrigger -Daily -At '00:05'
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -WakeToRun -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -MultipleInstances IgnoreNew -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 15) -ExecutionTimeLimit (New-TimeSpan -Hours 2)
# Interactive token avoids storing your Windows password. Runs while signed in,
# including a locked desktop. Missed dates are caught up on the next run.
$principal = New-ScheduledTaskPrincipal -UserId ([Security.Principal.WindowsIdentity]::GetCurrent().Name) -LogonType Interactive -RunLevel Limited
Register-ScheduledTask -TaskName 'IMS Daily Inbound and Outbound Reports' -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description 'Upload separate previous-day IMS CSV reports to the designated shared Google Drive folder; catch up missed dates from 2026-10-22.'
