// Abby Collection - skrip antarmuka
document.addEventListener('DOMContentLoaded', () => {
  // Menu mobile
  const toggle = document.querySelector('.nav-toggle');
  const links = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const open = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open);
    });
  }

  // Tombol +/- jumlah
  document.querySelectorAll('.qty').forEach(q => {
    const input = q.querySelector('input');
    q.querySelectorAll('button').forEach(b => b.addEventListener('click', () => {
      const max = parseInt(input.max || '99', 10);
      let v = parseInt(input.value || '1', 10) + (b.dataset.step === '+' ? 1 : -1);
      input.value = Math.min(Math.max(v, 1), max);
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }));
  });

  // Detail produk: batasi jumlah sesuai stok varian yang dipilih
  const qtyInput = document.querySelector('[data-qty-for-stock]');
  document.querySelectorAll('input[name="id_stok"][data-stok]').forEach(r => {
    r.addEventListener('change', () => {
      if (!qtyInput) return;
      qtyInput.max = r.dataset.stok;
      if (+qtyInput.value > +r.dataset.stok) qtyInput.value = r.dataset.stok;
      const info = document.getElementById('stok-info');
      if (info) info.textContent = 'Stok tersedia: ' + r.dataset.stok;
    });
  });

  // Keranjang: pilih semua + hitung subtotal terpilih
  const all = document.getElementById('pilih-semua');
  const items = [...document.querySelectorAll('.pilih-item')];
  const subtotalEl = document.getElementById('subtotal-terpilih');
  const hitung = () => {
    if (!subtotalEl) return;
    const total = items.filter(i => i.checked).reduce((s, i) => s + parseFloat(i.dataset.subtotal), 0);
    subtotalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
  };
  if (all) all.addEventListener('change', () => { items.forEach(i => i.checked = all.checked); hitung(); });
  items.forEach(i => i.addEventListener('change', hitung));
  hitung();

  // Checkout: tampilkan opsi sesuai metode pengiriman & pembayaran, hitung ongkir
  const kirim = document.querySelectorAll('input[name="metode_pengiriman"]');
  if (kirim.length) {
    const boxAlamat = document.getElementById('box-alamat');
    const boxAmbil = document.getElementById('box-ambil');
    const kurir = document.getElementById('kurir');
    const ongkirEl = document.getElementById('ongkir-view');
    const totalEl = document.getElementById('total-view');
    const base = parseFloat(totalEl?.dataset.base || '0');
    const fmt = n => 'Rp ' + n.toLocaleString('id-ID');
    const update = () => {
      const m = document.querySelector('input[name="metode_pengiriman"]:checked')?.value;
      const antar = m === 'diantar';
      if (boxAlamat) boxAlamat.hidden = !antar;
      if (boxAmbil) boxAmbil.hidden = antar;
      const ongkir = antar && kurir ? parseFloat(kurir.selectedOptions[0]?.dataset.ongkir || '0') : 0;
      if (ongkirEl) ongkirEl.textContent = fmt(ongkir);
      if (totalEl) totalEl.textContent = fmt(base + ongkir);
    };
    kirim.forEach(r => r.addEventListener('change', update));
    if (kurir) kurir.addEventListener('change', update);
    update();
  }

  // Konfirmasi aksi berbahaya
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('submit', ev => { if (!confirm(el.dataset.confirm)) ev.preventDefault(); });
  });

  // Pratinjau foto sebelum upload
  const foto = document.getElementById('foto-input');
  const prev = document.getElementById('foto-preview');
  if (foto && prev) foto.addEventListener('change', () => {
    const f = foto.files[0];
    if (f) { prev.src = URL.createObjectURL(f); prev.hidden = false; }
  });
});
