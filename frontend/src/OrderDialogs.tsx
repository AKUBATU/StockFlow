import { useEffect, useState, type FormEvent } from 'react';
import { api, ApiError, message } from './api';
import { Modal, ErrorBox } from './Modal';
import { MAX_INTEGER, rupiah, validateOrder } from './logic';
import { useDebounce } from './useDebounce';
import type { Order, OrderInput, Product } from './types';

function ProductPicker({ onPick }: { onPick: (p: Product) => void }) {
  const [search, setSearch] = useState(''); const debounced = useDebounce(search); const [products, setProducts] = useState<Product[]>([]); const [loading, setLoading] = useState(true); const [error, setError] = useState(''); const [retry, setRetry] = useState(0); const [page, setPage] = useState(1); const [total, setTotal] = useState(0);
  useEffect(() => {
    const controller = new AbortController(); setLoading(true); setError('');
    api.products(debounced, page, 'active', controller.signal).then(r => { setProducts(r.data); setTotal(r.total); }).catch(e => { if (!controller.signal.aborted) setError(message(e)); }).finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [debounced, page, retry]);
  return <section className="picker"><label>Cari produk aktif<input value={search} onChange={e => { setSearch(e.target.value); setPage(1); }} placeholder="Cari SKU atau nama…" /></label>
    {loading ? <p role="status">Memuat produk…</p> : error ? <ErrorBox text={error} retry={() => setRetry(n => n + 1)} /> : products.length === 0 ? <p className="muted">Produk tidak ditemukan.</p> : <><div className="picker-list">{products.map(p => <button key={p.id} type="button" onClick={() => onPick(p)}><span><strong>{p.name}</strong><small>{p.sku} · {rupiah(p.price)}</small></span><span className={p.stock <= 10 ? 'low-stock' : 'muted'}>{p.stock} unit <b>＋</b></span></button>)}</div><div className="pagination"><button type="button" className="button small" disabled={page === 1} onClick={() => setPage(n => n - 1)}>Sebelumnya</button><small>{page} / {Math.max(1, Math.ceil(total / 10))}</small><button type="button" className="button small" disabled={page * 10 >= total} onClick={() => setPage(n => n + 1)}>Berikutnya</button></div></>}
  </section>;
}
export function CreateOrder({ onClose, onCreated }: { onClose: () => void; onCreated: (order: Order) => void }) {
  const [input, setInput] = useState<OrderInput>({ customer: '', items: [] }); const [saving, setSaving] = useState(false); const [error, setError] = useState(''); const [failedSKU, setFailedSKU] = useState('');
  const [names, setNames] = useState<Record<string, string>>({});
  function pick(p: Product) {
    setNames(n => ({ ...n, [p.sku]: p.name }));
    setInput(current => ({ ...current, items: current.items.some(i => i.sku === p.sku) ? current.items.map(i => i.sku === p.sku ? { ...i, qty: i.qty + 1 } : i) : [...current.items, { sku: p.sku, qty: 1 }] }));
  }
  async function submit(e: FormEvent) {
    e.preventDefault(); const validation = validateOrder(input); if (validation) { setError(validation); return; }
    setSaving(true); setError(''); setFailedSKU('');
    try { onCreated(await api.createOrder({ ...input, customer: input.customer.trim() })); }
    catch (e) { setError(message(e)); if (e instanceof ApiError) setFailedSKU(e.detail.sku ?? ''); }
    finally { setSaving(false); }
  }
  return <Modal title="Buat pesanan" onClose={onClose} busy={saving}><form className="form" onSubmit={submit}>
    <p className="muted">Pilih produk dan jumlah. Harga dan total dihitung server saat pesanan disimpan.</p>
    {error && <ErrorBox text={error} />}
    <fieldset disabled={saving}>
      <label>Nama pelanggan<input required maxLength={200} placeholder="Contoh: Divisi Keuangan" value={input.customer} onChange={e => setInput({ ...input, customer: e.target.value })} /></label>
      <ProductPicker onPick={pick} />
      <div className="order-lines">{input.items.length === 0 ? <p className="empty">Tambahkan setidaknya satu produk.</p> : input.items.map((item, index) => <div key={item.sku} className={`order-line ${failedSKU === item.sku ? 'failed' : ''}`}>
        <span><strong>{names[item.sku]}</strong><small>{item.sku}{failedSKU === item.sku ? ' · Stok tidak cukup' : ''}</small></span>
        <label className="qty-label">Jumlah<input aria-label={`Jumlah ${item.sku}`} type="number" required min="1" max={MAX_INTEGER} step="1" value={item.qty} onChange={e => setInput({ ...input, items: input.items.map((i, n) => n === index ? { ...i, qty: e.target.valueAsNumber } : i) })} /></label>
        <button type="button" className="icon-button" aria-label={`Hapus ${item.sku}`} onClick={() => setInput({ ...input, items: input.items.filter((_, n) => n !== index) })}>×</button>
      </div>)}</div>
    </fieldset>
    <footer className="modal-footer"><button type="button" className="button" disabled={saving} onClick={onClose}>Batal</button><button className="button primary" disabled={saving || input.items.length === 0}>{saving ? 'Memproses…' : 'Buat pesanan'}</button></footer>
  </form></Modal>;
}
export function OrderDetail({ id, onClose, onChanged }: { id: number; onClose: () => void; onChanged: () => void }) {
  const [order, setOrder] = useState<Order | null>(null); const [error, setError] = useState(''); const [loading, setLoading] = useState(true); const [saving, setSaving] = useState(false); const [confirm, setConfirm] = useState(false); const [retry, setRetry] = useState(0); const [restored, setRestored] = useState(false);
  useEffect(() => { let live = true; setLoading(true); setError(''); api.order(id).then(o => { if (live) setOrder(o); }).catch(e => { if (live) setError(message(e)); }).finally(() => { if (live) setLoading(false); }); return () => { live = false; }; }, [id, retry]);
  async function cancel() {
    setSaving(true); setError('');
    try { setOrder(await api.cancelOrder(id)); setConfirm(false); setRestored(true); onChanged(); }
    catch (e) { setError(message(e)); }
    finally { setSaving(false); }
  }
  return <Modal title={`Pesanan #${String(id).padStart(4, '0')}`} onClose={onClose} busy={saving}><div className="form">
    {error && <ErrorBox text={error} retry={() => setRetry(n => n + 1)} />}
    {loading ? <p role="status">Memuat pesanan…</p> : order && <>
      <div className="detail-heading"><div><strong>{order.customer}</strong><small>{new Date(order.created_at).toLocaleString('id-ID')}</small></div><span className={`badge ${order.status === 'CREATED' ? 'green' : 'gray'}`}>{order.status === 'CREATED' ? 'Dibuat' : 'Dibatalkan'}</span></div>
      {restored && <p className="success-box" role="status">Pesanan dibatalkan. Stok telah dikembalikan dan daftar produk diperbarui.</p>}
      <div className="table-wrap"><table><thead><tr><th>SKU</th><th>Jumlah</th><th>Harga satuan</th><th>Subtotal</th></tr></thead><tbody>{order.items.map(i => <tr key={i.sku}><td className="sku">{i.sku}</td><td>{i.qty}</td><td>{rupiah(i.unit_price)}</td><td>{rupiah(i.qty * i.unit_price)}</td></tr>)}</tbody></table></div>
      <div className="order-total"><span>Total pesanan</span><strong>{rupiah(order.total)}</strong></div>
      {order.cancelled_at && <p className="muted">Dibatalkan {new Date(order.cancelled_at).toLocaleString('id-ID')}</p>}
      {order.status === 'CREATED' && (confirm ? <div className="confirm-box"><p>Batalkan pesanan dan kembalikan stok seluruh item?</p><div className="actions"><button className="button" disabled={saving} onClick={() => setConfirm(false)}>Kembali</button><button className="button danger" disabled={saving} onClick={cancel}>{saving ? 'Membatalkan…' : 'Ya, batalkan pesanan'}</button></div></div> : <footer className="modal-footer"><button className="button danger-outline" onClick={() => setConfirm(true)}>Batalkan pesanan</button></footer>)}
    </>}
  </div></Modal>;
}
