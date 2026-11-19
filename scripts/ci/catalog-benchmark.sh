#!/usr/bin/env bash
# Fixed, disposable schema. Never benchmark against a laptop or production database.
set -euo pipefail
[[ ${GITHUB_ACTIONS:-} == true && ${APP_ENV:-} == testing && ${DB_DATABASE:-} == siberkah_test ]]
: "${CI_DB_CONTAINER:?CI database container required}"
sql() { docker exec -i -e MYSQL_PWD="$DB_PASSWORD" "$CI_DB_CONTAINER" mariadb -uroot "$@"; }
sql -e 'CREATE DATABASE siberkah_catalog_ci'
export DB_DATABASE=siberkah_catalog_ci APP_DEBUG=false
trap "sql -e 'DROP DATABASE siberkah_catalog_ci'" EXIT
php artisan migrate --force
# The seq_ tables below are MariaDB's built-in sequence tables. Each reservation
# has a matching order and item. On day 24, odd products are full, even ones free.
sql "$DB_DATABASE" <<'SQL'
START TRANSACTION;
INSERT INTO kategori(id,nama,slug) VALUES (1,'Papan','papan');
INSERT INTO produk(id,kategori_id,nama,slug,jenis,harga_dasar) SELECT seq,1,CONCAT('Papan CI ',LPAD(seq,3,'0')),CONCAT('papan-',seq),'sewa',75000 FROM seq_1_to_200;
INSERT INTO varian_produk(id,produk_id,nama,harga) SELECT id,id,'Bening',75000 FROM produk;
INSERT INTO unit_sewa(id,varian_produk_id,kode) SELECT id,id,CONCAT('CI-',id) FROM produk;
INSERT INTO pelanggan(id,nomor_wa,nama) VALUES (1,'CI-ONLY','Data sintetis');
INSERT INTO pesanan(id,nomor,pelanggan_id,nama_pemesan,nomor_wa_pemesan,cara_serah,tanggal_serah,status)
SELECT seq,CONCAT('CI-',seq),1,'Sintetis','CI-ONLY','antar', ADDDATE('2030-01-01',(seq-1) DIV 200 + IF(MOD(seq,2)=0 AND seq>4800,1,0)),'dikonfirmasi' FROM seq_1_to_5000;
INSERT INTO item_pesanan(id,pesanan_id,produk_id,varian_produk_id,unit_sewa_id,nama_produk,harga_satuan,subtotal)
SELECT id,id,MOD(id-1,200)+1,MOD(id-1,200)+1,MOD(id-1,200)+1,'Papan',75000,75000 FROM pesanan;
INSERT INTO hari_reservasi(unit_sewa_id,pesanan_id,item_pesanan_id,tanggal) SELECT i.unit_sewa_id,p.id,i.id,p.tanggal_serah FROM item_pesanan i JOIN pesanan p ON p.id=i.pesanan_id;
COMMIT;
SQL
[[ $(sql -NB "$DB_DATABASE" -e 'SELECT COUNT(*) FROM produk') == 200 ]]
[[ $(sql -NB "$DB_DATABASE" -e 'SELECT COUNT(*) FROM hari_reservasi') == 5000 ]]
python scripts/ci/catalog-benchmark.py
