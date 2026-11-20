"""Exercise native form controls, CSRF, redirects and payment instructions in Chrome."""
import json
import subprocess
import os
import sys
import traceback
from pathlib import Path
from zoneinfo import ZoneInfo
from datetime import datetime, timedelta
from playwright.sync_api import sync_playwright, expect

def annotate(kind, error, trace):
    lines = traceback.extract_tb(trace)
    frame = next((f for f in reversed(lines) if f.filename.endswith('checkout-browser.py')), lines[-1])
    # Synthetic CI only; avoid printing credential input or cookies.
    message = str(error).split('Call log:')[0].splitlines()
    print(f'::error::Chrome: line {frame.lineno}: {kind.__name__}: {" ".join(message[:3])[:300]}', flush=True)
    sys.__excepthook__(kind, error, trace)
sys.excepthook = annotate

base = 'http://127.0.0.1:8127'
fixture = json.loads(subprocess.check_output(['php', 'scripts/ci/checkout-session.php']))
# Read-only operator checklist; the CLI intentionally rejects any other database.
subprocess.run(['php', 'artisan', 'uji:operator'], check=True)
wrong_db = subprocess.run(['php', 'artisan', 'uji:operator'], env={**os.environ, 'DB_DATABASE':'siberkah_test'}, capture_output=True, text=True)
assert wrong_db.returncode == 1 and 'basis data efektif' in wrong_db.stdout
with sync_playwright() as playwright:
    browser = playwright.chromium.launch(channel='chrome', args=['--no-sandbox'])
    context = browser.new_context(locale='id-ID')
    context.add_cookies([{**fixture['cookie'], 'url': base}])
    page = context.new_page()
    booking_day = datetime.now(ZoneInfo('Asia/Jakarta')).date()
    delivery_day = booking_day + timedelta(days=7)
    clock = Path(os.environ['CHECKOUT_CI_CLOCK'])
    clock.write_text(f'{booking_day} 10:00:00')
    page.goto(base+'/katalog')
    for width in (320,375,430,768,1024,1440):
        page.set_viewport_size({'width':width,'height':900})
        panel = page.locator('.koleksi')
        assert panel.evaluate('(el) => el.scrollWidth <= innerWidth'), f'Catalog overflow at {width}px'
        assert page.locator('.kc-dock').is_visible() == (width < 768)
        assert panel.locator('.kc-rail-title').is_visible() == (768 <= width < 1200)
        columns = panel.locator('.kc-grid').evaluate("el => getComputedStyle(el).gridTemplateColumns.split(' ').length")
        assert columns == (1 if width < 768 else 2 if width < 1200 else 3)
        if width < 768:
            page.get_by_role('button',name='Filter & urutan').click()
        for field in ('q','jenis','tanggal','urut'):
            box = panel.locator('.bk-date-trigger' if field == 'tanggal' else f'[name={field}]').bounding_box()
            assert box and box['x'] >= 0 and box['x']+box['width'] <= width+1
        if width < 768:
            page.get_by_role('button',name='Filter & urutan').click()
    kind = page.get_by_label('Jenis produk')
    kind.focus()
    kind.press('ArrowDown')
    kind.press('Enter')
    kind.press('Escape')
    kind.select_option('jual')
    expect(panel.locator('input[name=tanggal]')).to_be_disabled()
    if page.evaluate("CSS.supports('appearance:base-select') && CSS.supports('selector(::picker(select))')"):
        assert kind.evaluate("el => getComputedStyle(el,'::picker(select)').appearance") == 'base-select'
        assert kind.locator('option:checked').evaluate("el => getComputedStyle(el).backgroundColor") == 'rgb(245, 228, 234)'
    kind.select_option('sewa')
    expect(panel.locator('input[name=tanggal]')).to_be_enabled()
    panel.locator('.bk-date-trigger').click()
    calendar = page.get_by_role('dialog',name='Pilih tanggal sewa')
    calendar.get_by_role('button',name='Bulan berikutnya').click()
    date_button = calendar.locator('[data-date]').first
    picked = date_button.get_attribute('data-date')
    date_button.click()
    expect(panel.locator('[name=tanggal]')).to_have_value(picked)
    panel.locator('.bk-date-trigger').click()
    calendar.get_by_role('button',name='Kosongkan').click()
    expect(panel.locator('[name=tanggal]')).to_have_value('')
    kind.select_option('')
    expect(page.locator('[data-replay]')).to_have_count(0)
    page.emulate_media(reduced_motion='reduce')
    assert page.locator('.kc-hero-reveal').evaluate("el => getComputedStyle(el,'::after').animationName") == 'bk-soft-light'
    page.emulate_media(reduced_motion='no-preference')
    photo = page.locator('.kc-photo-link').first
    photo.hover()
    expect(page.locator('.kc-card').first).not_to_have_css('transform','none')
    fav = page.locator('.kc-card [data-favorite]').first
    fav.scroll_into_view_if_needed()
    box = fav.bounding_box()
    page.mouse.move(box['x'] + box['width']/2, box['y'] + box['height']/2)
    page.mouse.down()
    expect(fav).to_have_attribute('aria-pressed','false')
    page.mouse.up()
    expect(fav).to_have_attribute('aria-pressed','true')
    assert fav.evaluate("el => getComputedStyle(el,'::after').content.includes('✿')")
    assert fav.evaluate("el => el.getAnimations().some(a => a.effect.getKeyframes().some(k => k.boxShadow))")
    page.reload()
    expect(fav).to_have_attribute('aria-pressed','true')
    page.set_viewport_size({'width':375,'height':800})
    page.emulate_media(color_scheme='dark')
    assert page.locator('.kc-dock a').all_text_contents() == ['Beranda','Katalog','Lacak','Akun']
    for selector in ('#main-header','footer'):
        rgb = page.locator(selector).evaluate("el => getComputedStyle(el).backgroundColor")
        assert rgb in ('rgb(255, 253, 249)','rgb(250, 245, 240)'), rgb
    page.goto(base+'/katalog/buket-browser')
    page.emulate_media(reduced_motion='reduce')
    page.reload()
    expect(page.locator('.bk-flower-glow')).to_have_count(0)
    page.get_by_role('button',name='Simpan ke keranjang').click()
    page.emulate_media(reduced_motion='no-preference')
    expect(page.get_by_role('heading',name='Keranjang Anda')).to_be_visible()
    expect(page.get_by_role('button',name='Kurangi jumlah')).to_be_disabled()
    page.get_by_role('button',name='Tambah jumlah').click()
    expect(page.locator('[name=jumlah]')).to_have_value('2')
    page.get_by_role('button',name='Perbarui').click()
    page.get_by_role('link',name='Lanjut pesan').click()
    expect(page.locator('[name=jumlah]')).to_have_value('2')
    page.goto(base+'/keranjang')
    page.locator('.bk-remove summary').click()
    page.get_by_role('button',name='Ya, hapus').click()
    expect(page.get_by_role('status').filter(has_text='Produk dihapus')).to_be_visible()
    expect(page.get_by_text('Keranjang masih kosong.')).to_be_visible()
    page.set_viewport_size({'width':1440,'height':900})
    page.emulate_media(color_scheme='light')
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
    expect(courier.locator('form[data-ledger]').filter(has_text=number)).to_have_count(0)  # completed bouquet leaves the active work list
    cashier = login('owner')
    cashier.get_by_role('link', name='Periksa pembayaran dan setoran antar').click()
    row = cashier.locator('article').filter(has_text=number)
    expect(row).to_contain_text('belum_disetor')
    report_url = f'{base}/admin/laporan/penerimaan?mulai={booking_day}&akhir={delivery_day}'
    before = cashier.request.get(report_url).json()
    audit = ['php','artisan','uji:operator','--pesanan='+number]
    pending = subprocess.run(audit, capture_output=True, text=True)
    assert pending.returncode == 1 and 'GAGAL Riwayat setoran' in pending.stdout
    row.locator('[name=sesudah]').fill('135000')
    row.locator('[name=diperiksa_fisik]').check()
    row.get_by_role('button').click()
    expect(row).to_contain_text('cocok')
    after = cashier.request.get(report_url).json()
    assert before == after and after['neto'] == 270000
    assert {r['kanal']: r['pokok'] for r in after['rincian']} == {'bank':135000, 'tunai':135000}
    subprocess.run(audit, check=True)
    browser.close()
