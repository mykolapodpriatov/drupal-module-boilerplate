#!/usr/bin/env bash
#
# rename-module.sh — Rename this boilerplate to your own Drupal module.
#
# Replaces the machine name (example_starter) and the human-readable name
# (Example Starter) throughout the tree, then renames the example_starter.*
# files. Run it once, right after cloning the template.
#
#   scripts/rename-module.sh \
#     --machine-name=my_new_module \
#     --human-name="My New Module" \
#     [--dry-run]
#
# Works on both macOS/BSD and GNU/Linux: it detects the local `sed -i` flavour.

set -euo pipefail

readonly OLD_MACHINE='example_starter'
readonly OLD_HUMAN='Example Starter'

machine_name=''
human_name=''
dry_run=0

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd -- "${script_dir}/.." && pwd)"
self_name="$(basename -- "${BASH_SOURCE[0]}")"

usage() {
  cat <<'USAGE'
Usage:
  scripts/rename-module.sh --machine-name=NAME --human-name="Human Name" [--dry-run]

Options:
  --machine-name=NAME   New machine name; must match ^[a-z][a-z0-9_]*$
                        (lower-case, starts with a letter, words joined by _).
  --human-name=NAME     New human-readable name, e.g. "My New Module".
  --dry-run             List what would change without touching any file.
  -h, --help            Show this help and exit.
USAGE
}

die() {
  printf 'Error: %s\n' "$*" >&2
  exit 1
}

# Escape the characters that are special on the replacement side of an
# `s|...|...|` sed command: backslash first, then the '|' delimiter and '&'.
escape_replacement() {
  local value="$1"
  value="${value//\\/\\\\}"
  value="${value//|/\\|}"
  value="${value//&/\\&}"
  printf '%s' "$value"
}

# --- Parse arguments -------------------------------------------------------
for arg in "$@"; do
  case "$arg" in
    --machine-name=*) machine_name="${arg#*=}" ;;
    --human-name=*)   human_name="${arg#*=}" ;;
    --dry-run)        dry_run=1 ;;
    -h | --help)      usage; exit 0 ;;
    *) die "Unknown argument '$arg' (try --help)." ;;
  esac
done

# --- Validate --------------------------------------------------------------
[ -n "$machine_name" ] || die "--machine-name is required (try --help)."
[ -n "$human_name" ] || die "--human-name is required (try --help)."

if [[ ! "$machine_name" =~ ^[a-z][a-z0-9_]*$ ]]; then
  die "--machine-name '$machine_name' is invalid: it must match ^[a-z][a-z0-9_]*\$ (lower-case, start with a letter, words joined by underscores)."
fi

[ "$machine_name" != "$OLD_MACHINE" ] || die "--machine-name is still '$OLD_MACHINE'; pick a new machine name."
[ "$human_name" != "$OLD_HUMAN" ] || die "--human-name is still '$OLD_HUMAN'; pick a new human-readable name."

# --- Detect the local `sed -i` flavour -------------------------------------
# BSD sed (macOS) needs an explicit (empty) suffix argument after -i;
# GNU sed (Linux) must NOT receive one.
case "$(uname -s)" in
  Darwin | *BSD*) sed_inplace=(sed -i '') ;;
  *)              sed_inplace=(sed -i) ;;
esac

machine_repl="$(escape_replacement "$machine_name")"
human_repl="$(escape_replacement "$human_name")"

cd -- "$repo_root"

printf 'Renaming boilerplate:\n'
printf '  machine name : %s -> %s\n' "$OLD_MACHINE" "$machine_name"
printf '  human name   : %s -> %s\n' "$OLD_HUMAN" "$human_name"
if [ "$dry_run" -eq 1 ]; then
  printf '  mode         : DRY RUN (nothing will be changed)\n'
fi
printf '\n'

# --- Rewrite file contents -------------------------------------------------
# The script excludes itself so it does not rewrite its own guard strings.
content_changed=0
printf 'Files with content to update:\n'
while IFS= read -r -d '' file; do
  file="${file#./}"
  printf '  %s\n' "$file"
  content_changed=$((content_changed + 1))
  if [ "$dry_run" -eq 0 ]; then
    "${sed_inplace[@]}" \
      -e "s|${OLD_MACHINE}|${machine_repl}|g" \
      -e "s|${OLD_HUMAN}|${human_repl}|g" \
      "$file"
  fi
done < <(
  # --null emits NUL-separated names on both GNU and BSD grep (unlike -Z,
  # which BSD grep does not treat as a NUL separator).
  grep -rlI --null \
    --exclude-dir=.git \
    --exclude-dir=vendor \
    --exclude-dir=node_modules \
    --exclude="$self_name" \
    -e "$OLD_MACHINE" -e "$OLD_HUMAN" \
    .
)
[ "$content_changed" -gt 0 ] || printf '  (none)\n'

# --- Rename example_starter.* file prefixes --------------------------------
renamed=0
printf '\nFiles to rename:\n'
for f in "$OLD_MACHINE".*; do
  [ -e "$f" ] || continue
  new="${f/${OLD_MACHINE}/${machine_name}}"
  printf '  %s -> %s\n' "$f" "$new"
  renamed=$((renamed + 1))
  if [ "$dry_run" -eq 0 ]; then
    mv -- "$f" "$new"
  fi
done
[ "$renamed" -gt 0 ] || printf '  (none)\n'

# --- Summary ---------------------------------------------------------------
printf '\n'
if [ "$dry_run" -eq 1 ]; then
  printf 'Dry run complete: %d file(s) would be updated, %d file(s) would be renamed. Nothing was changed.\n' \
    "$content_changed" "$renamed"
else
  printf 'Done: updated %d file(s), renamed %d file(s).\n' "$content_changed" "$renamed"
  printf 'Next: review the diff, then run "rm -rf .git && git init" to start a fresh history.\n'
fi
