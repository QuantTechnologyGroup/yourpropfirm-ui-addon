#!/usr/bin/env bash
set -euo pipefail

# ---------------------------------------------------------------------------
# sync-to-plugin.sh
# Syncs checkout-frontend build artefacts into the YourPropFirm WP plugin dir.
# ---------------------------------------------------------------------------

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
FRONTEND_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

PLUGIN_DIR=""
DRY_RUN=false

# ---------------------------------------------------------------------------
# Argument parsing
# ---------------------------------------------------------------------------
while [[ $# -gt 0 ]]; do
  case "$1" in
    --plugin-dir)
      PLUGIN_DIR="$2"
      shift 2
      ;;
    --dry-run)
      DRY_RUN=true
      shift
      ;;
    *)
      echo "Unknown argument: $1" >&2
      echo "Usage: $0 --plugin-dir <path> [--dry-run]" >&2
      exit 1
      ;;
  esac
done

# ---------------------------------------------------------------------------
# Validate arguments
# ---------------------------------------------------------------------------
if [[ -z "$PLUGIN_DIR" ]]; then
  echo "Error: --plugin-dir is required." >&2
  exit 1
fi

if [[ ! -f "${PLUGIN_DIR}/yourpropfirm.php" ]]; then
  echo "Error: '${PLUGIN_DIR}' does not look like the YourPropFirm plugin directory." >&2
  echo "       Expected to find yourpropfirm.php inside it." >&2
  exit 1
fi

# ---------------------------------------------------------------------------
# Build parallel arrays: SRC_FILES (relative to FRONTEND_ROOT)
#                        DST_FILES (relative to PLUGIN_DIR)
# ---------------------------------------------------------------------------
SRC_FILES=()
DST_FILES=()

# Static mappings
SRC_FILES+=( "src/css/checkout.css" )
DST_FILES+=( "public/src/css/checkout.css" )

SRC_FILES+=( "src/css/components.css" )
DST_FILES+=( "public/src/css/components.css" )

SRC_FILES+=( "dist/css/checkout.css" )
DST_FILES+=( "public/css/checkout.css" )

SRC_FILES+=( "js/checkout.js" )
DST_FILES+=( "public/js/checkout.js" )

SRC_FILES+=( "js/dark-mode.js" )
DST_FILES+=( "public/js/dark-mode.js" )

SRC_FILES+=( "templates/partials/checkout/addons.php" )
DST_FILES+=( "public/partials/checkout/addons.php" )

SRC_FILES+=( "templates/partials/checkout/trading-platform.php" )
DST_FILES+=( "public/partials/checkout/trading-platform.php" )

# Dynamic: templates/woocommerce/checkout/*.php
WC_SRC_DIR="${FRONTEND_ROOT}/templates/woocommerce/checkout"
if [[ -d "$WC_SRC_DIR" ]]; then
  while IFS= read -r -d '' php_file; do
    rel_src="templates/woocommerce/checkout/$(basename "$php_file")"
    rel_dst="woocommerce/checkout/$(basename "$php_file")"
    SRC_FILES+=( "$rel_src" )
    DST_FILES+=( "$rel_dst" )
  done < <(find "$WC_SRC_DIR" -maxdepth 1 -name "*.php" -print0 | sort -z)
fi

# ---------------------------------------------------------------------------
# Helper: compute a hash for a file (sha256sum preferred, md5sum fallback)
# ---------------------------------------------------------------------------
file_hash() {
  local file="$1"
  if command -v sha256sum &>/dev/null; then
    sha256sum "$file" | awk '{print $1}'
  elif command -v md5sum &>/dev/null; then
    md5sum "$file" | awk '{print $1}'
  else
    # Last resort: use cksum (always available on POSIX)
    cksum "$file" | awk '{print $1 $2}'
  fi
}

# ---------------------------------------------------------------------------
# Sync loop
# ---------------------------------------------------------------------------
COPIED=0
SKIPPED=0
MISSING=0
CSS_CHANGED=false

echo "Syncing from: ${FRONTEND_ROOT}"
echo "Syncing to:   ${PLUGIN_DIR}"
echo "Dry-run:      ${DRY_RUN}"
echo "---"

for i in "${!SRC_FILES[@]}"; do
  src_rel="${SRC_FILES[$i]}"
  dst_rel="${DST_FILES[$i]}"

  src_abs="${FRONTEND_ROOT}/${src_rel}"
  dst_abs="${PLUGIN_DIR}/${dst_rel}"

  # Check source exists
  if [[ ! -f "$src_abs" ]]; then
    echo "MISSING  ${src_rel}"
    (( MISSING++ )) || true
    continue
  fi

  # Compare hashes if destination exists
  if [[ -f "$dst_abs" ]]; then
    src_hash="$(file_hash "$src_abs")"
    dst_hash="$(file_hash "$dst_abs")"
    if [[ "$src_hash" == "$dst_hash" ]]; then
      echo "SKIP     ${src_rel}  (identical)"
      (( SKIPPED++ )) || true
      continue
    fi
  fi

  # Determine if this is a CSS file for the post-sync reminder
  if [[ "$src_rel" == *.css ]]; then
    CSS_CHANGED=true
  fi

  if [[ "$DRY_RUN" == true ]]; then
    echo "WOULD COPY  ${src_rel}  ->  ${dst_rel}"
    (( COPIED++ )) || true
  else
    dst_dir="$(dirname "$dst_abs")"
    mkdir -p "$dst_dir"
    cp "$src_abs" "$dst_abs"
    echo "COPIED   ${src_rel}  ->  ${dst_rel}"
    (( COPIED++ )) || true
  fi
done

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
echo "---"
if [[ "$DRY_RUN" == true ]]; then
  echo "Dry-run complete. Would copy: ${COPIED}  |  Skip: ${SKIPPED}  |  Missing: ${MISSING}"
else
  echo "Done. Copied: ${COPIED}  |  Skipped: ${SKIPPED}  |  Missing: ${MISSING}"
fi

# Reminder if CSS was (or would be) copied
if [[ "$CSS_CHANGED" == true ]]; then
  echo ""
  echo "NOTE: CSS files were synced. If you edited source CSS (src/css/) rather than"
  echo "      the compiled output, remember to run 'npm run build' in the plugin directory"
  echo "      before testing, so Tailwind generates the final public/css/checkout.css."
fi

exit 0
