import { useEffect, useState } from 'react';
import { api, message } from './api';
import { ErrorBox } from './Modal';
import { ProductForm, StockForm, MovementHistory, DeleteProduct } from './ProductDialogs';
import { CreateOrder, OrderDetail } from './OrderDialogs';
import { relativeTime, rupiah } from './logic';
import { useDebounce } from './useDebounce';
import type { Order, Product, ProductFilter, ProductPage, Summary } from './types';

type Dialog = { kind: 'new-product' } | { kind: 'edit' | 'stock' | 'history' | 'delete'; product: Product } | { kind: 'new-order' } | { kind: 'order'; id: number };
const filters: { value: ProductFilter; label: string }[] = [{ value: 'all', label: 'Semua produk' }, { value: 'active', label: 'Aktif' }, { value: 'inactive', label: 'Nonaktif' }, { value: 'low', label: 'Stok rendah ≤ 10' }];

export default function App() {
  const [tab, setTab] = useState<'products' | 'orders'>('products');
  const [search, setSearch] = useState(''); const debounced = useDebounce(search);
  const [filter, setFilter] = useState<ProductFilter>('all'); const [page, setPage] = useState(1);
  const [products, setProducts] = useState<ProductPage | null>(null); const [loading, setLoading] = useState(true); const [error, setError] = useState('');
  const [summary, setSummary] = useState<Summary | null>(null); const [summaryError, setSummaryError] = useState('');
  const [orders, setOrders] = useState<Order[]>([]); const [orderPage, setOrderPage] = useState(1); const [orderLoading, setOrderLoading] = useState(true); const [orderError, setOrderError] = useState('');
  const [revision, setRevision] = useState(0); const [dialog, setDialog] = useState<Dialog | null>(null); const [notice, setNotice] = useState('');
  const refresh = () => setRevision(n => n + 1);
  function saved(text: string) { setDialog(null); setNotice(text); refresh(); }
  useEffect(() => { if (!notice) return; const timer = setTimeout(() => setNotice(''), 6000); return () => clearTimeout(timer); }, [notice]);
  useEffect(() => {
    const controller = new AbortController(); setLoading(true); setError('');
    api.products(debounced, page, filter, controller.signal).then(result => {
      if (controller.signal.aborted) return;
      if (result.data.length === 0 && page > 1) { setPage(Math.max(1, Math.ceil(result.total / result.limit))); return; }
      setProducts(result);
    }).catch(e => { if (!controller.signal.aborted) setError(message(e)); }).finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [debounced, page, filter, revision]);
  useEffect(() => {
    const controller = new AbortController(); setSummary(null); setSummaryError('');
    api.summary(controller.signal).then(s => { if (!controller.signal.aborted) setSummary(s); }).catch(e => { if (!controller.signal.aborted) setSummaryError(message(e)); });
    return () => controller.abort();
  }, [revision]);
  useEffect(() => {
    const controller = new AbortController(); setOrderLoading(true); setOrderError('');
    api.orders(orderPage, controller.signal).then(r => { if (!controller.signal.aborted) setOrders(r.data); }).catch(e => { if (!controller.signal.aborted) setOrderError(message(e)); }).finally(() => { if (!controller.signal.aborted) setOrderLoading(false); });
    return () => controller.abort();
  }, [orderPage, revision]);
  return <div className="app-shell">
    <aside className="sidebar"><a className="brand" href="/" aria-label="StockFlow beranda"><span className="brand-mark">S</span>StockFlow<span className="brand-dot">.</span></a><div className="workspace"><span className="workspace-icon">▦</span><div><strong>Gudang ATK</strong><small>Inventory & Sales</small></div><span className="workspace-arrow">⌄</span></div><span className="nav-label">WORKSPACE</span><nav aria-label="Navigasi utama"><button className={tab === 'products' ? 'nav-item selected' : 'nav-item'} onClick={() => setTab('products')}><span>▦</span>Produk & stok</button><button className={tab === 'orders' ? 'nav-item selected' : 'nav-item'} onClick={() => setTab('orders')}><span>▤</span>Pesanan penjualan</button></nav><div className="sidebar-bottom"><span className="avatar">GS</span><div><strong>Gudang Supervisor</strong><small>StockFlow workspace</small></div></div></aside>
    <div className="main-shell"><header className="topbar"><div className="breadcrumb">Workspace <span>/</span> <strong>{tab === 'products' ? 'Produk & stok' : 'Pesanan penjualan'}</strong></div><span className="topbar-label">INVENTORY & SALES</span></header>
      <main><div className="page-heading"><div><div className="eyebrow">KELOLA GUDANG, LEBIH TERATUR</div><h1>{tab === 'products' ? 'Produk & persediaan' : 'Pesanan penjualan'}</h1><p className="muted">{tab === 'products' ? 'Pantau produk, sesuaikan stok, dan jaga operasional tetap lancar.' : 'Catat penjualan dan kelola pesanan dalam satu tempat.'}</p></div><div className="actions"><button className="button" onClick={() => setDialog({ kind: 'new-order' })}>＋ Buat pesanan</button><button className="button primary" onClick={() => setDialog({ kind: 'new-product' })}>＋ Tambah produk</button></div></div>
      {notice && <div className="success-box notice" role="status">✓ {notice}<button className="icon-button" aria-label="Tutup notifikasi" onClick={() => setNotice('')}>×</button></div>}
      {summaryError ? <ErrorBox text={`Ringkasan: ${summaryError}`} retry={refresh} /> : <section className="summary-grid" aria-label="Ringkasan inventori" aria-busy={!summary}>
        <SummaryCard label="Total produk" value={summary ? String(summary.total_products) : '…'} note="Seluruh katalog produk" icon="▦" />
        <SummaryCard label="Produk aktif" value={summary ? String(summary.active_products) : '…'} note="Siap untuk dijual" icon="✓" />
        <SummaryCard label="Nilai persediaan" value={summary ? rupiah(summary.total_stock_value) : '…'} note="Harga × stok seluruh produk" icon="↗" />
        <SummaryCard label="Stok rendah" value={summary ? String(summary.low_stock_count) : '…'} note="Produk dengan stok ≤ 10" icon="!" warning />
      </section>}
      {tab === 'products' ? <section className="panel"><div className="panel-heading"><div><h2>Katalog produk <span className="count">{products?.total ?? '—'}</span></h2><p className="muted">Semua kebutuhan kantor, dalam satu inventori.</p></div><button className="button small" onClick={refresh}>↻ Perbarui</button></div><div className="toolbar"><div className="filters" aria-label="Filter produk">{filters.map(f => <button key={f.value} aria-pressed={filter === f.value} className={filter === f.value ? 'filter active' : 'filter'} onClick={() => { setFilter(f.value); setPage(1); }}>{f.label}</button>)}</div><label className="search"><span aria-hidden="true">⌕</span><input aria-label="Cari produk" placeholder="Cari SKU atau nama produk…" value={search} onChange={e => { setSearch(e.target.value); setPage(1); }} /></label></div>
        {loading ? <div className="table-wrap" role="status" aria-label="Memuat produk"><table><thead><tr>{['SKU', 'Nama produk', 'Harga', 'Stok', 'Status', 'Pergerakan terakhir', 'Aksi'].map(t => <th key={t}>{t}</th>)}</tr></thead><tbody>{Array.from({ length: 6 }, (_, i) => <tr key={i}>{Array.from({ length: 7 }, (_, j) => <td key={j}><div className="skeleton" /></td>)}</tr>)}</tbody></table></div> : error ? <div className="panel-state"><ErrorBox text={error} retry={refresh} /></div> : !products?.data.length ? <div className="empty-state"><span>▦</span><h3>Produk belum ditemukan</h3><p className="muted">Coba ubah pencarian atau filter, atau tambahkan produk baru.</p><button className="button" onClick={() => { setSearch(''); setFilter('all'); setPage(1); }}>Reset pencarian</button></div> : <>
          <div className="table-wrap"><table><thead><tr><th>SKU</th><th>Nama produk</th><th>Harga satuan</th><th>Stok</th><th>Status</th><th>Pergerakan terakhir</th><th>Aksi</th></tr></thead><tbody>{products.data.map(p => <tr key={p.id}><td><span className="sku">{p.sku}</span></td><td className="product-name">{p.name}</td><td className="money">{rupiah(p.price)}</td><td><span className={p.stock <= 10 ? 'stock-number low-stock' : 'stock-number'}>{p.stock}</span><small className="unit"> unit</small></td><td><span className={`badge ${p.is_active ? 'green' : 'gray'}`}><i />{p.is_active ? 'Aktif' : 'Nonaktif'}</span></td><td className="muted" title={p.last_movement_at ? new Date(p.last_movement_at).toLocaleString('id-ID') : ''}>{relativeTime(p.last_movement_at)}</td><td><div className="row-actions"><button className="text-button" onClick={() => setDialog({ kind: 'stock', product: p })}>± Stok</button><select className="action-select" aria-label={`Aksi ${p.sku}`} value="" onChange={e => { const kind = e.target.value; if (kind === 'edit' || kind === 'history' || kind === 'delete') setDialog({ kind, product: p }); }}><option value="" disabled>Aksi</option><option value="edit">Edit produk</option><option value="history">Riwayat stok</option><option value="delete">Hapus produk</option></select></div></td></tr>)}</tbody></table></div>
          <footer className="table-footer"><span>Menampilkan {(products.page - 1) * products.limit + 1}–{Math.min(products.page * products.limit, products.total)} dari {products.total} produk</span><div className="pagination"><button className="button small" disabled={page === 1} onClick={() => setPage(n => n - 1)}>← Sebelumnya</button><span>{page} / {Math.max(1, Math.ceil(products.total / products.limit))}</span><button className="button small" disabled={page * products.limit >= products.total} onClick={() => setPage(n => n + 1)}>Berikutnya →</button></div></footer>
        </>}
      </section> : <section className="panel"><div className="panel-heading"><div><h2>Daftar pesanan</h2><p className="muted">Stok dikurangi saat dibuat dan dikembalikan saat dibatalkan.</p></div><button className="button small" onClick={refresh}>↻ Perbarui</button></div>
        {orderLoading ? <p className="panel-state" role="status">Memuat pesanan…</p> : orderError ? <div className="panel-state"><ErrorBox text={orderError} retry={refresh} /></div> : <><div className="table-wrap"><table><thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Tanggal</th><th>Status</th><th>Total</th><th>Aksi</th></tr></thead><tbody>{orders.map(o => <tr key={o.id}><td className="sku">#{String(o.id).padStart(4, '0')}</td><td className="product-name">{o.customer}</td><td className="muted">{new Date(o.created_at).toLocaleString('id-ID')}</td><td><span className={`badge ${o.status === 'CREATED' ? 'green' : 'gray'}`}>{o.status === 'CREATED' ? 'Dibuat' : 'Dibatalkan'}</span></td><td className="money">{rupiah(o.total)}</td><td><button className="text-button" onClick={() => setDialog({ kind: 'order', id: o.id })}>Lihat detail →</button></td></tr>)}</tbody></table>{orders.length === 0 && <p className="empty">Tidak ada pesanan di halaman ini.</p>}</div><footer className="table-footer"><span>Halaman {orderPage}</span><div className="pagination"><button className="button small" disabled={orderPage === 1} onClick={() => setOrderPage(n => n - 1)}>← Sebelumnya</button><button className="button small" disabled={orders.length < 10} onClick={() => setOrderPage(n => n + 1)}>Berikutnya →</button></div></footer></>}
      </section>}
      <div className="page-footer"><span>StockFlow · Inventory & Sales</span><span>Data langsung dari API · PostgreSQL</span></div>
      </main>
    </div>
    {dialog?.kind === 'new-product' && <ProductForm onClose={() => setDialog(null)} onSaved={() => saved('Produk berhasil ditambahkan.')} />}
    {dialog?.kind === 'edit' && <ProductForm product={dialog.product} onClose={() => setDialog(null)} onSaved={() => saved('Produk berhasil diperbarui.')} />}
    {dialog?.kind === 'stock' && <StockForm product={dialog.product} onClose={() => setDialog(null)} onSaved={() => saved('Stok dan riwayat berhasil diperbarui.')} />}
    {dialog?.kind === 'history' && <MovementHistory product={dialog.product} onClose={() => setDialog(null)} />}
    {dialog?.kind === 'delete' && <DeleteProduct product={dialog.product} onClose={() => setDialog(null)} onSaved={() => saved('Produk berhasil dihapus.')} />}
    {dialog?.kind === 'new-order' && <CreateOrder onClose={() => setDialog(null)} onCreated={order => { setDialog({ kind: 'order', id: order.id }); setNotice(`Pesanan #${order.id} berhasil dibuat. Total: ${rupiah(order.total)}`); refresh(); }} />}
    {dialog?.kind === 'order' && <OrderDetail key={dialog.id} id={dialog.id} onClose={() => setDialog(null)} onChanged={refresh} />}
  </div>;
}
function SummaryCard({ label, value, note, icon, warning = false }: { label: string; value: string; note: string; icon: string; warning?: boolean }) {
  return <article className={`summary-card ${warning ? 'warning' : ''}`}><div className="card-top"><span>{label}</span><span className="card-icon">{icon}</span></div><strong className="card-value">{value}</strong><span className="card-note">{warning && <i />}{note}</span></article>;
}
