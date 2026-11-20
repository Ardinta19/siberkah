"""Exercise native form controls, CSRF, redirects and payment instructions in Chrome."""
import json
import subprocess
from datetime import date, timedelta
from playwright.sync_api import sync_playwright, expect

base = 'http://127.0.0.1:8127'
cookie = json.loads(subprocess.check_output(['php', 'scripts/ci/checkout-session.php']))
with sync_playwright() as playwright:
    browser = playwright.chromium.launch(channel='chrome', args=['--no-sandbox'])
    context = browser.new_context(locale='id-ID')
    context.add_cookies([{**cookie, 'url': base}])
    page = context.new_page()
    def checkout():
        page.goto(base+'/katalog/buket-browser')
        page.get_by_role('link', name='Pesan produk ini').click()
        expect(page.get_by_role('heading', name='Pesan Buket Browser')).to_be_visible()
        page.locator('[name=tanggal]').fill(str(date.today()+timedelta(days=7)))
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
    browser.close()
