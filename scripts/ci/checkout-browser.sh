#!/usr/bin/env bash
set -euo pipefail
[[ ${GITHUB_ACTIONS:-} == true && ${APP_ENV:-} == testing && ${DB_DATABASE:-} == siberkah_test ]]
sql() { docker exec -i -e MYSQL_PWD="$DB_PASSWORD" "$CI_DB_CONTAINER" mariadb -uroot "$@"; }
sql -e 'CREATE DATABASE siberkah_checkout_ci'
export DB_DATABASE=siberkah_checkout_ci RENTAL_CHECKOUT_STAGING=true CHECKOUT_PRIVACY_VERSION=browser-ci SESSION_DRIVER=file APP_URL=http://127.0.0.1:8127
export CHECKOUT_CI_CLOCK="$RUNNER_TEMP/checkout-clock.txt"
pid=''
cleanup() { rm -f "$CHECKOUT_CI_CLOCK"; if [[ -n $pid ]]; then kill "$pid" || true; fi; sql -e 'DROP DATABASE siberkah_checkout_ci'; }
trap cleanup EXIT
php artisan migrate --force >/dev/null
(cd public && exec php -S 127.0.0.1:8127 ../scripts/ci/checkout-router.php) >"$RUNNER_TEMP/checkout-browser.log" 2>&1 & pid=$!
python scripts/ci/checkout-browser.py
[[ $(sql -NB "$DB_DATABASE" -e "SELECT COUNT(*) FROM pesanan WHERE jumlah_dibayar=270000 AND saldo=0 AND status='sudah_diserahkan'") == 1 ]]
[[ $(sql -NB "$DB_DATABASE" -e 'SELECT COUNT(*) FROM pembayaran WHERE nominal_diterima IS NOT NULL') == 2 ]]
[[ $(sql -NB "$DB_DATABASE" -e 'SELECT COUNT(*) FROM mutasi_masuk') == 1 ]]
[[ $(sql -NB "$DB_DATABASE" -e 'SELECT COUNT(*) FROM setoran_kas_antar WHERE sebelum=0 AND sesudah=135000') == 1 ]]
