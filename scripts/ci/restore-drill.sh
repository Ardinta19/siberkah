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
# Synthetic storage roots, no production data or APP_KEY. Both disks and SQL belong to one archive.
mkdir -p "$work/source/public/produk" "$work/source/private/inspeksi" "$work/restored"
php -r '$im=imagecreatetruecolor(800,400); imagewebp($im,$argv[1]); imagesetpixel($im,0,0,255); imagepng($im,$argv[2]);' "$work/source/public/produk/ci.webp" "$work/source/private/inspeksi/ci.png"
src mariadb -uroot siberkah_test <<'SQL'
START TRANSACTION;
INSERT INTO kategori(nama,slug) VALUES ('Pemulihan CI','restore-ci');
INSERT INTO produk(kategori_id,nama,slug,jenis,foto_utama) VALUES (LAST_INSERT_ID(),'CI','restore-ci','sewa','produk/ci.webp');
SET @p=LAST_INSERT_ID();
INSERT INTO pelanggan(nomor_wa,nama) VALUES ('CI-RESTORE','Sintetis');
INSERT INTO pesanan(nomor,pelanggan_id,nama_pemesan,nomor_wa_pemesan,cara_serah,tanggal_serah,total,jumlah_dibayar,saldo) VALUES ('CI-RESTORE',LAST_INSERT_ID(),'Sintetis','CI-RESTORE','antar',CURRENT_DATE,250000,125000,125000);
INSERT INTO item_pesanan(pesanan_id,produk_id,nama_produk,harga_satuan,subtotal) VALUES (LAST_INSERT_ID(),@p,'CI',250000,250000);
SET @i=LAST_INSERT_ID();
INSERT INTO pengguna(nama,surel,kata_sandi,peran,aktif) VALUES ('CI','restore@example.invalid','disabled','staf_kurir',0);
INSERT INTO inspeksi_sewa(item_pesanan_id,tahap,kondisi,pengguna_id,dilakukan_pada) VALUES (@i,'serah','baik',LAST_INSERT_ID(),NOW());
INSERT INTO foto_inspeksi(inspeksi_sewa_id,urutan,jalur_berkas) VALUES (LAST_INSERT_ID(),1,'inspeksi/ci.png');
COMMIT;
SQL
src mariadb-dump -uroot --single-transaction --quick --routines --triggers --events --hex-blob siberkah_test | gzip > "$work/source/database.sql.gz"
(cd "$work/source" && sha256sum database.sql.gz public/produk/ci.webp private/inspeksi/ci.png > SHA256SUMS)
tar -C "$work/source" -czf "$work/backup.tgz" database.sql.gz public private SHA256SUMS
tar -C "$work/restored" -xzf "$work/backup.tgz"
(cd "$work/restored" && sha256sum --check SHA256SUMS)
# Use the same DB name but no network: qualified views cannot silently point to the source database.
target=$(docker run -d --network none -e MARIADB_ROOT_PASSWORD=restore_ci_only -e MARIADB_DATABASE=siberkah_test mariadb:11.4)
ready=false
for _ in {1..60}; do
    if docker exec "$target" healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; then ready=true; break; fi
    sleep 1
done
[[ $ready == true ]]
dst() { docker exec -i -e MYSQL_PWD=restore_ci_only "$target" mariadb -uroot siberkah_test "$@"; }
gzip -dc "$work/restored/database.sql.gz" | dst
query="SELECT COUNT(*) FROM migrations; SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE(); SELECT COUNT(*) FROM checkout_bank; SELECT slug FROM kategori WHERE slug='restore-ci';"
src mariadb -uroot -NB siberkah_test -e "$query" > "$work/source.txt"
dst -NB -e "$query" > "$work/restored.txt"
grep -qx 'restore-ci' "$work/restored.txt"; diff -u "$work/source.txt" "$work/restored.txt"
public=$(dst -NB -e "SELECT foto_utama FROM produk WHERE slug='restore-ci'")
private=$(dst -NB -e "SELECT f.jalur_berkas FROM foto_inspeksi f JOIN inspeksi_sewa i ON i.id=f.inspeksi_sewa_id JOIN item_pesanan t ON t.id=i.item_pesanan_id JOIN pesanan p ON p.id=t.pesanan_id WHERE p.nomor='CI-RESTORE'")
[[ $public == produk/ci.webp && $private == inspeksi/ci.png ]]
[[ -s "$work/restored/public/$public" && -s "$work/restored/private/$private" && ! -e "$work/restored/public/$private" ]]
[[ $(dst -NB -e "SELECT saldo FROM pesanan WHERE nomor='CI-RESTORE' AND total=jumlah_dibayar+saldo") == 125000 ]]
# CI runs phpunit on a fresh schema after this drill. The two database containers are disposable.
printf 'Pemulihan sintetis DB, saldo, media publik dan bukti privat: berhasil, %ss (termasuk startup target).\n' "$((SECONDS-start))" | tee -a "$GITHUB_STEP_SUMMARY"
