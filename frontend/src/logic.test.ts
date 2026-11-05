import { describe, expect, it, vi } from 'vitest';
import { MAX_INTEGER, positiveInteger, relativeTime, rupiah, validateOrder, validateProduct, validateStock } from './logic';

describe('integer quantities', () => {
  it.each([0, -1, 1.5, NaN, Infinity, MAX_INTEGER + 1])('rejects %s', value => expect(positiveInteger(value)).toBe(false));
  it.each([1, 10, MAX_INTEGER])('accepts %s', value => expect(positiveInteger(value)).toBe(true));
});
describe('stock validation', () => {
  it('accepts overdraw input so authoritative server conflicts can be shown', () => {
    expect(validateStock({ type: 'out', qty: 100, note: '' })).toBeNull();
  });
  it('rejects decimals and overly long notes', () => {
    expect(validateStock({ type: 'in', qty: 0.5, note: '' })).toBeTruthy();
    expect(validateStock({ type: 'in', qty: 1, note: 'x'.repeat(1001) })).toBeTruthy();
  });
});
describe('order validation', () => {
  it('requires a customer and at least one item', () => {
    expect(validateOrder({ customer: ' ', items: [{ sku: 'A', qty: 1 }] })).toBeTruthy();
    expect(validateOrder({ customer: 'Acme', items: [] })).toBeTruthy();
  });
  it('rejects duplicate SKUs and invalid quantities', () => {
    expect(validateOrder({ customer: 'Acme', items: [{ sku: 'A', qty: 1 }, { sku: 'A', qty: 2 }] })).toContain('A');
    expect(validateOrder({ customer: 'Acme', items: [{ sku: 'A', qty: 1.2 }] })).toContain('A');
  });
  it('accepts a multi-item order without client prices', () => {
    expect(validateOrder({ customer: 'Acme', items: [{ sku: 'A', qty: 1 }, { sku: 'B', qty: 2 }] })).toBeNull();
  });
});
describe('product validation and display', () => {
  it('allows zero prices but rejects negative and fractional money', () => {
    const product = { sku: 'A', name: 'Pulpen', price: 0, is_active: true };
    expect(validateProduct(product)).toBeNull();
    expect(validateProduct({ ...product, price: -1 })).toBeTruthy();
    expect(validateProduct({ ...product, price: 1.5 })).toBeTruthy();
  });
  it('formats integer IDR amounts', () => expect(rupiah(1234567)).toBe('Rp 1.234.567'));
  it('formats null, recent and older movements', () => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2026-11-05T10:00:00Z'));
    expect(relativeTime(null)).toBe('Belum ada pergerakan');
    expect(relativeTime('2026-11-05T10:00:00Z')).toBe('Baru saja');
    expect(relativeTime('2026-11-05T08:00:00Z')).toBe('2 jam lalu');
    vi.useRealTimers();
  });
});
