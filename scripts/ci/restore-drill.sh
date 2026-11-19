#!/usr/bin/env bash
# CI-only: use disposable MariaDB containers, never read the operator's .env.
set -euo pipefail
umask 077
[[ ${GITHUB_ACTIONS:-} == true && ${APP_ENV:-} == testing && ${DB_DATABASE:-} == siberkah_test ]]
: "${CI_DB_CONTAINER:?MariaDB service container is required}"
work=$(mktemp -d); target=''; start=$SECONDS
cleanup() { if [[ -n $target ]]; then docker rm -f "$target" >/dev/null; fi; rm -rf "$work"; }
trap cleanup EXIT
src() { docker exec -i -e MYSQL_PWD="$DB_PASSWORD" "$CI_DB_CONTAINER" "$@"; }
# A marker after migrations makes an empty restore distinguishable from a successful one.
src mariadb -uroot siberkah_test -e "INSERT INTO kategori(nama,slug) VALUES ('Pemulihan CI','restore-ci');"
src mariadb-dump -uroot --single-transaction --quick --routines --triggers --events --hex-blob siberkah_test | gzip > "$work/database.sql.gz"
# Use the same DB name but no network: qualified views cannot silently point to the source database.
target=$(docker run -d --network none -e MARIADB_ROOT_PASSWORD=restore_ci_only -e MARIADB_DATABASE=siberkah_test mariadb:11.4)
ready=false
for _ in {1..60}; do
    if docker exec "$target" healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; then ready=true; break; fi
    sleep 1
done
[[ $ready == true ]]
dst() { docker exec -i -e MYSQL_PWD=restore_ci_only "$target" mariadb -uroot siberkah_test "$@"; }
gzip -dc "$work/database.sql.gz" | dst
query="SELECT COUNT(*) FROM migrations; SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE(); SELECT COUNT(*) FROM checkout_bank; SELECT slug FROM kategori WHERE slug='restore-ci';"
src mariadb -uroot -NB siberkah_test -e "$query" > "$work/source.txt"
dst -NB -e "$query" > "$work/restored.txt"
grep -qx 'restore-ci' "$work/restored.txt"; diff -u "$work/source.txt" "$work/restored.txt"
src mariadb -uroot siberkah_test -e "DELETE FROM kategori WHERE slug='restore-ci';"
printf 'Pemulihan basis data sintetis: berhasil, %ss (termasuk startup target).\n' "$((SECONDS-start))" | tee -a "$GITHUB_STEP_SUMMARY"
