#!/usr/bin/env bash
# test-upgrade-regression.sh — fast Killa regression suite for upstream upgrades.
# Runs the tests most likely to catch an upstream-merge regression.
set -uo pipefail
cd "$(dirname "$0")/.."

if [ ! -f vendor/bin/phpunit ]; then
  echo "vendor/bin/phpunit not found — run 'composer install' first." >&2
  exit 1
fi

FAIL=0
run() { echo "== $1"; shift; "$@" || FAIL=1; }

run "Floating Licenses package" vendor/bin/phpunit --testsuite FloatingLicenses
run "Perpetual license tests"   vendor/bin/phpunit --filter 'UpdateLicenseTest|ImportLicenseTest'
run "LDAP deactivate-missing"   vendor/bin/phpunit --filter 'LdapDeactivateMissingTest|LdapWizardTest'
run "License feature core"      vendor/bin/phpunit tests/Feature/Licenses
run "Smoke: app boots + auth"   vendor/bin/phpunit --filter 'SettingsTest|AuthenticationTest'

if [ "$FAIL" -eq 0 ]; then
  echo; echo "UPGRADE REGRESSION SUITE: PASS"
else
  echo; echo "UPGRADE REGRESSION SUITE: FAILURES — see above"
  exit 1
fi
