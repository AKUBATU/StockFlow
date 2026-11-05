import { useEffect, useRef, type ReactNode } from 'react';

export function Modal({ title, children, onClose, busy = false }: { title: string; children: ReactNode; onClose: () => void; busy?: boolean }) {
  const ref = useRef<HTMLDialogElement>(null);
  useEffect(() => { const el = ref.current; el?.showModal(); return () => el?.close(); }, []);
  return <dialog ref={ref} aria-labelledby="modal-title" onCancel={e => { e.preventDefault(); if (!busy) onClose(); }}>
    <header className="modal-header"><h2 id="modal-title">{title}</h2><button className="icon-button" aria-label="Tutup dialog" disabled={busy} onClick={onClose}>×</button></header>
    {children}
  </dialog>;
}
export function ErrorBox({ text, retry }: { text: string; retry?: () => void }) {
  return <div className="error-box" role="alert"><span>{text}</span>{retry && <button className="button small" onClick={retry}>Coba lagi</button>}</div>;
}
