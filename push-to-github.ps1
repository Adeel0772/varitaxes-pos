# Run once in PowerShell from this folder:
#   .\push-to-github.ps1
#
# If not logged in to GitHub, it will open browser login first.

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

if (-not (Get-Command gh -ErrorAction SilentlyContinue)) {
    Write-Host "Install GitHub CLI: winget install GitHub.cli"
    exit 1
}

gh auth status 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-Host "Log in to GitHub (browser will open)..."
    gh auth login --hostname github.com --git-protocol https --web
}

Write-Host "Creating repo varitaxes-pos and pushing..."
gh repo create varitaxes-pos --public --source=. --remote=origin --push

$url = gh repo view --json url -q .url
Write-Host ""
Write-Host "DONE. Your repo:" -ForegroundColor Green
Write-Host $url
