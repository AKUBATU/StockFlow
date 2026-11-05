export interface Product {
  id: number;
  sku: string;
  name: string;
  price: number;
  stock: number;
  is_active: boolean;
  created_at: string;
  last_movement_at: string | null;
}
export interface ProductInput { sku: string; name: string; price: number; is_active: boolean }
export interface ProductPage { data: Product[]; page: number; limit: number; total: number }
export type ProductFilter = 'all' | 'active' | 'inactive' | 'low';
export interface Summary { total_products: number; active_products: number; total_stock_value: number; low_stock_count: number }
export interface Movement { id: number; type: 'in' | 'out'; qty: number; note: string; created_at: string }
export interface StockInput { type: 'in' | 'out'; qty: number; note: string }
export interface OrderInput { customer: string; items: { sku: string; qty: number }[] }
export interface OrderItem { sku: string; qty: number; unit_price: number }
export interface Order { id: number; customer: string; status: 'CREATED' | 'CANCELLED'; total: number; created_at: string; cancelled_at: string | null; items: OrderItem[] }
export interface ErrorDetail { code: string; message: string; sku?: string; available?: number }
