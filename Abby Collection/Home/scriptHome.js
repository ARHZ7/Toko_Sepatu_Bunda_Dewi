document.addEventListener('DOMContentLoaded', () => {
  // ==================== HELPER & INITIAL DATA ====================
  const createShoeSVG = (label, bg, color) => {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">
      <rect width="400" height="300" fill="${bg}"/>
      <path d="M85 195 Q130 130 195 152 L270 175 Q315 185 315 210 L82 210 Z" fill="${color}"/>
      <rect x="80" y="210" width="238" height="12" rx="6" fill="#291C0E"/>
      <text x="200" y="255" font-family="sans-serif" font-size="14" fill="#291C0E" text-anchor="middle" opacity="0.75">${label}</text>
    </svg>`;
    return `data:image/svg+xml;utf8,${encodeURIComponent(svg)}`;
  };

  const defaultDB = {
    users: [
      {
        id: 1,
        role: 'admin',
        name: 'Admin Abby',
        email: 'admin@abby.com',
        password: 'admin123',
        phone: '081100001111',
        addresses: []
      },
      {
        id: 2,
        role: 'customer',
        name: 'Amri Hamzah',
        email: 'user@abby.com',
        password: 'user123',
        phone: '081234567890',
        addresses: [
          {
            id: 101,
            label: 'Rumah Utama',
            recipient: 'Amri Hamzah (081234567890)',
            full: 'Jl. Perintis Kemerdekaan Km. 10, Tamalanrea, Makassar'
          }
        ]
      }
    ],
    brands: ['Abby Studio', 'Urban Step', 'Terra Walk'],
    categories: ['Loafers', 'Sneakers', 'Slip-On', 'Boots'],
    products: [
      {
        id: 1001,
        name: 'Abby Classic Espresso Loafers',
        brand: 'Abby Studio',
        category: 'Loafers',
        price: 385000,
        desc: 'Loafers kulit minimalis dengan sol empuk untuk tampilan formal maupun kasual.',
        image: createShoeSVG('Espresso Loafers', '#E1D4C2', '#291C0E'),
        variants: [
          { color: 'Espresso', size: 40, stock: 6 },
          { color: 'Espresso', size: 41, stock: 4 },
          { color: 'Cocoa', size: 42, stock: 5 }
        ]
      },
      {
        id: 1002,
        name: 'Cocoa Suede Daily Sneakers',
        brand: 'Urban Step',
        category: 'Sneakers',
        price: 430000,
        desc: 'Sneakers berbahan suede lembut bernuansa cokelat hangat yang ringan dipakai seharian.',
        image: createShoeSVG('Cocoa Sneakers', '#D8C8B4', '#6E473B'),
        variants: [
          { color: 'Cocoa', size: 39, stock: 5 },
          { color: 'Cocoa', size: 40, stock: 8 },
          { color: 'Sand', size: 41, stock: 3 }
        ]
      },
      {
        id: 1003,
        name: 'Sand Minimalist Slip-On',
        brand: 'Terra Walk',
        category: 'Slip-On',
        price: 315000,
        desc: 'Sepatu slip-on praktis dengan warna krem pasir yang mudah dipadukan dengan berbagai outfit.',
        image: createShoeSVG('Sand Slip-On', '#EAE0D2', '#A78D78'),
        variants: [
          { color: 'Sand', size: 38, stock: 7 },
          { color: 'Sand', size: 40, stock: 5 },
          { color: 'Espresso', size: 42, stock: 2 }
        ]
      }
    ],
    orders: []
  };

  let db = JSON.parse(localStorage.getItem('abby_full_db')) || defaultDB;
  let currentUser = JSON.parse(localStorage.getItem('abby_current_user')) || null;
  let cart = JSON.parse(localStorage.getItem('abby_user_cart')) || [];

  const saveDB = () => localStorage.setItem('abby_full_db', JSON.stringify(db));
  const saveCart = () => localStorage.setItem('abby_user_cart', JSON.stringify(cart));

  const formatRp = (num) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);

  const toastEl = document.getElementById('toast');
  const showToast = (msg) => {
    toastEl.textContent = msg;
    toastEl.classList.add('show');
    setTimeout(() => toastEl.classList.remove('show'), 2600);
  };

  // ==================== 1. AUTENTIKASI (LOGIN & REGISTRASI) ====================
  const authView = document.getElementById('authView');
  const customerApp = document.getElementById('customerApp');
  const adminApp = document.getElementById('adminApp');

  document.querySelectorAll('.auth-tab').forEach((tab) => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.auth-tab').forEach((t) => t.classList.remove('active'));
      tab.classList.add('active');
      const target = tab.dataset.target;
      document.getElementById('loginForm').classList.toggle('hidden', target !== 'loginForm');
      document.getElementById('registerForm').classList.toggle('hidden', target !== 'registerForm');
    });
  });

  // Login
  document.getElementById('loginForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value.trim().toLowerCase();
    const password = document.getElementById('loginPassword').value;

    const foundUser = db.users.find((u) => u.email.toLowerCase() === email && u.password === password);
    if (!foundUser) {
      showToast('Email atau kata sandi salah!');
      return;
    }

    currentUser = foundUser;
    localStorage.setItem('abby_current_user', JSON.stringify(currentUser));
    e.target.reset();
    routeApp();
    showToast(`Selamat datang, ${currentUser.name}!`);
  });

  // Registrasi Pelanggan
  document.getElementById('registerForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('regName').value.trim();
    const email = document.getElementById('regEmail').value.trim().toLowerCase();
    const phone = document.getElementById('regPhone').value.trim();
    const password = document.getElementById('regPassword').value;

    if (db.users.some((u) => u.email.toLowerCase() === email)) {
      showToast('Email sudah terdaftar. Silakan login.');
      return;
    }

    const newUser = {
      id: Date.now(),
      role: 'customer',
      name,
      email,
      password,
      phone,
      addresses: []
    };

    db.users.push(newUser);
    saveDB();
    currentUser = newUser;
    localStorage.setItem('abby_current_user', JSON.stringify(currentUser));
    e.target.reset();
    routeApp();
    showToast('Registrasi berhasil! Anda telah masuk.');
  });

  // Logout (User & Admin)
  const handleLogout = () => {
    currentUser = null;
    localStorage.removeItem('abby_current_user');
    routeApp();
    showToast('Anda telah keluar dari akun.');
  };

  document.getElementById('userLogoutBtn').addEventListener('click', handleLogout);
  document.getElementById('adminLogoutBtn').addEventListener('click', handleLogout);

  // ==================== ROUTING ANTARA AUTH / USER / ADMIN ====================
  const routeApp = () => {
    authView.classList.add('hidden');
    customerApp.classList.add('hidden');
    adminApp.classList.add('hidden');

    if (!currentUser) {
      authView.classList.remove('hidden');
      return;
    }

    if (currentUser.role === 'admin') {
      adminApp.classList.remove('hidden');
      initAdminView();
    } else {
      customerApp.classList.remove('hidden');
      initCustomerView();
    }
  };

  // ==================== 2. LOGIKA VERSI PELANGGAN ====================
  const initCustomerView = () => {
    document.getElementById('navUserName').textContent = `Halo, ${currentUser.name}`;
    populateCustomerFilters();
    renderCustomerCatalog();
    renderCartAndCheckout();
    renderCustomerOrders();
    renderCustomerProfile();
  };

  // Navigasi Tab Pelanggan
  document.querySelectorAll('[data-user-nav]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.userNav;
      document.querySelectorAll('[data-user-nav]').forEach((b) => b.classList.remove('active'));
      document.querySelectorAll(`.nav-btn[data-user-nav="${target}"]`).forEach((b) => b.classList.add('active'));

      document.getElementById('userCatalogTab').classList.toggle('hidden', target !== 'catalog');
      document.getElementById('userCartTab').classList.toggle('hidden', target !== 'cart');
      document.getElementById('userOrdersTab').classList.toggle('hidden', target !== 'orders');
      document.getElementById('userProfileTab').classList.toggle('hidden', target !== 'profile');

      if (target === 'cart') renderCartAndCheckout();
      if (target === 'orders') renderCustomerOrders();
      if (target === 'profile') renderCustomerProfile();
    });
  });

  // Isi Opsi Filter Kategori, Merek, Warna
  const populateCustomerFilters = () => {
    const catSelect = document.getElementById('filterCategory');
    const brandSelect = document.getElementById('filterBrand');
    const colorSelect = document.getElementById('filterColor');

    catSelect.innerHTML = '<option value="">Semua Kategori</option>' +
      db.categories.map((c) => `<option value="${c}">${c}</option>`).join('');

    brandSelect.innerHTML = '<option value="">Semua Merek</option>' +
      db.brands.map((b) => `<option value="${b}">${b}</option>`).join('');

    const allColors = [...new Set(db.products.flatMap((p) => p.variants.map((v) => v.color)))];
    colorSelect.innerHTML = '<option value="">Semua Warna</option>' +
      allColors.map((col) => `<option value="${col}">${col}</option>`).join('');
  };

  // Render Katalog Sepatu
  const renderCustomerCatalog = () => {
    const keyword = document.getElementById('searchShoe').value.toLowerCase();
    const cat = document.getElementById('filterCategory').value;
    const brand = document.getElementById('filterBrand').value;
    const size = document.getElementById('filterSize').value;
    const color = document.getElementById('filterColor').value;
    const maxPrice = Number(document.getElementById('filterMaxPrice').value);

    const filtered = db.products.filter((p) => {
      const matchName = p.name.toLowerCase().includes(keyword);
      const matchCat = !cat || p.category === cat;
      const matchBrand = !brand || p.brand === brand;
      const matchPrice = !maxPrice || p.price <= maxPrice;
      const matchSize = !size || p.variants.some((v) => String(v.size) === size && v.stock > 0);
      const matchColor = !color || p.variants.some((v) => v.color === color && v.stock > 0);
      return matchName && matchCat && matchBrand && matchPrice && matchSize && matchColor;
    });

    const grid = document.getElementById('customerProductGrid');
    if (filtered.length === 0) {
      grid.innerHTML = '<p>Tidak ada sepatu yang sesuai dengan filter pencarian Anda.</p>';
      return;
    }

    grid.innerHTML = filtered
      .map((p) => {
        const totalStock = p.variants.reduce((sum, v) => sum + v.stock, 0);
        return `
        <div class="product-card">
          <div class="product-img-wrap">
            <img src="${p.image}" alt="${p.name}">
            <span class="stock-pill ${totalStock === 0 ? 'out' : ''}">
              ${totalStock > 0 ? `Stok: ${totalStock}` : 'Stok Habis'}
            </span>
          </div>
          <div class="product-body">
            <span class="product-meta">${p.category} • ${p.brand}</span>
            <h3 class="product-title">${p.name}</h3>
            <p class="product-price">${formatRp(p.price)}</p>
            <button type="button" class="btn-primary full-width btn-detail-shoe" data-id="${p.id}">
              Lihat Detail & Pilih
            </button>
          </div>
        </div>`;
      })
      .join('');
  };

  ['searchShoe', 'filterCategory', 'filterBrand', 'filterSize', 'filterColor', 'filterMaxPrice'].forEach((id) => {
    document.getElementById(id).addEventListener('input', renderCustomerCatalog);
  });

  document.getElementById('resetFilterBtn').addEventListener('click', () => {
    document.getElementById('searchShoe').value = '';
    document.getElementById('filterCategory').value = '';
    document.getElementById('filterBrand').value = '';
    document.getElementById('filterSize').value = '';
    document.getElementById('filterColor').value = '';
    document.getElementById('filterMaxPrice').value = '';
    renderCustomerCatalog();
  });

  // Modal Detail Sepatu & Pilih Variasi
  let activeModalProduct = null;
  const productDetailModal = document.getElementById('productDetailModal');
  const modalSelectColor = document.getElementById('modalSelectColor');
  const modalSelectSize = document.getElementById('modalSelectSize');

  const updateModalVariantStock = () => {
    if (!activeModalProduct) return;
    const color = modalSelectColor.value;
    const size = Number(modalSelectSize.value);
    const variant = activeModalProduct.variants.find((v) => v.color === color && v.size === size);
    const stock = variant ? variant.stock : 0;

    document.getElementById('modalStockInfo').textContent =
      stock > 0 ? `Tersedia: ${stock} pasang` : 'Stok variasi ini habis';
    const qtyInput = document.getElementById('modalQty');
    qtyInput.max = stock || 1;
    qtyInput.value = 1;
    document.getElementById('modalAddToCartBtn').disabled = stock === 0;
  };

  const populateModalSizes = () => {
    if (!activeModalProduct) return;
    const selectedColor = modalSelectColor.value;
    const sizesForColor = activeModalProduct.variants.filter((v) => v.color === selectedColor);
    modalSelectSize.innerHTML = sizesForColor
      .map((v) => `<option value="${v.size}">${v.size} (Stok: ${v.stock})</option>`)
      .join('');
    updateModalVariantStock();
  };

  document.getElementById('customerProductGrid').addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-detail-shoe');
    if (!btn) return;
    const prodId = Number(btn.dataset.id);
    activeModalProduct = db.products.find((p) => p.id === prodId);
    if (!activeModalProduct) return;

    document.getElementById('modalShoeImg').src = activeModalProduct.image;
    document.getElementById('modalShoeMeta').textContent = `${activeModalProduct.category} • ${activeModalProduct.brand}`;
    document.getElementById('modalShoeName').textContent = activeModalProduct.name;
    document.getElementById('modalShoePrice').textContent = formatRp(activeModalProduct.price);
    document.getElementById('modalShoeDesc').textContent = activeModalProduct.desc;

    const colors = [...new Set(activeModalProduct.variants.map((v) => v.color))];
    if (colors.length === 0) {
      modalSelectColor.innerHTML = '<option value="">Belum ada variasi</option>';
      modalSelectSize.innerHTML = '<option value="">-</option>';
      document.getElementById('modalStockInfo').textContent = 'Stok belum tersedia';
      document.getElementById('modalAddToCartBtn').disabled = true;
    } else {
      modalSelectColor.innerHTML = colors.map((c) => `<option value="${c}">${c}</option>`).join('');
      populateModalSizes();
    }

    productDetailModal.classList.remove('hidden');
  });

  modalSelectColor.addEventListener('change', populateModalSizes);
  modalSelectSize.addEventListener('change', updateModalVariantStock);
  document.getElementById('closeDetailModal').addEventListener('click', () => {
    productDetailModal.classList.add('hidden');
  });

  // Tambah ke Keranjang
  document.getElementById('modalAddToCartBtn').addEventListener('click', () => {
    if (!activeModalProduct) return;
    const color = modalSelectColor.value;
    const size = Number(modalSelectSize.value);
    const qty = Number(document.getElementById('modalQty').value);
    const variant = activeModalProduct.variants.find((v) => v.color === color && v.size === size);

    if (!variant || variant.stock < qty || qty < 1) {
      showToast('Jumlah melebihi stok yang tersedia!');
      return;
    }

    const cartKey = `${activeModalProduct.id}-${color}-${size}`;
    const existing = cart.find((item) => item.key === cartKey);

    if (existing) {
      if (existing.qty + qty > variant.stock) {
        showToast('Total di keranjang melebihi stok tersedia!');
        return;
      }
      existing.qty += qty;
    } else {
      cart.push({
        key: cartKey,
        productId: activeModalProduct.id,
        name: activeModalProduct.name,
        price: activeModalProduct.price,
        image: activeModalProduct.image,
        color,
        size,
        qty,
        selected: true
      });
    }

    saveCart();
    updateCartBadge();
    productDetailModal.classList.add('hidden');
    showToast('Sepatu berhasil ditambahkan ke keranjang!');
  });

  const updateCartBadge = () => {
    const total = cart.reduce((sum, item) => sum + item.qty, 0);
    document.getElementById('navCartCount').textContent = total;
  };

  // ==================== KERANJANG & CHECKOUT ====================
  const renderCartAndCheckout = () => {
    updateCartBadge();
    const container = document.getElementById('cartItemsContainer');

    if (cart.length === 0) {
      container.innerHTML = '<p>Keranjang belanja Anda masih kosong.</p>';
    } else {
      container.innerHTML = cart
        .map(
          (item, idx) => `
          <div class="cart-item">
            <input type="checkbox" class="cart-select-cb" data-idx="${idx}" ${item.selected ? 'checked' : ''}>
            <img src="${item.image}" alt="${item.name}">
            <div class="cart-item-info">
              <h4>${item.name}</h4>
              <p>Warna: <strong>${item.color}</strong> | Ukuran: <strong>${item.size}</strong></p>
              <p><strong>${formatRp(item.price)}</strong></p>
              <div class="qty-controls">
                <button type="button" class="qty-btn" data-action="dec" data-idx="${idx}">-</button>
                <span>${item.qty}</span>
                <button type="button" class="qty-btn" data-action="inc" data-idx="${idx}">+</button>
                <button type="button" class="btn-danger sm" data-action="del" data-idx="${idx}">Hapus</button>
              </div>
            </div>
          </div>`
        )
        .join('');
    }

    // Isi dropdown alamat pelanggan
    const addrSelect = document.getElementById('checkoutAddressSelect');
    const freshUser = db.users.find((u) => u.id === currentUser.id);
    if (freshUser && freshUser.addresses.length > 0) {
      addrSelect.innerHTML = freshUser.addresses
        .map((a) => `<option value="${a.label} - ${a.recipient} (${a.full})">${a.label} — ${a.full}</option>`)
        .join('');
    } else {
      addrSelect.innerHTML = '<option value="">-- Belum ada alamat tersimpan --</option>';
    }

    recalculateCheckoutSummary();
  };

  document.getElementById('cartItemsContainer').addEventListener('change', (e) => {
    if (e.target.classList.contains('cart-select-cb')) {
      const idx = Number(e.target.dataset.idx);
      cart[idx].selected = e.target.checked;
      saveCart();
      recalculateCheckoutSummary();
    }
  });

  document.getElementById('cartItemsContainer').addEventListener('click', (e) => {
    const idx = Number(e.target.dataset.idx);
    const action = e.target.dataset.action;
    if (isNaN(idx) || !action) return;

    const item = cart[idx];
    const prod = db.products.find((p) => p.id === item.productId);
    const variant = prod ? prod.variants.find((v) => v.color === item.color && v.size === item.size) : null;

    if (action === 'inc') {
      if (variant && item.qty + 1 <= variant.stock) {
        item.qty += 1;
      } else {
        showToast('Mencapai batas maksimal stok!');
      }
    } else if (action === 'dec') {
      if (item.qty > 1) {
        item.qty -= 1;
      }
    } else if (action === 'del') {
      cart.splice(idx, 1);
    }

    saveCart();
    renderCartAndCheckout();
  });

  // Toggle Metode Pengiriman & Pembayaran
  document.querySelectorAll('input[name="shippingMethod"]').forEach((radio) => {
    radio.addEventListener('change', () => {
      const isDelivery = radio.value === 'Diantar ke Alamat';
      document.getElementById('shippingAddressBox').classList.toggle('hidden', !isDelivery);
      document.getElementById('pickupLocationBox').classList.toggle('hidden', isDelivery);
      recalculateCheckoutSummary();
    });
  });

  document.querySelectorAll('input[name="paymentMethod"]').forEach((radio) => {
    radio.addEventListener('change', () => {
      const isManual = radio.value === 'Transfer Bank Manual';
      document.getElementById('manualTransferBox').classList.toggle('hidden', !isManual);
      document.getElementById('qrisBox').classList.toggle('hidden', isManual);
    });
  });

  const recalculateCheckoutSummary = () => {
    const selectedItems = cart.filter((i) => i.selected);
    const subtotal = selectedItems.reduce((sum, i) => sum + i.price * i.qty, 0);
    const shippingMethod = document.querySelector('input[name="shippingMethod"]:checked').value;
    const shippingCost = selectedItems.length > 0 && shippingMethod === 'Diantar ke Alamat' ? 15000 : 0;

    document.getElementById('selectedItemCount').textContent = `${selectedItems.length} jenis barang`;
    document.getElementById('shippingCostDisplay').textContent = formatRp(shippingCost);
    document.getElementById('checkoutTotalDisplay').textContent = formatRp(subtotal + shippingCost);
  };

  // Proses Submit Checkout & Pembayaran
  document.getElementById('checkoutForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const selectedItems = cart.filter((i) => i.selected);
    if (selectedItems.length === 0) {
      showToast('Pilih minimal 1 sepatu di keranjang untuk checkout!');
      return;
    }

    const shippingMethod = document.querySelector('input[name="shippingMethod"]:checked').value;
    const selectedAddr = document.getElementById('checkoutAddressSelect').value;

    if (shippingMethod === 'Diantar ke Alamat' && !selectedAddr) {
      showToast('Tambahkan dan pilih alamat pengiriman terlebih dahulu!');
      return;
    }

    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
    const proofFile = document.getElementById('transferProofInput').files[0];

    if (paymentMethod === 'Transfer Bank Manual' && !proofFile) {
      showToast('Unggah bukti transfer untuk pembayaran Transfer Bank Manual!');
      return;
    }

    const finalizeOrder = (proofDataUrl) => {
      // Kurangi stok produk
      selectedItems.forEach((item) => {
        const prod = db.products.find((p) => p.id === item.productId);
        if (prod) {
          const variant = prod.variants.find((v) => v.color === item.color && v.size === item.size);
          if (variant) variant.stock = Math.max(0, variant.stock - item.qty);
        }
      });

      const subtotal = selectedItems.reduce((sum, i) => sum + i.price * i.qty, 0);
      const shippingCost = shippingMethod === 'Diantar ke Alamat' ? 15000 : 0;

      const newOrder = {
        id: 'ORD-' + Date.now().toString().slice(-6),
        userId: currentUser.id,
        userName: currentUser.name,
        userEmail: currentUser.email,
        date: new Date().toLocaleString('id-ID'),
        items: selectedItems,
        shippingMethod,
        shippingInfo:
          shippingMethod === 'Diantar ke Alamat'
            ? selectedAddr
            : 'Ambil di Toko: Abby Collection Store — Jl. Boulevard No. 45, Makassar',
        paymentMethod,
        paymentProof: proofDataUrl,
        paymentStatus: paymentMethod === 'QRIS' ? 'Lunas (Terverifikasi QRIS)' : 'Menunggu Konfirmasi Admin',
        orderStatus: 'Diproses',
        totalAmount: subtotal + shippingCost
      };

      db.orders.unshift(newOrder);
      saveDB();

      // Hapus barang yang sudah dibeli dari keranjang
      cart = cart.filter((i) => !i.selected);
      saveCart();
      e.target.reset();

      renderCustomerCatalog();
      renderCartAndCheckout();
      renderCustomerOrders();
      showToast(`Pesanan ${newOrder.id} berhasil dibuat!`);
    };

    if (proofFile) {
      const reader = new FileReader();
      reader.onload = (ev) => finalizeOrder(ev.target.result);
      reader.readAsDataURL(proofFile);
    } else {
      finalizeOrder(null);
    }
  });

  // ==================== RIWAYAT PESANAN PELANGGAN ====================
  const renderCustomerOrders = () => {
    const list = document.getElementById('customerOrdersList');
    const myOrders = db.orders.filter((o) => o.userId === currentUser.id);

    if (myOrders.length === 0) {
      list.innerHTML = '<div class="card-panel"><p>Anda belum memiliki riwayat pesanan.</p></div>';
      return;
    }

    list.innerHTML = myOrders
      .map(
        (o) => `
        <div class="order-card">
          <div class="order-header">
            <div>
              <strong>${o.id}</strong> • <small>${o.date}</small>
            </div>
            <div>
              <span class="status-badge ${o.paymentStatus.includes('Lunas') ? 'paid' : ''}">${o.paymentStatus}</span>
              <span class="status-badge">Pesanan: ${o.orderStatus}</span>
            </div>
          </div>
          <div class="order-items-mini">
            ${o.items
              .map((i) => `<div>• ${i.name} (${i.color}, Size ${i.size}) x${i.qty} — ${formatRp(i.price * i.qty)}</div>`)
              .join('')}
          </div>
          <p><strong>Metode Pengiriman:</strong> ${o.shippingMethod}</p>
          <p><strong>Info Lokasi / Alamat:</strong> ${o.shippingInfo}</p>
          <p><strong>Pembayaran:</strong> ${o.paymentMethod} — Total: <strong>${formatRp(o.totalAmount)}</strong></p>
        </div>`
      )
      .join('');
  };

  // ==================== KELOLA PROFIL & ALAMAT PELANGGAN ====================
  const renderCustomerProfile = () => {
    const freshUser = db.users.find((u) => u.id === currentUser.id);
    if (!freshUser) return;
    currentUser = freshUser;

    document.getElementById('profName').value = freshUser.name;
    document.getElementById('profEmail').value = freshUser.email;
    document.getElementById('profPhone').value = freshUser.phone;

    const addrContainer = document.getElementById('addressListContainer');
    if (freshUser.addresses.length === 0) {
      addrContainer.innerHTML = '<p>Belum ada alamat pengiriman tersimpan.</p>';
    } else {
      addrContainer.innerHTML = freshUser.addresses
        .map(
          (a) => `
          <div class="address-item">
            <div>
              <strong>${a.label}</strong> — <small>${a.recipient}</small>
              <p>${a.full}</p>
            </div>
            <div class="btn-row">
              <button type="button" class="btn-outline sm" data-addr-edit="${a.id}">Ubah</button>
              <button type="button" class="btn-danger sm" data-addr-del="${a.id}">Hapus</button>
            </div>
          </div>`
        )
        .join('');
    }
  };

  document.getElementById('updateProfileForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const userObj = db.users.find((u) => u.id === currentUser.id);
    userObj.name = document.getElementById('profName').value.trim();
    userObj.phone = document.getElementById('profPhone').value.trim();
    saveDB();
    localStorage.setItem('abby_current_user', JSON.stringify(userObj));
    document.getElementById('navUserName').textContent = `Halo, ${userObj.name}`;
    showToast('Data profil berhasil diperbarui!');
  });

  document.getElementById('updatePasswordForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const oldPass = document.getElementById('oldPassword').value;
    const newPass = document.getElementById('newPassword').value;
    const userObj = db.users.find((u) => u.id === currentUser.id);

    if (userObj.password !== oldPass) {
      showToast('Kata sandi lama tidak sesuai!');
      return;
    }

    userObj.password = newPass;
    saveDB();
    e.target.reset();
    showToast('Kata sandi berhasil diperbarui!');
  });

  document.getElementById('addressForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const editId = document.getElementById('editAddressId').value;
    const label = document.getElementById('addrLabel').value.trim();
    const recipient = document.getElementById('addrRecipient').value.trim();
    const full = document.getElementById('addrFull').value.trim();

    const userObj = db.users.find((u) => u.id === currentUser.id);
    if (editId) {
      const addr = userObj.addresses.find((a) => a.id === Number(editId));
      if (addr) {
        addr.label = label;
        addr.recipient = recipient;
        addr.full = full;
      }
      showToast('Alamat berhasil diubah!');
    } else {
      userObj.addresses.push({ id: Date.now(), label, recipient, full });
      showToast('Alamat baru ditambahkan!');
    }

    saveDB();
    resetAddressForm();
    renderCustomerProfile();
  });

  const resetAddressForm = () => {
    document.getElementById('addressForm').reset();
    document.getElementById('editAddressId').value = '';
    document.getElementById('saveAddressBtn').textContent = 'Tambah Alamat';
    document.getElementById('cancelEditAddrBtn').classList.add('hidden');
  };

  document.getElementById('cancelEditAddrBtn').addEventListener('click', resetAddressForm);

  document.getElementById('addressListContainer').addEventListener('click', (e) => {
    const editId = e.target.dataset.addrEdit;
    const delId = e.target.dataset.addrDel;
    const userObj = db.users.find((u) => u.id === currentUser.id);

    if (editId) {
      const addr = userObj.addresses.find((a) => a.id === Number(editId));
      if (!addr) return;
      document.getElementById('editAddressId').value = addr.id;
      document.getElementById('addrLabel').value = addr.label;
      document.getElementById('addrRecipient').value = addr.recipient;
      document.getElementById('addrFull').value = addr.full;
      document.getElementById('saveAddressBtn').textContent = 'Simpan Perubahan Alamat';
      document.getElementById('cancelEditAddrBtn').classList.remove('hidden');
    } else if (delId) {
      userObj.addresses = userObj.addresses.filter((a) => a.id !== Number(delId));
      saveDB();
      renderCustomerProfile();
      showToast('Alamat dihapus.');
    }
  });

  // ==================== 3. LOGIKA VERSI ADMIN ====================
  const initAdminView = () => {
    populateAdminSelects();
    renderAdminProducts();
    renderAdminMasterData();
    renderAdminOrders();
    renderAdminUsers();
  };

  document.querySelectorAll('[data-admin-nav]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.adminNav;
      document.querySelectorAll('[data-admin-nav]').forEach((b) => b.classList.remove('active'));
      document.querySelectorAll(`.nav-btn[data-admin-nav="${target}"]`).forEach((b) => b.classList.add('active'));

      document.getElementById('adminProductsTab').classList.toggle('hidden', target !== 'products');
      document.getElementById('adminMasterTab').classList.toggle('hidden', target !== 'master');
      document.getElementById('adminOrdersTab').classList.toggle('hidden', target !== 'orders');
      document.getElementById('adminUsersTab').classList.toggle('hidden', target !== 'users');

      if (target === 'products') {
        populateAdminSelects();
        renderAdminProducts();
      }
      if (target === 'master') renderAdminMasterData();
      if (target === 'orders') renderAdminOrders();
      if (target === 'users') renderAdminUsers();
    });
  });

  const populateAdminSelects = () => {
    document.getElementById('prodBrand').innerHTML = db.brands
      .map((b) => `<option value="${b}">${b}</option>`)
      .join('');
    document.getElementById('prodCategory').innerHTML = db.categories
      .map((c) => `<option value="${c}">${c}</option>`)
      .join('');
  };

  // Kelola Produk (Tambah, Ubah, Hapus)
  const renderAdminProducts = () => {
    const list = document.getElementById('adminProductList');
    list.innerHTML = db.products
      .map((p) => {
        const totalStock = p.variants.reduce((sum, v) => sum + v.stock, 0);
        return `
        <div class="admin-product-item">
          <div class="admin-prod-left">
            <img src="${p.image}" alt="${p.name}">
            <div>
              <h4>${p.name}</h4>
              <p><small>${p.category} • ${p.brand} • <strong>${formatRp(p.price)}</strong></small></p>
              <p><small>Total Stok: <strong>${totalStock} pasang</strong> (${p.variants.length} variasi)</small></p>
            </div>
          </div>
          <div class="btn-row">
            <button type="button" class="btn-primary sm" data-prod-stock="${p.id}">Stok & Variasi</button>
            <button type="button" class="btn-outline sm" data-prod-edit="${p.id}">Ubah</button>
            <button type="button" class="btn-danger sm" data-prod-del="${p.id}">Hapus</button>
          </div>
        </div>`;
      })
      .join('');
  };

  document.getElementById('adminProductForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const editId = document.getElementById('editProductId').value;
    const name = document.getElementById('prodName').value.trim();
    const brand = document.getElementById('prodBrand').value;
    const category = document.getElementById('prodCategory').value;
    const price = Number(document.getElementById('prodPrice').value);
    const desc = document.getElementById('prodDesc').value.trim();
    const file = document.getElementById('prodImageFile').files[0];

    const saveProductData = (imgUrl) => {
      if (editId) {
        const prod = db.products.find((p) => p.id === Number(editId));
        if (prod) {
          prod.name = name;
          prod.brand = brand;
          prod.category = category;
          prod.price = price;
          prod.desc = desc;
          if (imgUrl) prod.image = imgUrl;
        }
        showToast('Data produk berhasil diperbarui!');
      } else {
        db.products.unshift({
          id: Date.now(),
          name,
          brand,
          category,
          price,
          desc,
          image: imgUrl || createShoeSVG(name, '#E1D4C2', '#6E473B'),
          variants: [{ color: 'Default', size: 40, stock: 5 }]
        });
        showToast('Produk sepatu baru berhasil ditambahkan!');
      }

      saveDB();
      resetProductForm();
      renderAdminProducts();
    };

    if (file) {
      const reader = new FileReader();
      reader.onload = (ev) => saveProductData(ev.target.result);
      reader.readAsDataURL(file);
    } else {
      saveProductData(null);
    }
  });

  const resetProductForm = () => {
    document.getElementById('adminProductForm').reset();
    document.getElementById('editProductId').value = '';
    document.getElementById('productFormTitle').textContent = 'Tambah Produk Sepatu Baru';
    document.getElementById('saveProductBtn').textContent = 'Simpan Produk';
    document.getElementById('cancelEditProdBtn').classList.add('hidden');
  };

  document.getElementById('cancelEditProdBtn').addEventListener('click', resetProductForm);

  // Kelola Stok Variasi Sepatu (Modal Admin)
  const stockModal = document.getElementById('stockModal');
  const renderVariantList = (prodId) => {
    const prod = db.products.find((p) => p.id === prodId);
    if (!prod) return;
    document.getElementById('stockModalTitle').textContent = `Stok & Variasi — ${prod.name}`;
    const container = document.getElementById('variantListContainer');

    if (prod.variants.length === 0) {
      container.innerHTML = '<p>Belum ada variasi ukuran & warna.</p>';
      return;
    }

    container.innerHTML = prod.variants
      .map(
        (v, idx) => `
        <div class="variant-row">
          <span>Warna: <strong>${v.color}</strong> | Ukuran: <strong>${v.size}</strong></span>
          <div class="btn-row">
            <span>Stok: <strong>${v.stock}</strong></span>
            <button type="button" class="btn-danger sm" data-var-del="${idx}" data-prod-id="${prod.id}">Hapus</button>
          </div>
        </div>`
      )
      .join('');
  };

  document.getElementById('adminProductList').addEventListener('click', (e) => {
    const stockId = e.target.dataset.prodStock;
    const editId = e.target.dataset.prodEdit;
    const delId = e.target.dataset.prodDel;

    if (stockId) {
      const id = Number(stockId);
      document.getElementById('stockProductId').value = id;
      renderVariantList(id);
      stockModal.classList.remove('hidden');
    } else if (editId) {
      const prod = db.products.find((p) => p.id === Number(editId));
      if (!prod) return;
      document.getElementById('editProductId').value = prod.id;
      document.getElementById('prodName').value = prod.name;
      document.getElementById('prodBrand').value = prod.brand;
      document.getElementById('prodCategory').value = prod.category;
      document.getElementById('prodPrice').value = prod.price;
      document.getElementById('prodDesc').value = prod.desc;
      document.getElementById('productFormTitle').textContent = 'Ubah Informasi Produk';
      document.getElementById('saveProductBtn').textContent = 'Simpan Perubahan';
      document.getElementById('cancelEditProdBtn').classList.remove('hidden');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (delId) {
      db.products = db.products.filter((p) => p.id !== Number(delId));
      saveDB();
      renderAdminProducts();
      showToast('Produk dihapus.');
    }
  });

  document.getElementById('addVariantForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const prodId = Number(document.getElementById('stockProductId').value);
    const color = document.getElementById('varColor').value.trim();
    const size = Number(document.getElementById('varSize').value);
    const stock = Number(document.getElementById('varStock').value);

    const prod = db.products.find((p) => p.id === prodId);
    if (!prod) return;

    const existing = prod.variants.find((v) => v.color.toLowerCase() === color.toLowerCase() && v.size === size);
    if (existing) {
      existing.stock = stock;
    } else {
      prod.variants.push({ color, size, stock });
    }

    saveDB();
    e.target.reset();
    renderVariantList(prodId);
    renderAdminProducts();
    showToast('Stok variasi berhasil diperbarui!');
  });

  document.getElementById('variantListContainer').addEventListener('click', (e) => {
    const varIdx = e.target.dataset.varDel;
    const prodId = Number(e.target.dataset.prodId);
    if (varIdx === undefined) return;

    const prod = db.products.find((p) => p.id === prodId);
    if (prod) {
      prod.variants.splice(Number(varIdx), 1);
      saveDB();
      renderVariantList(prodId);
      renderAdminProducts();
      showToast('Variasi dihapus.');
    }
  });

  document.getElementById('closeStockModal').addEventListener('click', () => {
    stockModal.classList.add('hidden');
  });

  // ==================== KELOLA MEREK & KATEGORI (ADMIN) ====================
  const renderAdminMasterData = () => {
    document.getElementById('brandList').innerHTML = db.brands
      .map(
        (b, i) => `
        <li>
          <span>${b}</span>
          <div class="btn-row">
            <button type="button" class="btn-outline sm" data-brand-edit="${i}">Ubah</button>
            <button type="button" class="btn-danger sm" data-brand-del="${i}">Hapus</button>
          </div>
        </li>`
      )
      .join('');

    document.getElementById('categoryList').innerHTML = db.categories
      .map(
        (c, i) => `
        <li>
          <span>${c}</span>
          <div class="btn-row">
            <button type="button" class="btn-outline sm" data-cat-edit="${i}">Ubah</button>
            <button type="button" class="btn-danger sm" data-cat-del="${i}">Hapus</button>
          </div>
        </li>`
      )
      .join('');
  };

  document.getElementById('brandForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const editIdx = document.getElementById('editBrandIndex').value;
    const val = document.getElementById('brandNameInput').value.trim();
    if (!val) return;

    if (editIdx !== '') {
      db.brands[Number(editIdx)] = val;
      document.getElementById('editBrandIndex').value = '';
    } else {
      db.brands.push(val);
    }
    saveDB();
    e.target.reset();
    renderAdminMasterData();
    showToast('Data merek disimpan.');
  });

  document.getElementById('brandList').addEventListener('click', (e) => {
    const editIdx = e.target.dataset.brandEdit;
    const delIdx = e.target.dataset.brandDel;
    if (editIdx !== undefined) {
      document.getElementById('editBrandIndex').value = editIdx;
      document.getElementById('brandNameInput').value = db.brands[Number(editIdx)];
    } else if (delIdx !== undefined) {
      db.brands.splice(Number(delIdx), 1);
      saveDB();
      renderAdminMasterData();
      showToast('Merek dihapus.');
    }
  });

  document.getElementById('categoryForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const editIdx = document.getElementById('editCategoryIndex').value;
    const val = document.getElementById('categoryNameInput').value.trim();
    if (!val) return;

    if (editIdx !== '') {
      db.categories[Number(editIdx)] = val;
      document.getElementById('editCategoryIndex').value = '';
    } else {
      db.categories.push(val);
    }
    saveDB();
    e.target.reset();
    renderAdminMasterData();
    showToast('Data kategori disimpan.');
  });

  document.getElementById('categoryList').addEventListener('click', (e) => {
    const editIdx = e.target.dataset.catEdit;
    const delIdx = e.target.dataset.catDel;
    if (editIdx !== undefined) {
      document.getElementById('editCategoryIndex').value = editIdx;
      document.getElementById('categoryNameInput').value = db.categories[Number(editIdx)];
    } else if (delIdx !== undefined) {
      db.categories.splice(Number(delIdx), 1);
      saveDB();
      renderAdminMasterData();
      showToast('Kategori dihapus.');
    }
  });

  // ==================== KELOLA PESANAN & PEMBAYARAN (ADMIN) ====================
  const renderAdminOrders = () => {
    const list = document.getElementById('adminOrdersList');
    if (db.orders.length === 0) {
      list.innerHTML = '<div class="card-panel"><p>Belum ada pesanan masuk dari pelanggan.</p></div>';
      return;
    }

    list.innerHTML = db.orders
      .map(
        (o) => `
        <div class="order-card">
          <div class="order-header">
            <div>
              <strong>${o.id}</strong> — Pelanggan: <strong>${o.userName}</strong> (${o.userEmail})
              <br><small>${o.date}</small>
            </div>
            <div>
              <span class="status-badge ${o.paymentStatus.includes('Lunas') ? 'paid' : ''}">${o.paymentStatus}</span>
              <span class="status-badge">Status: ${o.orderStatus}</span>
            </div>
          </div>

          <div class="order-items-mini">
            ${o.items
              .map((i) => `<div>• ${i.name} (${i.color}, Size ${i.size}) x${i.qty} — ${formatRp(i.price * i.qty)}</div>`)
              .join('')}
          </div>

          <p><strong>Total Tagihan:</strong> ${formatRp(o.totalAmount)} (${o.paymentMethod})</p>
          <p><strong>Pengiriman (${o.shippingMethod}):</strong> ${o.shippingInfo}</p>

          ${
            o.paymentProof
              ? `<div class="mt-10"><strong>Bukti Transfer:</strong><img src="${o.paymentProof}" class="proof-thumb" alt="Bukti Transfer"></div>`
              : ''
          }

          <div class="btn-row mt-10">
            ${
              !o.paymentStatus.includes('Lunas')
                ? `<button type="button" class="btn-primary sm" data-confirm-pay="${o.id}">Konfirmasi Pembayaran Lunas</button>`
                : ''
            }
            <select class="sm" style="width:auto;" data-update-status="${o.id}">
              <option value="Diproses" ${o.orderStatus === 'Diproses' ? 'selected' : ''}>Status: Diproses</option>
              <option value="Siap Diambil" ${o.orderStatus === 'Siap Diambil' ? 'selected' : ''}>Status: Siap Diambil</option>
              <option value="Sedang Dikirim" ${o.orderStatus === 'Sedang Dikirim' ? 'selected' : ''}>Status: Sedang Dikirim</option>
              <option value="Selesai" ${o.orderStatus === 'Selesai' ? 'selected' : ''}>Status: Selesai</option>
            </select>
          </div>
        </div>`
      )
      .join('');
  };

  document.getElementById('adminOrdersList').addEventListener('click', (e) => {
    const orderId = e.target.dataset.confirmPay;
    if (!orderId) return;
    const order = db.orders.find((o) => o.id === orderId);
    if (order) {
      order.paymentStatus = 'Lunas (Dikonfirmasi Admin)';
      saveDB();
      renderAdminOrders();
      showToast(`Pembayaran pesanan ${orderId} dikonfirmasi!`);
    }
  });

  document.getElementById('adminOrdersList').addEventListener('change', (e) => {
    const orderId = e.target.dataset.updateStatus;
    if (!orderId) return;
    const order = db.orders.find((o) => o.id === orderId);
    if (order) {
      order.orderStatus = e.target.value;
      saveDB();
      renderAdminOrders();
      showToast(`Status pesanan ${orderId} diubah menjadi "${order.orderStatus}".`);
    }
  });

  // ==================== KELOLA PENGGUNA / PELANGGAN (ADMIN) ====================
  const renderAdminUsers = () => {
    const customers = db.users.filter((u) => u.role === 'customer');
    const grid = document.getElementById('adminUsersList');

    grid.innerHTML = customers
      .map(
        (c) => `
        <div class="user-card">
          <h3>${c.name}</h3>
          <p><strong>Email:</strong> ${c.email}</p>
          <p><strong>No. HP:</strong> ${c.phone}</p>
          <hr class="divider">
          <p><strong>Alamat Tersimpan (${c.addresses.length}):</strong></p>
          ${
            c.addresses.length > 0
              ? c.addresses.map((a) => `<p><small>• <strong>${a.label}:</strong> ${a.full}</small></p>`).join('')
              : '<p><small>Belum menambahkan alamat.</small></p>'
          }
        </div>`
      )
      .join('');
  };

  // Jalankan Routing Awal
  routeApp();
});