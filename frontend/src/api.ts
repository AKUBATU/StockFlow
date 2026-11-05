import type { ErrorDetail, Movement, Order, OrderInput, Product, ProductFilter, ProductInput, ProductPage, StockInput, Summary } from './types';

export class ApiError extends Error {
  constructor(public status: number, public detail: ErrorDetail) {
    super(detail.available !== undefined
      ? `${detail.sku ? `${detail.sku}: ` : ''}${detail.message}. Stok tersedia: ${detail.available}.`
      : detail.message);
  }
}
function errorDetail(value: unknown): ErrorDetail | null {
  if (typeof value !== 'object' || value === null || !('error' in value)) return null;
  const e = value.error;
  if (typeof e !== 'object' || e === null || !('code' in e) || typeof e.code !== 'string') return null;
  return {
    code: e.code,
    message: 'message' in e && typeof e.message === 'string' ? e.message : e.code,
    sku: 'sku' in e && typeof e.sku === 'string' ? e.sku : undefined,
    available: 'available' in e && typeof e.available === 'number' ? e.available : undefined,
  };
}
async function request<T>(path: string, init?: RequestInit): Promise<T> {
  let res: Response;
  try { res = await fetch(`/api${path}`, { ...init, headers: { 'Content-Type': 'application/json', ...init?.headers } }); }
  catch (e) { if (e instanceof DOMException && e.name === 'AbortError') throw e; throw new Error('Tidak dapat terhubung ke API. Pastikan backend sedang berjalan.'); }
  if (res.status === 204) return undefined as T;
  const data: unknown = await res.json().catch(() => null);
  if (!res.ok) throw new ApiError(res.status, errorDetail(data) ?? { code: 'HTTP_ERROR', message: `Permintaan gagal (${res.status})` });
  return data as T;
}
const body = (method: string, value?: unknown): RequestInit => ({ method, ...(value === undefined ? {} : { body: JSON.stringify(value) }) });
export const api = {
  products: (search: string, page: number, filter: ProductFilter, signal?: AbortSignal) => request<ProductPage>(`/products?${new URLSearchParams({ search, page: String(page), limit: '10', filter })}`, { signal }),
  summary: (signal?: AbortSignal) => request<Summary>('/summary', { signal }),
  createProduct: (value: ProductInput) => request<Product>('/products', body('POST', value)),
  updateProduct: (id: number, value: ProductInput) => request<Product>(`/products/${id}`, body('PUT', value)),
  deleteProduct: (id: number) => request<void>(`/products/${id}`, body('DELETE')),
  stock: (id: number, value: StockInput) => request<Product>(`/products/${id}/stock`, body('POST', value)),
  movements: (id: number) => request<{ data: Movement[] }>(`/products/${id}/movements`),
  orders: (page: number, signal?: AbortSignal) => request<{ data: Order[] }>(`/orders?page=${page}&limit=10`, { signal }),
  createOrder: (value: OrderInput) => request<Order>('/orders', body('POST', value)),
  order: (id: number) => request<Order>(`/orders/${id}`),
  cancelOrder: (id: number) => request<Order>(`/orders/${id}/cancel`, body('POST')),
};
export function message(error: unknown): string { return error instanceof Error ? error.message : 'Terjadi kesalahan. Silakan coba lagi.'; }
