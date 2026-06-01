<#
.SYNOPSIS
    Syncs checkout-frontend build artifacts and source files to the YourPropFirm plugin directory.

.DESCRIPTION
    Compares SHA256 hashes of source and destination files, copies only changed files,
    and reminds the developer to run `npm run build` when CSS source files are updated.

.PARAMETER PluginDir
    Mandatory. Absolute path to the yourpropfirm-plugin directory.
    Must contain yourpropfirm.php.

.PARAMETER DryRun
    Switch. When present, prints what WOULD be copied without making any changes.

.EXAMPLE
    .\sync-to-plugin.ps1 -PluginDir "C:\Users\ADVAN\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin"

.EXAMPLE
    .\sync-to-plugin.ps1 -PluginDir "C:\Users\ADVAN\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin" -DryRun
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

Write-Info "Frontend root : $FrontendRoot"
Write-Info "Plugin dir    : $PluginDir"
if ($DryRun) {
    Write-Info "[DRY RUN] No files will be written."
}
Write-Host ""

# ---------------------------------------------------------------------------
# Static file mappings  (repo-relative -> plugin-relative)
# ---------------------------------------------------------------------------

$mappings = [ordered]@{
    'src\css\checkout.css'                                = 'public\src\css\checkout.css'
    'src\css\components.css'                              = 'public\src\css\components.css'
    'dist\css\checkout.css'                               = 'public\css\checkout.css'
    'js\checkout.js'                                      = 'public\js\checkout.js'
    'js\dark-mode.js'                                     = 'public\js\dark-mode.js'
    'templates\partials\checkout\addons.php'              = 'public\partials\checkout\addons.php'
    'templates\partials\checkout\trading-platform.php'    = 'public\partials\checkout\trading-platform.php'
}

# ---------------------------------------------------------------------------
# Dynamically add templates\woocommerce\checkout\*.php
# ---------------------------------------------------------------------------

$wooCheckoutTemplateDir = Join-Path $FrontendRoot 'templates\woocommerce\checkout'
if (Test-Path $wooCheckoutTemplateDir -PathType Container) {
    $phpFiles = Get-ChildItem -Path $wooCheckoutTemplateDir -Filter '*.php' -File
    foreach ($phpFile in $phpFiles) {
        $repoRelative   = "templates\woocommerce\checkout\$($phpFile.Name)"
        $pluginRelative = "woocommerce\checkout\$($phpFile.Name)"
        $mappings[$repoRelative] = $pluginRelative
    }
} else {
    Write-Warning "templates\woocommerce\checkout\ not found under frontend root — skipping dynamic PHP mapping."
}

# ---------------------------------------------------------------------------
# Optionally add templates\woocommerce\single-checkout.php
# ---------------------------------------------------------------------------

$singleCheckout = 'templates\woocommerce\single-checkout.php'
$singleCheckoutFull = Join-Path $FrontendRoot $singleCheckout
if (Test-Path $singleCheckoutFull -PathType Leaf) {
    $mappings[$singleCheckout] = 'woocommerce\single-checkout.php'
}

# ---------------------------------------------------------------------------
# Process mappings
# ---------------------------------------------------------------------------

$countCopied  = 0
$countSkipped = 0
$countMissing = 0
$cssSourceChanged = $false

$cssSourceKeys = @(
    'src\css\checkout.css',
    'src\css\components.css'
)

foreach ($entry in $mappings.GetEnumerator()) {
    $srcRelative  = $entry.Key
    $destRelative = $entry.Value

    $srcPath  = Join-Path $FrontendRoot $srcRelative
    $destPath = Join-Path $PluginDir    $destRelative

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

        # Track whether a CSS source file changed (only relevant for non-dry-run)
        if (-not $DryRun -and ($cssSourceKeys -contains $srcRelative)) {
            $cssSourceChanged = $true
        }
    }
}

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------

Write-Host ""
Write-Host "--------------------------------------------"
Write-Host ("  Copied : {0,4}" -f $countCopied)  -ForegroundColor $(if ($countCopied  -gt 0) { 'Green'    } else { 'White' })
Write-Host ("  Skipped: {0,4}" -f $countSkipped) -ForegroundColor DarkGray
Write-Host ("  Missing: {0,4}" -f $countMissing) -ForegroundColor $(if ($countMissing -gt 0) { 'Red'      } else { 'White' })
Write-Host "--------------------------------------------"
Write-Host ""

# ---------------------------------------------------------------------------
# Post-copy reminder
# ---------------------------------------------------------------------------

if ($cssSourceChanged) {
    Write-Host "CSS source files were updated. Remember to run:" -ForegroundColor Yellow
    Write-Host "    npm run build" -ForegroundColor Yellow
    Write-Host "inside the plugin directory to regenerate public/css/checkout.css." -ForegroundColor Yellow
    Write-Host ""
}

if ($DryRun) {
    Write-Info "Dry run complete. No files were modified."
}

exit $(if ($countMissing -gt 0) { 1 } else { 0 })
