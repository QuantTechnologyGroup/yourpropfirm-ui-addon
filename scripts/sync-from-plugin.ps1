<#
.SYNOPSIS
    Syncs files FROM the YourPropFirm plugin directory INTO this checkout-frontend repo.

.DESCRIPTION
    The reverse of sync-to-plugin.ps1. Compares SHA256 hashes of plugin files against
    repo source files, copies only changed files, and reminds the developer to run
    "git diff" to review what changed.

    Because this overwrites tracked source files in the repo, a confirmation prompt
    is shown unless -DryRun is specified.

.PARAMETER PluginDir
    Mandatory. Absolute path to the yourpropfirm-plugin directory.
    Must contain yourpropfirm.php.

.PARAMETER DryRun
    Switch. When present, prints what WOULD be copied without making any changes.
    Skips the confirmation prompt.

.EXAMPLE
    .\sync-from-plugin.ps1 -PluginDir "C:\Users\ADVAN\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin"

.EXAMPLE
    .\sync-from-plugin.ps1 -PluginDir "C:\Users\ADVAN\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin" -DryRun
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$PluginDir,

    [switch]$DryRun
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

function Write-Copied  { param([string]$msg) Write-Host $msg -ForegroundColor Green }
function Write-Skipped { param([string]$msg) Write-Host $msg -ForegroundColor DarkGray }
function Write-Missing { param([string]$msg) Write-Host $msg -ForegroundColor Red }
function Write-Info    { param([string]$msg) Write-Host $msg -ForegroundColor Cyan }

function Get-FileHash256 {
    param([string]$Path)
    (Get-FileHash -Path $Path -Algorithm SHA256).Hash
}

# ---------------------------------------------------------------------------
# Validate plugin directory
# ---------------------------------------------------------------------------

if (-not (Test-Path $PluginDir -PathType Container)) {
    Write-Error "PluginDir does not exist or is not a directory: $PluginDir"
    exit 1
}

$pluginEntryFile = Join-Path $PluginDir 'yourpropfirm.php'
if (-not (Test-Path $pluginEntryFile -PathType Leaf)) {
    Write-Error "PluginDir does not appear to be the yourpropfirm plugin (yourpropfirm.php not found): $PluginDir"
    exit 1
}

# ---------------------------------------------------------------------------
# Resolve frontend root (parent of the scripts\ directory)
# ---------------------------------------------------------------------------

$FrontendRoot = Split-Path -Parent $PSScriptRoot

Write-Info "Plugin dir    : $PluginDir"
Write-Info "Frontend root : $FrontendRoot"
if ($DryRun) {
    Write-Info "[DRY RUN] No files will be written."
}
Write-Host ""

# ---------------------------------------------------------------------------
# Confirmation prompt (skipped in DryRun mode)
# ---------------------------------------------------------------------------

if (-not $DryRun) {
    Write-Host "WARNING: This will overwrite source files in the repo with files from the plugin." -ForegroundColor Yellow
    Write-Host "Make sure you have committed or stashed any local changes before proceeding." -ForegroundColor Yellow
    Write-Host ""
    $answer = Read-Host "Type 'yes' to continue, anything else to abort"
    if ($answer -ne 'yes') {
        Write-Info "Aborted by user."
        exit 0
    }
    Write-Host ""
}

# ---------------------------------------------------------------------------
# Static file mappings  (plugin-relative -> repo-relative)
# ---------------------------------------------------------------------------

$mappings = [ordered]@{
    'public\src\css\checkout.css'                         = 'src\css\checkout.css'
    'public\src\css\components.css'                       = 'src\css\components.css'
    'public\css\checkout.css'                             = 'dist\css\checkout.css'
    'public\js\checkout.js'                               = 'js\checkout.js'
    'public\js\dark-mode.js'                              = 'js\dark-mode.js'
    'public\partials\checkout\addons.php'                 = 'templates\partials\checkout\addons.php'
    'public\partials\checkout\trading-platform.php'       = 'templates\partials\checkout\trading-platform.php'
}

# ---------------------------------------------------------------------------
# Dynamically add woocommerce\checkout\*.php from the plugin
# ---------------------------------------------------------------------------

$pluginWooCheckoutDir = Join-Path $PluginDir 'woocommerce\checkout'
if (Test-Path $pluginWooCheckoutDir -PathType Container) {
    $phpFiles = Get-ChildItem -Path $pluginWooCheckoutDir -Filter '*.php' -File
    foreach ($phpFile in $phpFiles) {
        $pluginRelative = "woocommerce\checkout\$($phpFile.Name)"
        $repoRelative   = "templates\woocommerce\checkout\$($phpFile.Name)"
        $mappings[$pluginRelative] = $repoRelative
    }
} else {
    Write-Warning "woocommerce\checkout\ not found under plugin dir — skipping dynamic PHP mapping."
}

# ---------------------------------------------------------------------------
# Process mappings
# ---------------------------------------------------------------------------

$countCopied  = 0
$countSkipped = 0
$countMissing = 0

foreach ($entry in $mappings.GetEnumerator()) {
    $srcRelative  = $entry.Key
    $destRelative = $entry.Value

    $srcPath  = Join-Path $PluginDir   $srcRelative
    $destPath = Join-Path $FrontendRoot $destRelative

    # --- Source missing ---
    if (-not (Test-Path $srcPath -PathType Leaf)) {
        Write-Missing "  MISSING  $srcRelative"
        $countMissing++
        continue
    }

    # --- Compare hashes ---
    $needsCopy = $true
    if (Test-Path $destPath -PathType Leaf) {
        $srcHash  = Get-FileHash256 $srcPath
        $destHash = Get-FileHash256 $destPath
        if ($srcHash -eq $destHash) {
            Write-Skipped "  SKIPPED  $srcRelative  (identical)"
            $countSkipped++
            $needsCopy = $false
        }
    }

    if ($needsCopy) {
        if ($DryRun) {
            Write-Copied "  WOULD COPY  $srcRelative  ->  $destRelative"
        } else {
            # Ensure destination directory exists
            $destParent = Split-Path -Parent $destPath
            if (-not (Test-Path $destParent -PathType Container)) {
                New-Item -ItemType Directory -Force -Path $destParent | Out-Null
            }

            Copy-Item -Path $srcPath -Destination $destPath -Force
            Write-Copied "  COPIED   $srcRelative  ->  $destRelative"
        }
        $countCopied++
    }
}

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------

Write-Host ""
Write-Host "--------------------------------------------"
Write-Host ("  Copied : {0,4}" -f $countCopied)  -ForegroundColor $(if ($countCopied  -gt 0) { 'Green' } else { 'White' })
Write-Host ("  Skipped: {0,4}" -f $countSkipped) -ForegroundColor DarkGray
Write-Host ("  Missing: {0,4}" -f $countMissing) -ForegroundColor $(if ($countMissing -gt 0) { 'Red'   } else { 'White' })
Write-Host "--------------------------------------------"
Write-Host ""

# ---------------------------------------------------------------------------
# Post-copy reminder
# ---------------------------------------------------------------------------

if (-not $DryRun -and $countCopied -gt 0) {
    Write-Host "Files were updated in the repo. Review what changed before committing:" -ForegroundColor Yellow
    Write-Host "    git diff" -ForegroundColor Yellow
    Write-Host ""
}

if ($DryRun) {
    Write-Info "Dry run complete. No files were modified."
}

exit $(if ($countMissing -gt 0) { 1 } else { 0 })
