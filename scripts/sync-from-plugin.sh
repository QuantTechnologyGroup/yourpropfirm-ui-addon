#!/usr/bin/env bash
# sync-from-plugin.sh — copy source files FROM the plugin repo INTO this frontend repo.
# Usage: ./scripts/sync-from-plugin.sh --plugin-dir /path/to/yourpropfirm-plugin [--dry-run]

set -euo pipefail

# ---------------------------------------------------------------------------
# Argument parsing
# ---------------------------------------------------------------------------
PLUGIN_DIR=""
DRY_RUN=false

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

if [[ -z "$PLUGIN_DIR" ]]; then
  echo "Error: --plugin-dir is required." >&2
  echo "Usage: $0 --plugin-dir <path> [--dry-run]" >&2
  exit 1
fi

if [[ ! -d "$PLUGIN_DIR" ]]; then
  echo "Error: plugin directory does not exist: $PLUGIN_DIR" >&2
  exit 1
fi

# Resolve to absolute path
PLUGIN_DIR="$(cd "$PLUGIN_DIR" && pwd)"

# Repo root = directory that contains this script's parent
REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# ---------------------------------------------------------------------------
# Static file mappings (parallel indexed arrays)
# source paths are relative to PLUGIN_DIR
# dest paths are relative to REPO_DIR
# ---------------------------------------------------------------------------
SRCS=(
  "public/src/css/checkout.css"
  "public/src/css/components.css"
  "public/css/checkout.css"
  "public/js/checkout.js"
  "public/js/dark-mode.js"
  "public/partials/checkout/addons.php"
  "public/partials/checkout/trading-platform.php"
)

DESTS=(
  "src/css/checkout.css"
  "src/css/components.css"
  "dist/css/checkout.css"
  "js/checkout.js"
  "js/dark-mode.js"
  "templates/partials/checkout/addons.php"
  "templates/partials/checkout/trading-platform.php"
)

# ---------------------------------------------------------------------------
# Dynamically append woocommerce/checkout/*.php mappings
# ---------------------------------------------------------------------------
WC_SRC_DIR="$PLUGIN_DIR/woocommerce/checkout"
WC_DEST_DIR="templates/woocommerce/checkout"

if [[ -d "$WC_SRC_DIR" ]]; then
  while IFS= read -r -d '' php_file; do
    rel_src="woocommerce/checkout/$(basename "$php_file")"
    rel_dest="$WC_DEST_DIR/$(basename "$php_file")"
    SRCS+=("$rel_src")
    DESTS+=("$rel_dest")
  done < <(find "$WC_SRC_DIR" -maxdepth 1 -name '*.php' -print0 | sort -z)
fi

# ---------------------------------------------------------------------------
# Preview what will be copied
# ---------------------------------------------------------------------------
echo ""
echo "Syncing FROM plugin: $PLUGIN_DIR"
echo "           TO repo:  $REPO_DIR"
echo ""
echo "Files to copy:"
for i in "${!SRCS[@]}"; do
  echo "  ${SRCS[$i]}"
  echo "    -> ${DESTS[$i]}"
done
echo ""

# ---------------------------------------------------------------------------
# Confirmation prompt (skipped in dry-run mode)
# ---------------------------------------------------------------------------
if [[ "$DRY_RUN" == true ]]; then
  echo "[dry-run] No files will be copied."
  exit 0
fi

read -rp "Continue? (y/N): " confirm
if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
  echo "Aborted."
  exit 0
fi

# ---------------------------------------------------------------------------
# Copy files
# ---------------------------------------------------------------------------
COPIED=0
SKIPPED=0
ERRORS=0

for i in "${!SRCS[@]}"; do
  src="$PLUGIN_DIR/${SRCS[$i]}"
  dest="$REPO_DIR/${DESTS[$i]}"

  if [[ ! -f "$src" ]]; then
    echo "  [skip] source not found: ${SRCS[$i]}"
    (( SKIPPED++ )) || true
    continue
  fi

  dest_dir="$(dirname "$dest")"
  if [[ ! -d "$dest_dir" ]]; then
    if mkdir -p "$dest_dir"; then
      echo "  [mkdir] $dest_dir"
    else
      echo "  [error] could not create directory: $dest_dir" >&2
      (( ERRORS++ )) || true
      continue
    fi
  fi

  if cp "$src" "$dest"; then
    echo "  [copy]  ${SRCS[$i]} -> ${DESTS[$i]}"
    (( COPIED++ )) || true
  else
    echo "  [error] failed to copy: ${SRCS[$i]}" >&2
    (( ERRORS++ )) || true
  fi
done

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
echo ""
echo "Done. Copied: $COPIED  Skipped: $SKIPPED  Errors: $ERRORS"

if [[ "$ERRORS" -gt 0 ]]; then
  echo "Warning: $ERRORS error(s) occurred." >&2
fi

echo ""
echo "Suggestion: review changes with"
echo "  git -C \"$REPO_DIR\" diff"
echo "  git -C \"$REPO_DIR\" status"
