#!/bin/bash
# pipeline-test-1779873148: # JTI WordPress entrypoint: rsync overlay between image-baked wp-content and
# the AzFiles share mounted at /persist.
#
# Mount: AzFiles share `wp-content` → /persist  (share root mirrors wp-content)
# Layout:
#     /persist/
#     ├── uploads/        ← symlink target (direct write, no sync window)
#     └── everything else ← bidirectional rsync overlay, polled every 30 s
#
# Design notes:
#   - uploads/ is symlinked (not rsync'd). Storage Explorer / PHP writes land
#     directly on the share and are immediately visible. uploads/ is also
#     allowed to grow without bound — keeping it out of the rsync avoids
#     paying for boot-time scans of GBs of media.
#   - Everything else (plugins/themes/mu-plugins/languages/cache/…) is rsync'd
#     both directions every 30 s with `-u` (skip if dest is newer) and no
#     `--delete`. Semantics: last-mtime-wins on write; deletions don't
#     propagate (delete on both sides if you really mean it).
#   - inotify on the CIFS mount was considered; SMB doesn't reliably deliver
#     cross-client change notifications to local inotify (confirmed 2026-05-25
#     with a 90 s recursive watch on 4179 dirs catching 0 events while a
#     parallel Storage Explorer upload landed on the share). Polling is
#     simpler and correct.
#   - First boot with an empty /persist: the boot rsync is a no-op (source
#     empty); the first poll cycle copies the image baseline (wp-content) →
#     /persist. No seed-marker logic needed.

set -uo pipefail
PERSIST=/persist
WP=/var/www/html/wp-content
POLL_INTERVAL=30

# Sync excludes. uploads is the symlink target (rsync would loop).
# CRITICAL: uploads is anchored (/uploads, no trailing slash) so the pattern
# matches regardless of source-side type. With --exclude=uploads/ (trailing
# slash, no anchor), rsync only excludes DIRECTORIES named uploads — the
# container-side wp-content/uploads is a SYMLINK, which escapes that pattern,
# and rsync then writes the symlink onto the share, clobbering the real
# uploads/ directory there. Symptom: Azure Storage Explorer can't navigate
# into uploads/ (it sees a broken self-referencing symlink). Fix shipped
# 2026-05-26 after the prod migration hit this.
# The rest are excluded because they're either huge (updraft backups can be
# GBs), churny (cache files), or pure noise (logs, swap files) — syncing them
# across SMB on every boot makes the boot rsync exceed App Service's 230 s
# container-start timeout. Reintroduced 2026-05-25 after a boot timeout.
EXCLUDES=(
  --exclude=/uploads
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

log() { echo "[wp-persist $(date '+%H:%M:%S')] $*"; }

# Bail-out if /persist isn't mounted: site still starts, just without
# persistence. Fail loud in the log, not fatal.
if ! mountpoint -q "$PERSIST" 2>/dev/null; then
  log "WARN: $PERSIST not mounted — admin updates will NOT persist."
  exec /usr/local/bin/docker-entrypoint.sh "$@"
fi

# uploads/: symlink so writes go directly to AzFiles (zero data-loss window).
mkdir -p "$PERSIST/uploads"
rm -rf "$WP/uploads"
ln -sfT "$PERSIST/uploads" "$WP/uploads"
log "symlinked $WP/uploads → $PERSIST/uploads"

# Background boot sync: pull /persist into wp-content. Runs in parallel with
# Apache startup so the container makes its 230 s start-probe window even if
# the SMB walk is slow. During the first ~30-60 s after boot, the site may
# serve image-baseline content for files that /persist would later override.
# Acceptable trade — admin-side updates surface within one poll cycle.
#
# -u: skip if wp-content version is newer. Combined with the image baseline
#     being touched to mtime 1970 in the Dockerfile, /persist always wins for
#     any file that has been touched on the share (real mtime > 1970).
# no --delete: image-only files survive a partial /persist.
(
  log "boot sync /persist → wp-content (background)"
  if rsync -au "${EXCLUDES[@]}" "$PERSIST/" "$WP/" 2>/dev/null; then
    log "boot sync done"
  else
    log "WARN: boot sync exited non-zero"
  fi
  chown -R www-data:www-data "$WP" 2>/dev/null || true

  # Opcache refresh after the boot sync (NOT before — that's the whole point).
  # Apache starts immediately (the exec below) and races this background rsync;
  # with opcache.validate_timestamps=1 but a long revalidate_freq (4h), any PHP
  # class opcache compiled from a half-synced wp-content during that race stays
  # pinned for up to 4h — which is how a restart can leave jti-custom routes
  # (e.g. /docs) 404'ing. A one-shot `apache2ctl graceful` once the sync is done
  # spawns fresh workers with an empty opcache, so they recompile against the
  # now-fully-synced code. Startup-only: zero steady-state cost, keeps the 4h
  # revalidate_freq intact. (graceful = finish in-flight requests, no downtime.)
  for _i in $(seq 1 60); do
    [ -f /var/run/apache2/apache2.pid ] && break
    sleep 1
  done
  if [ -f /var/run/apache2/apache2.pid ]; then
    apache2ctl graceful 2>/dev/null \
      && log "graceful Apache reload after boot sync (opcache refreshed)" \
      || log "WARN: graceful reload failed (routes may need a manual opcache reset)"
  else
    log "WARN: apache pidfile not found — skipped post-sync opcache refresh"
  fi
) &
log "boot sync backgrounded (PID $!)"

# Periodic poller: every POLL_INTERVAL seconds, sync both directions.
# wp-content → /persist runs first (push container-side writes — admin
# updates), then /persist → wp-content (pull share-side writes — Storage
# Explorer drops). Both -u so neither direction overwrites a newer dest.
(
  while sleep "$POLL_INTERVAL"; do
    rsync -au "${EXCLUDES[@]}" "$WP/"      "$PERSIST/" 2>/dev/null || true
    rsync -au "${EXCLUDES[@]}" "$PERSIST/" "$WP/"      2>/dev/null || true
  done
) &
log "poller backgrounded (PID $!, interval ${POLL_INTERVAL}s)"

# HTTP Basic Auth toggle. The vhost wraps the auth block in
# <IfDefine BASIC_AUTH>, which only takes effect when httpd is started with
# -DBASIC_AUTH. We append that flag when env var JTI_BASIC_AUTH=true.
# Staging sets JTI_BASIC_AUTH=true; prod leaves it unset.
if [ "${JTI_BASIC_AUTH:-false}" = "true" ]; then
  log "JTI_BASIC_AUTH=true — starting Apache with -DBASIC_AUTH"
  exec /usr/local/bin/docker-entrypoint.sh "$@" -DBASIC_AUTH
else
  log "JTI_BASIC_AUTH not set — Apache will NOT enforce Basic Auth"
  exec /usr/local/bin/docker-entrypoint.sh "$@"
fi
