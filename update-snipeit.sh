#!/usr/bin/env bash
# Update Killa Asset (Snipe-IT fork) to a new upstream release and deploy it.
#
# Usage:   SERVER=user@host ./update-snipeit.sh v8.9.0
#          SKIP_MERGE=1 SERVER=user@host ./update-snipeit.sh v8.9.0  # after resolving conflicts
#
# What it does:
#   1. Fetches the upstream tag from grokability/snipe-it and merges it into
#      the current branch. All custom code (floating-license plugin, branding)
#      lives as commits on top of upstream, so the merge preserves it. Custom
#      blocks are marked with "[floating-licenses addon]" comments — if a
#      conflict hits one, keep BOTH sides and re-apply the addon block.
#   2. Archives the merged tree and ships it to the server.
#   3. Runs snipeit-apply-update.sh on the server (backup, rsync, composer,
#      migrate, caches).
#
# Prereqs: ssh key auth to the server (ssh user@host works without a
# password), or set SSH="plink -pw ..." below.
set -e

TAG=$1
SSH=${SSH:-ssh}
SCP=${SCP:-scp}

[ -n "$TAG" ] || { echo "usage: SERVER=user@host $0 <upstream-tag>  e.g. SERVER=root@example.com $0 v8.9.0"; exit 1; }
[ -n "${SERVER:-}" ] || { echo "error: SERVER env var is required (SERVER=user@host $0 $TAG)"; exit 1; }

cd "$(dirname "$0")"

if [ "${SKIP_MERGE:-0}" = "1" ]; then
    [ -z "$(git ls-files -u)" ] && [ -z "$(git status --porcelain)" ] || {
        echo "error: SKIP_MERGE=1 requires a clean tree with no merge in progress"; exit 1; }
    echo "==> SKIP_MERGE=1: skipping fetch/merge"
else
    echo "==> Fetch upstream"
    git remote get-url upstream >/dev/null 2>&1 || \
        git remote add upstream https://github.com/grokability/snipe-it.git
    git fetch upstream --tags

    echo "==> Merge $TAG"
    if ! git merge "$TAG" --no-edit; then
        echo "!! Resolve the conflicts (watch for '[floating-licenses addon]' and"
        echo "!! branding blocks — keep both sides), then: git add -A && git commit"
        echo "!! and re-run this script with: SKIP_MERGE=1 SERVER=$SERVER $0 $TAG"
        exit 1
    fi
fi

echo "==> Archive + ship"
PKG=/tmp/snipeit-$TAG-killa.tar.gz
git archive --format=tar.gz -o "$PKG" HEAD
$SCP "$PKG" "$SERVER:/tmp/snipeit-update.tar.gz"

echo "==> Apply on server"
$SSH "$SERVER" "bash /usr/local/sbin/snipeit-apply-update.sh /tmp/snipeit-update.tar.gz"

echo "==> Push fork"
git push origin HEAD

echo "==> Updated to $TAG"
