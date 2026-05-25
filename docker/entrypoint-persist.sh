#!/bin/bash
# JTI WordPress entrypoint: persist wp-content to Azure Files.
#
# Mount: AzFiles share `wp-content` → /persist  (share root mirrors wp-content)
# Layout:
#     /persist/
#     ├── plugins/        ← overlay (rsync-managed; fast PHP includes from local)
#     ├── themes/         ← overlay
#     ├── languages/      ← overlay
#     ├── mu-plugins/     ← overlay
#     └── uploads/        ← symlink target (direct write, no rsync window)
#
# On boot: symlink uploads, seed-or-restore the overlay, start watcher,
# exec upstream WP entrypoint. Persist always wins on overlay conflict.

set -uo pipefail

PERSIST=/persist
WP=/var/www/html/wp-content
DEBOUNCE=10

EXCLUDES=(
  --exclude=uploads/
  --exclude=cache/
  --exclude=litespeed/
  --exclude=jetpack-waf/
  --exclude=updraft/
  --exclude=plugins-old/
  --exclude=upgrade/
  --exclude=upgrade-temp-backup/
  --exclude='*.log'
  --exclude='*.swp'
  --exclude='*~'
  --exclude='*.tmp'
)
# inotify-tools regex (POSIX ERE on full path) — must match EXCLUDES above
INOTIFY_EXCLUDE='/wp-content/(uploads|cache|litespeed|jetpack-waf|updraft|plugins-old|upgrade|upgrade-temp-backup)(/|$)|\.(log|swp|tmp)$|~$'

log() { echo "[wp-persist $(date '+%H:%M:%S')] $*"; }

# Bail-out if /persist isn't mounted: site still starts, just without
# persistence (admin updates will be wiped on next restart). Fail loud, not fatal.
if ! mountpoint -q "$PERSIST" 2>/dev/null; then
  log "WARN: $PERSIST not mounted — admin updates will NOT persist."
  exec /usr/local/bin/docker-entrypoint.sh "$@"
fi

# Uploads: symlink so writes go directly to AzFiles (zero data-loss window).
mkdir -p "$PERSIST/uploads"
rm -rf "$WP/uploads"
ln -sfT "$PERSIST/uploads" "$WP/uploads"
log "symlinked $WP/uploads → $PERSIST/uploads"

# Overlay: seed /persist on first boot, otherwise restore /persist → /wp-content.
#
# Seed completion is tracked by a marker file (.seed-complete) so an
# interrupted seed (e.g. App Service container-start timeout) doesn't leave
# /persist in a half-populated state that the next boot mistakes for "ready".
#
# Restore intentionally does NOT use --delete: image-only files (e.g. a theme
# added in a new image but not yet in /persist) stay alive. Admin deletions
# via wp-admin don't persist across restart this way — that's a deliberate
# trade against the catastrophic case where a partial /persist would prune
# the live wp-content. The watcher pushes deletions to /persist, but on
# restart the image's baseline is restored on top.
SEED_MARKER="$PERSIST/.seed-complete"

if [ -f "$SEED_MARKER" ]; then
  log "restoring $WP from $PERSIST (no --delete: image-only files preserved)"
  rsync -a "${EXCLUDES[@]}" "$PERSIST/" "$WP/" \
    && log "restore complete" \
    || log "WARN: restore rsync exited non-zero (continuing)"
else
  log "no seed marker — seeding $PERSIST from image"
  if rsync -a "${EXCLUDES[@]}" "$WP/" "$PERSIST/"; then
    touch "$SEED_MARKER"
    log "seed complete (marker created)"
  else
    log "WARN: seed rsync exited non-zero — marker NOT created, will re-seed next boot"
  fi
fi
chown -R www-data:www-data "$WP" 2>/dev/null || true

# Background watcher: debounced rsync of wp-content → /persist on change.
(
  while true; do
    inotifywait -r -q -e create,modify,delete,move \
        --exclude "$INOTIFY_EXCLUDE" "$WP" >/dev/null 2>&1 \
      || { sleep 5; continue; }
    # Drain follow-up events for DEBOUNCE seconds — a plugin install touches
    # dozens of files; we want one rsync at the end, not dozens.
    while inotifywait -r -q -t "$DEBOUNCE" -e create,modify,delete,move \
        --exclude "$INOTIFY_EXCLUDE" "$WP" >/dev/null 2>&1; do : ; done
    rsync -a --delete "${EXCLUDES[@]}" "$WP/" "$PERSIST/" \
      && log "synced to /persist" \
      || log "WARN: sync rsync failed (will retry on next event)"
  done
) &
log "watcher backgrounded (PID $!)"

exec /usr/local/bin/docker-entrypoint.sh "$@"
