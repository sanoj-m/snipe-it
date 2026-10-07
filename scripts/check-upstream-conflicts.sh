#!/usr/bin/env bash
# check-upstream-conflicts.sh <upstream-tag>
# Read-only: reports which files changed between our upstream base and the
# target tag intersect with files Killa modifies. Does NOT modify the repo.
set -euo pipefail

TAG="${1:?Usage: $0 <upstream-tag>  e.g. $0 v8.9.0}"

BASE=$(git describe --tags "$(git merge-base HEAD upstream/master 2>/dev/null)" 2>/dev/null || true)
if [ -z "${BASE}" ]; then
  echo "Could not determine upstream base. Is the 'upstream' remote fetched?"; exit 1
fi

git fetch upstream --tags --quiet || true
if ! git rev-parse -q --verify "refs/tags/${TAG}" >/dev/null; then
  echo "Tag ${TAG} not found. Run: git fetch upstream --tags"; exit 1
fi

KILLA_CHANGED=$(mktemp); UPSTREAM_CHANGED=$(mktemp); trap 'rm -f "$KILLA_CHANGED" "$UPSTREAM_CHANGED"' EXIT
git diff --name-only "${BASE}..HEAD" | sort -u > "$KILLA_CHANGED"
git diff --name-only "${BASE}..${TAG}" | sort -u > "$UPSTREAM_CHANGED"

UP_COUNT=$(wc -l < "$UPSTREAM_CHANGED" | tr -d ' ')
KIL_COUNT=$(wc -l < "$KILLA_CHANGED" | tr -d ' ')
COLLISIONS=$(comm -12 "$KILLA_CHANGED" "$UPSTREAM_CHANGED" || true)
COL_COUNT=$(echo -n "$COLLISIONS" | grep -c . || true)

echo "UPSTREAM UPGRADE RISK REPORT"
echo "============================"
echo "Current base:   ${BASE}"
echo "Incoming:       ${TAG}"
echo ""
echo "Files changed upstream (${BASE}..${TAG}): ${UP_COUNT}"
echo "Files changed by Killa  (${BASE}..HEAD): ${KIL_COUNT}"
echo "Direct collision candidates:             ${COL_COUNT}"
echo ""

if [ "${COL_COUNT}" -gt 0 ]; then
  echo "COLLISION CANDIDATES (upstream changed AND Killa modified):"
  echo "$COLLISIONS" | while read -r f; do
    risk=""
    case "$f" in
      app/Http/Controllers/*|app/Http/Transformers/*|app/Policies/*|routes/*|config/*) risk="  [HIGH]" ;;
      resources/views/*|app/Presenters/*|app/Models/*) risk="  [MEDIUM]" ;;
    esac
    echo "  ${f}${risk}"
  done
  echo ""
fi

echo "REGISTERED CORE PATCHES in collision set:"
if [ -f docs/customizations/CORE_PATCH_REGISTER.md ]; then
  echo "$COLLISIONS" | while read -r f; do
    grep -F "$f" docs/customizations/CORE_PATCH_REGISTER.md | sed 's/^/  /' || true
  done
else
  echo "  (CORE_PATCH_REGISTER.md not found)"
fi
echo ""

echo "MIGRATION COLLISION CHECK:"
echo "  Killa migrations:"; git diff --name-only "${BASE}..HEAD" -- database/migrations packages/floating-licenses/database/migrations | sed 's/^/    /' || true
echo "  Upstream migrations in ${TAG}:"; git diff --name-only "${BASE}..${TAG}" -- database/migrations | sed 's/^/    /' || true
echo ""

echo "DEPENDENCY CHANGES upstream (${BASE}..${TAG}):"
git diff --stat "${BASE}..${TAG}" -- composer.json composer.lock package.json package-lock.json | sed 's/^/  /' || true
echo ""
echo "Report only — repository unchanged."
