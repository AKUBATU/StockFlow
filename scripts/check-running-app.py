"""Read-only smoke checks against the running frontend and its real API proxy."""

import json
import sys
import urllib.request


def check(base_url):
    def get(path):
        with urllib.request.urlopen(base_url.rstrip('/') + path, timeout=10) as response:
            if response.status != 200:
                raise RuntimeError(f'{path}: expected HTTP 200, got {response.status}')
            return response.read()

    def api(path):
        return json.loads(get('/api' + path))

    if b'id="root"' not in get('/'):
        raise RuntimeError('Frontend HTML does not contain the React root')
    if api('/health').get('status') != 'ok':
        raise RuntimeError('Backend health check failed')
    products = api('/products?page=1&limit=100')
    if products['total'] < 12 or len(products['data']) < 12:
        raise RuntimeError('Expected at least 12 real seeded products')
    if any('last_movement_at' not in product for product in products['data']):
        raise RuntimeError('Product response is missing last_movement_at')
    if len(api('/orders?page=1&limit=100')['data']) < 2:
        raise RuntimeError('Expected at least two seeded orders')
    summary = api('/summary')
    if summary['total_products'] != products['total']:
        raise RuntimeError('Summary and product totals do not match')
    if summary['total_stock_value'] != sum(p['price'] * p['stock'] for p in products['data']):
        raise RuntimeError('Summary stock value does not match seeded products')
    print('PASS: frontend, API health, seeded products, orders, last movement and summary')


if __name__ == '__main__':
    check(sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:3000')
