"""Loopback CI-only HTTP measurements. No external targets, no credentials or live data."""
import concurrent.futures
import os
import signal
import statistics
import subprocess
import threading
import time
import urllib.request

assert os.environ.get('GITHUB_ACTIONS') == 'true' and os.environ['DB_DATABASE'] == 'siberkah_catalog_ci'
url = 'http://127.0.0.1:18081/katalog?tanggal=2030-01-25'
server = subprocess.Popen(['php', '-d', 'opcache.enable_cli=1', '-S', '127.0.0.1:18081', '-t', 'public', 'public/index.php'],
                          env={**os.environ, 'PHP_CLI_SERVER_WORKERS': '4'}, start_new_session=True,
                          stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

def request():
    start = time.perf_counter()
    with urllib.request.urlopen(url, timeout=15) as response:
        html = response.read().decode()
        if response.status != 200 or 'Papan CI 002' not in html or 'Papan CI 001' in html:
            raise ValueError('hasil filter katalog keliru')
    return (time.perf_counter() - start) * 1000

try:
    for _ in range(60):
        if server.poll() is not None:
            raise RuntimeError('server benchmark berhenti')
        try:
            request()
            break
        except OSError:
            time.sleep(.25)
    else:
        raise RuntimeError('server benchmark tidak siap')
    times = [request() for _ in range(10)]
    gate = threading.Barrier(50, timeout=20)
    def user(_):
        gate.wait()
        return request()
    with concurrent.futures.ThreadPoolExecutor(max_workers=50) as pool:
        futures = [pool.submit(user, i) for i in range(50)]
        concurrent.futures.wait(futures)
    errors = [f.exception() for f in futures if f.exception()]
    results = (f'Katalog sintetis, 200 produk/5.000 reservasi: 10 GET hangat, median {statistics.median(times):.1f} ms, '
               f'maks {max(times):.1f} ms. Gelombang 50 klien: {len(errors)} galat dari 50 GET.')
    print(f"::notice::{results}", flush=True)
    with open(os.environ['GITHUB_STEP_SUMMARY'], 'a') as report:
        print(results, file=report)
    if errors or max(times) > 500:
        raise RuntimeError(f'Batas performa tidak terpenuhi: {errors[:1]}')
finally:
    os.killpg(server.pid, signal.SIGTERM)
    server.wait(timeout=10)
