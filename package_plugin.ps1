$repo = 'C:\Users\Pappi\Documents\GitHub\cured-diagnostics'
$zip = 'C:\Users\Pappi\Documents\GitHub\chdfinal.zip'

if (Test-Path $zip) {
    Remove-Item $zip -Force
}

$items = Get-ChildItem -Path $repo -Recurse -File |
    Where-Object {
        $_.FullName -notmatch '\\.git' -and $_.FullName -notmatch '\\.zip$'
    }

if ($items.Count -eq 0) {
    Write-Host 'No files found to package.'
    exit 1
}

Compress-Archive -Path (Join-Path $repo '*') -DestinationPath $zip -Force

Write-Host "Created: $zip"
Write-Host "Files archived: $($items.Count)"
