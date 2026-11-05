import { useEffect, useState, type FormEvent } from 'react';
import { api, message } from './api';
import { Modal, ErrorBox } from './Modal';
import { MAX_INTEGER, rupiah, validateProduct, validateStock } from './logic';
import type { Movement, Product, ProductInput, StockInput } from './types';

export function ProductForm({ product, onClose, onSaved }: { product?: Product; onClose: () => void; onSaved: () => void }) {
  const [value, setValue] = useState<ProductInput>({ sku: product?.sku ?? '', name: product?.name ?? '', price: product?.price ?? 0, is_active: product?.is_active ?? true });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  async function submit(e: FormEvent) {
    e.preventDefault(); const validation = validateProduct(value); if (validation) { setError(validation); return; }
    setSaving(true); setError('');
    try {
      const input = { ...value, sku: value.sku.trim(), name: value.name.trim() };
      if (product) await api.updateProduct(product.id, input); else await api.createProduct(input);
      onSaved();
    } catch (e) { setError(message(e)); } finally { setSaving(false); }
  }
  return <Modal title={product ? 'Edit produk' : 'Tambah produk'} onClose={onClose} busy={saving}>
    <form onSubmit={submit} className="form"><p className="muted">Kelola katalog. Stok berubah melalui penyesuaian atau pesanan.</p>
      {error && <ErrorBox text={error} />}
      <fieldset disabled={saving}>
        <label>SKU<input required maxLength={100} value={value.sku} onChange={e => setValue({ ...value, sku: e.target.value })} placeholder="ATK015" /></label>
        <label>Nama produk<input required maxLength={200} value={value.name} onChange={e => setValue({ ...value, name: e.target.value })} placeholder="Nama produk" /></label>
        <label>Harga satuan (Rp)<input type="number" required min="0" max={MAX_INTEGER} step="1" value={value.price} onChange={e => setValue({ ...value, price: e.target.valueAsNumber })} /></label>
        <label className="checkbox"><input type="checkbox" checked={value.is_active} onChange={e => setValue({ ...value, is_active: e.target.checked })} />Produk aktif dan dapat dipesan</label>
      </fieldset>
      <footer className="modal-footer"><button type="button" className="button" disabled={saving} onClick={onClose}>Batal</button><button className="button primary" disabled={saving}>{saving ? 'Menyimpan…' : 'Simpan produk'}</button></footer>
    </form>
  </Modal>;
}
export function StockForm({ product, onClose, onSaved }: { product: Product; onClose: () => void; onSaved: () => void }) {
  const [value, setValue] = useState<StockInput>({ type: 'in', qty: 1, note: '' });
  const [saving, setSaving] = useState(false); const [error, setError] = useState('');
  async function submit(e: FormEvent) {
    e.preventDefault(); const validation = validateStock(value); if (validation) { setError(validation); return; }
    setError(''); setSaving(true);
    try { await api.stock(product.id, value); onSaved(); } catch (e) { setError(message(e)); } finally { setSaving(false); }
  }
  return <Modal title="Sesuaikan stok" onClose={onClose} busy={saving}>
    <form className="form" onSubmit={submit}>
      <div className="product-callout"><span className="sku">{product.sku}</span><strong>{product.name}</strong><span className="muted">Stok saat dibuka: {product.stock} unit · {rupiah(product.price)}</span></div>
      {error && <ErrorBox text={error} />}
      <fieldset disabled={saving}>
        <label>Arah pergerakan<select value={value.type} onChange={e => setValue({ ...value, type: e.target.value === 'in' ? 'in' : 'out' })}><option value="in">Masuk (+)</option><option value="out">Keluar (−)</option></select></label>
        <label>Jumlah<input required type="number" min="1" max={MAX_INTEGER} step="1" value={value.qty} onChange={e => setValue({ ...value, qty: e.target.valueAsNumber })} /></label>
        <label>Catatan<textarea maxLength={1000} value={value.note} onChange={e => setValue({ ...value, note: e.target.value })} placeholder="Contoh: restock dari pemasok" /></label>
      </fieldset>
      <footer className="modal-footer"><button type="button" className="button" disabled={saving} onClick={onClose}>Batal</button><button className="button primary" disabled={saving}>{saving ? 'Menyimpan…' : 'Simpan penyesuaian'}</button></footer>
    </form>
  </Modal>;
}
export function MovementHistory({ product, onClose }: { product: Product; onClose: () => void }) {
  const [data, setData] = useState<Movement[]>([]); const [loading, setLoading] = useState(true); const [error, setError] = useState(''); const [retry, setRetry] = useState(0);
  useEffect(() => { let live = true; setLoading(true); setError(''); api.movements(product.id).then(r => { if (live) setData(r.data); }).catch(e => { if (live) setError(message(e)); }).finally(() => { if (live) setLoading(false); }); return () => { live = false; }; }, [product.id, retry]);
  return <Modal title={`Riwayat · ${product.sku}`} onClose={onClose}><div className="form"><p className="muted">{product.name} · Pergerakan terbaru ditampilkan lebih dulu.</p>
    {loading ? <p role="status">Memuat riwayat…</p> : error ? <ErrorBox text={error} retry={() => setRetry(n => n + 1)} /> : data.length === 0 ? <p className="empty">Belum ada pergerakan stok.</p> : <ul className="history">{data.map(m => <li key={m.id}><span className={`badge ${m.type === 'in' ? 'green' : 'amber'}`}>{m.type === 'in' ? '+' : '−'}{m.qty}</span><div><strong>{m.note || 'Penyesuaian stok'}</strong><small>{new Date(m.created_at).toLocaleString('id-ID')}</small></div></li>)}</ul>}
  </div></Modal>;
}
export function DeleteProduct({ product, onClose, onSaved }: { product: Product; onClose: () => void; onSaved: () => void }) {
  const [saving, setSaving] = useState(false); const [error, setError] = useState('');
  async function remove() { setSaving(true); setError(''); try { await api.deleteProduct(product.id); onSaved(); } catch (e) { setError(message(e)); } finally { setSaving(false); } }
  return <Modal title="Hapus produk?" onClose={onClose} busy={saving}><div className="form"><p><strong>{product.sku} · {product.name}</strong></p><p className="muted">Produk yang memiliki riwayat akan ditolak oleh server. Anda dapat menonaktifkannya melalui Edit.</p>{error && <ErrorBox text={error} />}<footer className="modal-footer"><button className="button" disabled={saving} onClick={onClose}>Kembali</button><button className="button danger" disabled={saving} onClick={remove}>{saving ? 'Menghapus…' : 'Hapus produk'}</button></footer></div></Modal>;
}
