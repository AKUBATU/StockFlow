import { afterEach, describe, expect, it, vi } from 'vitest';
import { api, ApiError } from './api';

afterEach(() => vi.unstubAllGlobals());
describe('API error handling', () => {
  it('keeps SKU and available quantity from a real API error envelope', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify({ error: { code: 'INSUFFICIENT_STOCK', sku: 'ATK001', available: 0, message: 'Stok tidak cukup' } }), { status: 409 })));
    try { await api.createOrder({ customer: 'Acme', items: [{ sku: 'ATK001', qty: 1 }] }); throw new Error('Expected rejection'); }
    catch (e) {
      expect(e).toBeInstanceOf(ApiError);
      expect((e as ApiError).detail.available).toBe(0);
      expect((e as Error).message).toContain('ATK001');
      expect((e as Error).message).toContain('Stok tersedia: 0');
    }
  });
  it('shows a useful message when the backend is unreachable', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')));
    await expect(api.summary()).rejects.toThrow('Tidak dapat terhubung');
  });
  it('accepts a successful 204 delete without attempting JSON parsing', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 204 })));
    await expect(api.deleteProduct(1)).resolves.toBeUndefined();
  });
});
