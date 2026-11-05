import type { OrderInput, ProductInput, StockInput } from './types';
export const MAX_INTEGER = 2147483647;
export const rupiah = (value: number) => `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(value)}`;
export function positiveInteger(value: number): boolean { return Number.isInteger(value) && value > 0 && value <= MAX_INTEGER; }
export function validateProduct(p: ProductInput): string | null {
  if (!p.sku.trim() || !p.name.trim()) return 'SKU dan nama produk wajib diisi.';
  if (p.sku.trim().length > 100 || p.name.trim().length > 200) return 'SKU maksimal 100 dan nama 200 karakter.';
  if (!Number.isInteger(p.price) || p.price < 0 || p.price > MAX_INTEGER) return 'Harga harus integer 0–2147483647.';
  return null;
}
export function validateStock(p: StockInput): string | null {
  if (p.type !== 'in' && p.type !== 'out') return 'Pilih arah stok.';
  if (!positiveInteger(p.qty)) return 'Jumlah harus integer positif maksimal 2147483647.';
  if (p.note.length > 1000) return 'Catatan maksimal 1000 karakter.';
  return null;
}
export function validateOrder(p: OrderInput): string | null {
  if (!p.customer.trim() || p.customer.length > 200) return 'Nama pelanggan wajib diisi, maksimal 200 karakter.';
  if (!p.items.length || p.items.length > 100) return 'Pesanan harus memiliki 1–100 item.';
  const seen = new Set<string>();
  for (const item of p.items) {
    if (!item.sku.trim()) return 'SKU wajib diisi untuk setiap item.';
    if (!positiveInteger(item.qty)) return `${item.sku}: jumlah harus integer positif maksimal 2147483647.`;
    if (seen.has(item.sku)) return `SKU ${item.sku} muncul lebih dari satu kali.`;
    seen.add(item.sku);
  }
  return null;
}
export function relativeTime(value: string | null): string {
  if (!value) return 'Belum ada pergerakan';
  const seconds = Math.max(0, Math.floor((Date.now() - Date.parse(value)) / 1000));
  if (seconds < 60) return 'Baru saja';
  if (seconds < 3600) return `${Math.floor(seconds / 60)} menit lalu`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)} jam lalu`;
  return `${Math.floor(seconds / 86400)} hari lalu`;
}
