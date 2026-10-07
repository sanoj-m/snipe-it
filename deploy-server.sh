#!/bin/bash
# Server-side apply step for Snipe-IT (Killa Asset) updates.
# Expects a git-archive tarball of the fork (already merged with the upstream
# tag) at $1, e.g. /tmp/snipeit-update.tar.gz
# Usage: snipeit-apply-update.sh /tmp/snipeit-update.tar.gz
set -e

APP=/opt/snipe-it
PHP=php8.3
WEBUSER=www-data
TARBALL=$1
STAGE=/opt/snipe-it-stage

[ -f "$TARBALL" ] || { echo "tarball not found: $TARBALL"; exit 1; }

echo "==> Backup"
mkdir -p /root/snipeit-backups
TS=$(date +%Y%m%d_%H%M%S)
DB_NAME=$(grep '^DB_DATABASE=' $APP/.env | cut -d= -f2)
DB_USER=$(grep '^DB_USERNAME=' $APP/.env | cut -d= -f2)
DB_PASS=$(grep '^DB_PASSWORD=' $APP/.env | cut -d= -f2)
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > /root/snipeit-backups/snipeit_db_$TS.sql.gz
tar czf /root/snipeit-backups/snipe-it_files_$TS.tar.gz \
    --exclude='snipe-it/vendor' --exclude='snipe-it/node_modules' \
    --exclude='snipe-it/storage/framework/sessions' \
    --exclude='snipe-it/storage/framework/cache' \
    --ignore-failed-read \
    -C /opt snipe-it || true
echo "    backup: /root/snipeit-backups/*_$TS.*"

echo "==> Stage new code"
rm -rf $STAGE && mkdir -p $STAGE
tar xzf "$TARBALL" -C $STAGE

echo "==> Rsync into $APP (keeping .env, storage, uploads, vendor)"
rsync -a --delete \
    --exclude='vendor/' --exclude='.env' --exclude='storage/' \
    --exclude='public/uploads/' --exclude='.git' --exclude='backups/' \
    --exclude='*.bak-*' \
    $STAGE/ $APP/

cd $APP
chown -R $WEBUSER:$WEBUSER $APP

echo "==> Ensure composer.lock contains snipe-it/floating-licenses"
if ! grep -q 'snipe-it/floating-licenses' composer.lock; then
    $PHP -r '
        $lock = json_decode(file_get_contents("composer.lock"), true);
        $lock["packages"][] = [
            "name" => "snipe-it/floating-licenses",
            "version" => "dev-master",
            "dist" => ["type" => "path", "url" => "packages/floating-licenses", "reference" => "local-floating-licenses"],
            "require" => ["php" => "^8.2"],
            "type" => "library",
            "extra" => ["laravel" => ["providers" => ["SnipeIt\\FloatingLicenses\\FloatingLicensesServiceProvider"]]],
            "autoload" => ["psr-4" => ["SnipeIt\\FloatingLicenses\\" => "src/"]],
            "license" => ["AGPL-3.0-or-later"],
            "description" => "Floating / concurrent software license pools for Snipe-IT",
            "transport-options" => ["symlink" => true, "relative" => true],
        ];
        $content = json_decode(file_get_contents("composer.json"), true);
        $keys = ["name","version","require","require-dev","conflict","replace","provide","minimum-stability","prefer-stable","repositories","extra"];
        $rel = [];
        foreach (array_intersect($keys, array_keys($content)) as $k) { $rel[$k] = $content[$k]; }
        if (isset($content["config"]["platform"])) { $rel["config"]["platform"] = $content["config"]["platform"]; }
        ksort($rel);
        $lock["content-hash"] = md5(json_encode($rel));
        file_put_contents("composer.lock", json_encode($lock, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n");
    '
    echo "    lock patched"
fi

echo "==> Composer install"
if ! sudo -u $WEBUSER composer install --no-dev --prefer-dist --no-interaction; then
    echo "!! composer install failed (usually github.com API rate limit on the"
    echo "!! laravel-scim-server VCS repo). Continuing — vendor/ is unchanged"
    echo "!! from the previous deploy. If this release added new dependencies,"
    echo "!! set a token (composer config -g github-oauth.github.com <token>)"
    echo "!! and re-run: cd $APP && composer install --no-dev --prefer-dist"
fi

echo "==> Regenerate package manifest + autoloader"
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
sudo -u $WEBUSER composer dump-autoload
sudo -u $WEBUSER $PHP artisan package:discover

echo "==> Migrate + caches"
sudo -u $WEBUSER $PHP artisan migrate --force
sudo -u $WEBUSER $PHP artisan optimize:clear
sudo -u $WEBUSER $PHP artisan config:cache

echo "==> Done: $(grep -m1 app_version config/version.php)"
