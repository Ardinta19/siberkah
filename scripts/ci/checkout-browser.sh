#!/usr/bin/env bash
set -euo pipefail
[[ ${GITHUB_ACTIONS:-} == true && ${APP_ENV:-} == testing && ${DB_DATABASE:-} == siberkah_test ]]
sql() { docker exec -i -e MYSQL_PWD="$DB_PASSWORD" "$CI_DB_CONTAINER" mariadb -uroot "$@"; }
sql -e 'CREATE DATABASE siberkah_checkout_ci'
export DB_DATABASE=siberkah_checkout_ci RENTAL_CHECKOUT_STAGING=true CHECKOUT_PRIVACY_VERSION=browser-ci SESSION_DRIVER=file APP_URL=http://127.0.0.1:8127
pid=''
cleanup() { if [[ -n $pid ]]; then kill "$pid" || true; fi; sql -e 'DROP DATABASE siberkah_checkout_ci'; }
trap cleanup EXIT
php artisan migrate --force >/dev/null
(cd public && exec php -S 127.0.0.1:8127 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php) >"$RUNNER_TEMP/checkout-browser.log" 2>&1 & pid=$!
python scripts/ci/checkout-browser.py
[[ $(sql -NB "$DB_DATABASE" -e 'SELECT COUNT(*) FROM pesanan WHERE jumlah_dibayar=0 AND tunai_saat_serah=1') == 2 ]]
[[ $(sql -NB "$DB_DATABASE" -e "SELECT COUNT(*) FROM pembayaran WHERE jenis='dp' AND tagihan=135000 AND dikonfirmasi_pelanggan_pada IS NOT NULL AND status='menunggu'") == 1 ]]
