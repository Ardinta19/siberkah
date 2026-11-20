"""Exercise native form controls, CSRF, redirects and payment instructions in Chrome."""
import json
import subprocess
import os
from pathlib import Path
from zoneinfo import ZoneInfo
from datetime import datetime, timedelta
from playwright.sync_api import sync_playwright, expect

base = 'http://127.0.0.1:8127'
fixture = json.loads(subprocess.check_output(['php', 'scripts/ci/checkout-session.php']))
with sync_playwright() as playwright:
    browser = playwright.chromium.launch(channel='chrome', args=['--no-sandbox'])
    context = browser.new_context(locale='id-ID')
    context.add_cookies([{**fixture['cookie'], 'url': base}])
    page = context.new_page()
    delivery_day = datetime.now(ZoneInfo('Asia/Jakarta')).date()
    booking_day = delivery_day - timedelta(days=7)
    clock = Path(os.environ['CHECKOUT_CI_CLOCK'])
    clock.write_text(f'{booking_day} 10:00:00')
    def checkout():
        page.goto(base+'/katalog/buket-browser')
        page.get_by_role('link', name='Pesan produk ini').click()
        expect(page.get_by_role('heading', name='Pesan Buket Browser')).to_be_visible()
        page.locator('[name=tanggal]').fill(str(delivery_day))
        page.locator('[name=metode_pelunasan]').select_option('tunai_saat_serah')
        page.locator('[name=setuju_privasi]').check()
    checkout()
    expect(page.locator('[name=alamat_antar]')).to_be_disabled()
    page.get_by_role('button', name='Buat pesanan').click()
    expect(page.get_by_text('Bayar tunai saat ambil di toko: Rp130.000.')).to_be_visible()
    checkout()
    page.locator('[name=jumlah]').fill('2')
    page.locator('[name=cara_serah]').select_option('antar')
    page.locator('[name=alamat_antar]').fill('Alamat sintetis CI')
    page.locator('[name=pembayaran]').select_option('dp')
    expect(page.locator('#estimate')).to_contain_text('DP Rp135.000')
    page.get_by_role('button', name='Buat pesanan').click()
    expect(page.get_by_text('Bayar DP terlebih dahulu.', exact=False)).to_be_visible()
    page.get_by_role('link', name='Lihat tagihan dan petunjuk pembayaran').click()
    page.locator('[name=tujuan_id]').check()
    page.get_by_role('button', name='Saya sudah membayar ke tujuan yang dipilih').click()
    expect(page.get_by_text('Konfirmasi diterima, menunggu pemeriksaan Admin.', exact=False)).to_be_visible()
    number = page.url.split('/')[-2]
    def login(role):
        operator = browser.new_context(locale='id-ID').new_page()
        operator.goto(base+'/staff/login')
        operator.locator('[name=email]').fill(role+'@example.test')
        operator.locator('[name=password]').fill(fixture['password'])
        operator.get_by_role('button', name='Masuk', exact=True).click()
        return operator
    admin = login('admin')
    admin.get_by_role('link', name='Periksa pembayaran dan setoran antar').click()
    verification = admin.locator('form[data-ledger]').filter(has_text=number)
    text = verification.inner_text()
    import re
    exact = re.search(r'Tagihan persis Rp([0-9.]+)', text).group(1).replace('.', '')
    assert 135000 < int(exact) <= 135999
    verification.locator('[name=referensi]').fill('CI:BANK:001')
    verification.locator('[name=nominal]').fill(exact)
    verification.get_by_role('button').click()
    expect(admin.get_by_text('Tidak ada konfirmasi baru.')).to_be_visible()
    admin.goto(base+'/staf/produksi')
    ready = admin.locator('p').filter(has_text=number).locator('xpath=following-sibling::form[1]')
    ready.get_by_role('button', name='Tandai siap').click()
    expect(admin.locator('p').filter(has_text=number)).to_have_count(0)
    clock.write_text(f'{delivery_day} 10:00:00')
    courier = login('staf_kurir')
    handover = courier.locator('form[data-ledger]').filter(has_text=number)
    for field in ('barang_diperiksa','diterima_pembeli','tunai_diterima'):
        handover.locator(f'[name={field}]').check()
    handover.locator('[name=nominal_tunai]').fill('135000')
    handover.get_by_role('button').click()
    expect(courier.get_by_text('sudah diserahkan', exact=False)).to_be_visible()
    cashier = login('owner')
    cashier.get_by_role('link', name='Periksa pembayaran dan setoran antar').click()
    row = cashier.locator('article').filter(has_text=number)
    expect(row).to_contain_text('belum_disetor')
    report_url = f'{base}/admin/laporan/penerimaan?mulai={booking_day}&akhir={delivery_day}'
    before = cashier.request.get(report_url).json()
    row.locator('[name=sesudah]').fill('135000')
    row.locator('[name=diperiksa_fisik]').check()
    row.get_by_role('button').click()
    expect(row).to_contain_text('cocok')
    after = cashier.request.get(report_url).json()
    assert before == after and after['neto'] == 270000
    assert {r['kanal']: r['pokok'] for r in after['rincian']} == {'bank':135000, 'tunai':135000}
    browser.close()
