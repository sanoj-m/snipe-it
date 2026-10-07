# Upstream Upgrade Runbook — Killa Asset

Upgrading = merging an upstream **release tag** into Killa. Never `git merge
upstream/master` blindly; never resolve conflicts with plain `ours`/`theirs`.

Three-way mental model for every conflict:

```
BASE     = previous Snipe-IT release (what both sides started from)
UPSTREAM = new Snipe-IT release (bug/security fixes — keep these)
KILLA    = our customization (the intent to re-apply, not necessarily the old code)
Correct resolution = NEW UPSTREAM BEHAVIOR + REAPPLIED KILLA INTENT
```

## 0. Prerequisites

- Clean working tree (`git status`), all work committed.
- Production backup taken (DB dump + uploads + `.env`).
- Read: AGENTS.md, docs/upstream/UPSTREAM_BASELINE.md,
  docs/customizations/CUSTOMIZATION_INVENTORY.md,
  docs/customizations/CORE_PATCH_REGISTER.md, CHANGELOG-KILLA.md.

## 1. Local preparation

```bash
git fetch origin && git fetch upstream --tags
git checkout master && git pull origin master
git tag --list 'v[0-9]*' | sort -V | tail   # pick target tag, e.g. v8.9.0
```

## 2. Risk analysis (before touching anything)

```bash
scripts/check-upstream-conflicts.sh v8.9.0
```

Read the report: upstream-changed files ∩ Killa-modified files = collision
candidates. Cross-reference the CORE_PATCH_REGISTER — every registered patch
whose file appears in the report needs manual attention.

## 3. Upgrade branch and merge

```bash
git checkout -b upgrade/snipeit-8.9.0
git merge v8.9.0 --no-commit --no-ff   # review before committing
# or plain: git merge v8.9.0  — resolve conflicts as they surface
```

For each conflicted file:

1. Identify the registered patch (look for `[floating-licenses addon]` /
   `[killa-v2]` fences and the CORE_PATCH_REGISTER entry).
2. Take the upstream side's *logic*, re-apply the Killa *intent* inside it.
3. If a Killa customization conflicts with an upstream **security** fix
   (auth, policies, CSRF, validation, escaping, uploads, Passport, LDAP/SAML/SCIM):
   redesign the customization around the fix. Never revert the fix.
4. If upstream now provides the feature natively (check release notes), remove
   the Killa patch and record the removal in the register + history.

## 4. Dependencies and build

```bash
composer install                 # upstream composer.json is the baseline
npm ci && npm run prod           # if package.json / frontend changed
php artisan optimize:clear
```

Custom deps to re-verify against upstream requirements:
`snipe-it/floating-licenses` (path package), puppeteer/ssh2 (tooling).

## 5. Migrations

```bash
php artisan migrate --pretend    # inspect first
php artisan migrate              # staging first, never production directly
```

Check: do upstream migrations collide with Killa columns
(`ldap_deactivate_missing`, `perpetual`, `floating_licenses_enabled`,
`floating_license_*` tables)? Killa guards exist but verify. Test rollback of
new migrations where safe.

## 6. Verification gates (all must pass before deploy)

```bash
vendor/bin/pint --test --format agent
vendor/bin/phpstan analyse                 # if configured
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Feature
vendor/bin/phpunit --testsuite FloatingLicenses
scripts/test-upgrade-regression.sh         # Killa smoke suite
```

Manual smoke: login, dashboard, asset list/checkout/checkin, license list/
checkout/checkin (fixed AND floating pool), users, settings save, LDAP settings
wizard, reports, one API call with a token.

Security review: diff upstream's changes to `app/Http/Kernel.php`, `config/`,
`app/Policies/`, auth controllers, Passport/LDAP/SAML/SCIM code — confirm none
were weakened during conflict resolution.

## 7. Deploy (production)

```bash
# 1. Backup: DB dump + storage/app + private_uploads + .env
# 2. php artisan down
# 3. deploy code (git archive/rsync or update-snipeit.sh)
# 4. composer install --no-dev --optimize-autoloader
# 5. php artisan migrate --force
# 6. php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
# 7. php artisan up
# 8. Smoke test: login, dashboard, license flows, floating pool allocation/release
# 9. Monitor storage/logs/laravel.log + web server logs for 30+ min
```

## 8. Rollback

```bash
php artisan down
git checkout <previous-killa-tag>          # or redeploy previous archive
# restore DB backup ONLY if migrations ran and broke something
composer install --no-dev
php artisan optimize:clear && php artisan up
```

Migrations are the irreversible part — the pre-deploy DB backup is mandatory.

## 9. Close out

```bash
git tag killa-X.Y.Z+snipe8.9.0            # naming: killa version + upstream base
git push origin master --tags
```

Update: docs/upstream/UPSTREAM_BASELINE.md (new base tag, new divergence stats),
docs/upgrades/UPGRADE_HISTORY.md (full entry: conflicts, patches reapplied/
removed, migrations, test results), CHANGELOG-KILLA.md, CORE_PATCH_REGISTER.md
(patches added/removed).

## Notes

- `update-snipeit.sh` automates steps 3+7 for the current single-server deploy
  (hardcoded IP; treats merge conflicts per the fence convention). It is a
  convenience, not a replacement for this runbook's analysis and gates.
