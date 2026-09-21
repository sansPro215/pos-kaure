/**
 * WARUNG KAURE POS & MANAGEMENT SYSTEM
 * Fast POS Logic & Cart Management
 */

let cart = [];
let discount = { type: 'NONE', value: 0 };
let currentTotal = 0;

document.addEventListener('DOMContentLoaded', () => {
    initCategoryFilter();
    initSearchFilter();
    initProductClicks();
    initQuickCash();
    initPaymentMethodTabs();
    initCheckoutForm();
    initDiscountModal();
    initHoldCart();
    initOwnerCashierPicker();
    initPaymentProofPhoto();

    // Render initial empty cart
    renderCart();
});

// 1. Category Filtering
function initCategoryFilter() {
    const pills = document.querySelectorAll('.pos-cat-pill');
    const cards = document.querySelectorAll('.pos-product-item');

    pills.forEach(pill => {
        pill.addEventListener('click', function() {
            pills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');

            const catId = this.getAttribute('data-cat-id');

            cards.forEach(card => {
                if (catId === 'all' || card.getAttribute('data-category-id') === catId) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
}

// 2. Real-Time Search Filter
function initSearchFilter() {
    const searchInput = document.getElementById('posSearchInput');
    const cards = document.querySelectorAll('.pos-product-item');

    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        const activePill = document.querySelector('.pos-cat-pill.active');
        const activeCatId = activePill ? activePill.getAttribute('data-cat-id') : 'all';

        cards.forEach(card => {
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            const sku = (card.getAttribute('data-sku') || '').toLowerCase();
            const catId = card.getAttribute('data-category-id');

            const matchCat = (activeCatId === 'all' || catId === activeCatId);
            const matchQuery = (name.includes(query) || sku.includes(query));

            if (matchCat && matchQuery) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
}

// 3. Product Card / Table Row Clicks
function initProductClicks() {
    document.querySelectorAll('.pos-product-item').forEach(card => {
        card.addEventListener('click', function() {
            if (this.classList.contains('out-of-stock')) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stok Habis',
                        text: 'Produk ini sedang tidak tersedia atau stok bahan baku habis.',
                        confirmButtonColor: '#6F4E37'
                    });
                } else {
                    alert('Stok produk ini sedang habis.');
                }
                return;
            }

            const cardInner = this.querySelector('.product-card');
            const rawId = this.getAttribute('data-id') || (cardInner ? cardInner.getAttribute('data-id') : null);
            const rawName = this.getAttribute('data-name') || (cardInner ? cardInner.getAttribute('data-name') : '') || 'Produk';
            const rawPrice = this.getAttribute('data-price') || (cardInner ? cardInner.getAttribute('data-price') : null);

            const productId = parseInt(rawId || 0);
            const name = rawName;
            const price = parseFloat(rawPrice || 0);

            if (productId > 0 && !isNaN(productId)) {
                addToCart(productId, name, price);
            }
        });
    });
}

// 4. Cart Management
function addToCart(productId, name, price) {
    if (!productId || isNaN(productId)) {
        console.warn('addToCart called with invalid productId:', productId);
        return;
    }
    const safePrice = isNaN(price) ? 0 : parseFloat(price);

    const existingIndex = cart.findIndex(item => item.product_id === productId);

    if (existingIndex > -1) {
        cart[existingIndex].qty += 1;
    } else {
        cart.push({
            product_id: productId,
            name: name,
            price: safePrice,
            qty: 1
        });
    }

    renderCart();
}

function updateQty(productId, change) {
    const item = cart.find(i => i.product_id === productId);
    if (!item) return;

    const newQty = item.qty + change;

    if (newQty <= 0) {
        removeFromCart(productId);
    } else {
        item.qty = newQty;
        renderCart();
    }
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.product_id !== productId);
    renderCart();
}

function clearCart() {
    cart = [];
    discount = { type: 'NONE', value: 0 };
    renderCart();
}

// 5. Render Cart
function renderCart() {
    let subtotal = 0;
    let totalItems = 0;

    cart.forEach(item => {
        subtotal += item.price * item.qty;
        totalItems += item.qty;
    });

    let discountAmount = 0;
    if (discount.type === 'PERCENT') {
        discountAmount = (subtotal * discount.value) / 100;
    } else if (discount.type === 'FIXED') {
        discountAmount = discount.value;
    }
    discountAmount = Math.min(subtotal, Math.max(0, discountAmount));
    const grandTotal = Math.max(0, subtotal - discountAmount);
    currentTotal = grandTotal;

    const formatRp = (num) => 'Rp' + new Intl.NumberFormat('id-ID').format(num);

    // Update Mobile Sticky Bar
    const mobileCountElem = document.getElementById('mobileCartCount');
    const mobileTotalElem = document.getElementById('mobileCartTotal');
    const mobileBar = document.getElementById('posMobileBar');

    if (mobileCountElem) mobileCountElem.innerText = `${totalItems} Item`;
    if (mobileTotalElem) mobileTotalElem.innerText = formatRp(grandTotal);

    if (mobileBar) {
        if (totalItems > 0) {
            mobileBar.classList.remove('d-none');
        } else {
            mobileBar.classList.add('d-none');
        }
    }

    // Template for item list matching standard Warung Kaure POS
    const generateHtml = (isMobile = false) => {
        if (cart.length === 0) {
            return `<div class="text-center py-5 text-muted small">
                <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                Keranjang masih kosong.<br>Silakan pilih produk dari katalog.
            </div>`;
        }

        return cart.map(item => `
            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                <div class="pe-2 flex-grow-1" style="min-width: 0;">
                    <div class="fw-semibold text-truncate small" title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</div>
                    <div class="text-muted" style="font-size: 0.8rem;">
                        ${formatRp(item.price)} × ${item.qty} = <span class="fw-semibold text-wk-primary">${formatRp(item.price * item.qty)}</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button class="stepper-btn" onclick="updateQty(${item.product_id}, -1)">−</button>
                    <span class="fw-bold px-2 text-center" style="min-width: 28px;">${item.qty}</span>
                    <button class="stepper-btn" onclick="updateQty(${item.product_id}, 1)">+</button>
                    <button class="btn btn-sm text-danger border-0 p-1 ms-1" onclick="removeFromCart(${item.product_id})" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    };

    // Render list into Desktop Panel & Mobile Offcanvas
    const desktopList = document.getElementById('desktopCartItems');
    const mobileList = document.getElementById('mobileCartItems');

    if (desktopList) desktopList.innerHTML = generateHtml(false);
    if (mobileList) mobileList.innerHTML = generateHtml(true);

    // Update Totals Display
    const updateDisplays = (prefix) => {
        const subElem = document.getElementById(prefix + 'Subtotal');
        const discElem = document.getElementById(prefix + 'Discount');
        const grandElem = document.getElementById(prefix + 'GrandTotal');
        const payBtn = document.getElementById(prefix + 'PayBtn');
        const holdBtn = document.getElementById(prefix + 'HoldBtn');

        if (subElem) subElem.innerText = formatRp(subtotal);
        if (discElem) {
            if (discount.type === 'PERCENT' && discount.value > 0) {
                discElem.innerText = `${discount.value}%`;
            } else if (discountAmount > 0) {
                discElem.innerText = `-${formatRp(discountAmount)}`;
            } else {
                discElem.innerText = '0%';
            }
        }
        if (grandElem) grandElem.innerText = formatRp(grandTotal);

        if (payBtn) {
            payBtn.disabled = (totalItems === 0);
            payBtn.innerHTML = `BAYAR &nbsp; ${formatRp(grandTotal)}`;
        }
        if (holdBtn) {
            holdBtn.disabled = (totalItems === 0);
        }
    };

    updateDisplays('desktop');
    updateDisplays('offcanvas');
}

// 6. Discount Modal
function initDiscountModal() {
    const applyBtn = document.getElementById('applyDiscountBtn');
    const discModal = document.getElementById('discountModal');

    if (!applyBtn || !discModal) return;

    applyBtn.addEventListener('click', () => {
        const typeElem = document.querySelector('input[name="discount_type_radio"]:checked');
        const valElem = document.getElementById('discountInputVal');

        const type = typeElem ? typeElem.value : 'NONE';
        const val = parseFloat(valElem.value || '0');

        if (val < 0) {
            alert('Nilai diskon tidak boleh negatif.');
            return;
        }

        discount = { type: type, value: val };
        renderCart();

        const bsModal = bootstrap.Modal.getInstance(discModal);
        if (bsModal) bsModal.hide();
    });

    const removeDiscBtn = document.getElementById('removeDiscountBtn');
    if (removeDiscBtn) {
        removeDiscBtn.addEventListener('click', () => {
            discount = { type: 'NONE', value: 0 };
            renderCart();
            const bsModal = bootstrap.Modal.getInstance(discModal);
            if (bsModal) bsModal.hide();
        });
    }
}

// Helper: Format number with thousand dots (e.g. 50.000)
function formatRupiahThousand(numStr) {
    if (numStr === null || numStr === undefined || numStr === '') return '';
    const clean = numStr.toString().replace(/[^0-9]/g, '');
    if (!clean) return '';
    return new Intl.NumberFormat('id-ID').format(clean);
}

// 7. Payment Tabs & Quick Cash
function initPaymentMethodTabs() {
    const methodInputs = document.querySelectorAll('input[name="pos_payment_method"]');
    const cashSection = document.getElementById('cashPaymentSection');
    const cashlessSection = document.getElementById('cashlessPaymentSection');
    const providerRow = document.getElementById('ewalletProviderRow');

    methodInputs.forEach(input => {
        input.addEventListener('change', function() {
            const method = this.value;
            if (method === 'CASH') {
                if (cashSection) cashSection.classList.remove('d-none');
                if (cashlessSection) cashlessSection.classList.add('d-none');
                if (providerRow) providerRow.classList.add('d-none');
            } else {
                if (cashSection) cashSection.classList.add('d-none');
                if (cashlessSection) cashlessSection.classList.remove('d-none');

                if (providerRow) {
                    if (method === 'EWALLET') {
                        providerRow.classList.remove('d-none');
                    } else {
                        providerRow.classList.add('d-none');
                    }
                }
            }
        });
    });
}

function initQuickCash() {
    const receivedInput = document.getElementById('receivedCashInput');
    const changeDisplay = document.getElementById('changeAmountDisplay');
    const quickBtns = document.querySelectorAll('.btn-quick-cash');

    const updateQuickBtnActive = (val) => {
        const numVal = typeof val === 'number' ? val : parseInt(val.toString().replace(/[^0-9]/g, '') || '0');
        quickBtns.forEach(btn => {
            btn.classList.remove('btn-wk-primary', 'text-white', 'active');
            btn.classList.add('btn-outline-secondary');

            const btnVal = btn.getAttribute('data-val');
            if (btnVal === 'exact' && numVal === currentTotal && currentTotal > 0) {
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-wk-primary', 'text-white', 'active');
            } else if (parseInt(btnVal) === numVal && numVal > 0) {
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-wk-primary', 'text-white', 'active');
            }
        });
    };

    const updateChange = () => {
        if (!receivedInput || !changeDisplay) return;
        const rawDigits = receivedInput.value.replace(/[^0-9]/g, '');
        const received = parseFloat(rawDigits || '0');
        const change = received - currentTotal;

        if (change >= 0) {
            changeDisplay.className = 'fw-bold text-success fs-5';
            changeDisplay.innerText = 'Rp' + new Intl.NumberFormat('id-ID').format(change);
        } else {
            changeDisplay.className = 'fw-bold text-danger fs-5';
            changeDisplay.innerText = 'Kurang Rp' + new Intl.NumberFormat('id-ID').format(Math.abs(change));
        }

        updateQuickBtnActive(received);
    };

    if (receivedInput) {
        receivedInput.addEventListener('input', function() {
            const rawDigits = this.value.replace(/[^0-9]/g, '');
            if (rawDigits) {
                this.value = formatRupiahThousand(rawDigits);
            } else {
                this.value = '';
            }
            updateChange();
        });
    }

    quickBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const val = this.getAttribute('data-val');
            let targetNominal = 0;
            if (val === 'exact') {
                targetNominal = currentTotal;
            } else {
                targetNominal = parseInt(val);
            }
            receivedInput.value = formatRupiahThousand(targetNominal);
            updateChange();
        });
    });
}

// 7b. Payment Proof Photo for Cashless Transactions
function initPaymentProofPhoto() {
    const proofInput = document.getElementById('posPaymentProofInput');
    const placeholder = document.getElementById('proofUploadPlaceholder');
    const previewBox = document.getElementById('proofUploadPreviewBox');
    const previewImg = document.getElementById('proofUploadPreviewImg');

    if (!proofInput) return;

    proofInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ukuran File Terlalu Besar',
                    text: 'Ukuran foto maksimal 5 MB.',
                    confirmButtonColor: '#6F4E37'
                });
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                if (previewImg) previewImg.src = e.target.result;
                if (placeholder) placeholder.classList.add('d-none');
                if (previewBox) previewBox.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
    });

    window.clearPaymentProofPhoto = function() {
        if (proofInput) proofInput.value = '';
        if (previewImg) previewImg.src = '';
        if (previewBox) previewBox.classList.add('d-none');
        if (placeholder) placeholder.classList.remove('d-none');
    };
}

// Open Checkout Modal
window.openCheckoutModal = function() {
    if (cart.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Keranjang Kosong',
            text: 'Silakan pilih produk terlebih dahulu.',
            confirmButtonColor: '#6F4E37'
        });
        return;
    }

    // Close mobile offcanvas if open
    const offcanvasEl = document.getElementById('offcanvasCart');
    if (offcanvasEl) {
        const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
        if (offcanvas) offcanvas.hide();
    }

    // Reset payment method to CASH
    const cashRadio = document.getElementById('methodCash');
    if (cashRadio) {
        cashRadio.checked = true;
        const cashSection = document.getElementById('cashPaymentSection');
        const cashlessSection = document.getElementById('cashlessPaymentSection');
        const providerRow = document.getElementById('ewalletProviderRow');
        if (cashSection) cashSection.classList.remove('d-none');
        if (cashlessSection) cashlessSection.classList.add('d-none');
        if (providerRow) providerRow.classList.add('d-none');
    }

    // Reset reference number & payment proof photo
    const refInput = document.getElementById('referenceNumberInput');
    if (refInput) refInput.value = '';
    if (window.clearPaymentProofPhoto) window.clearPaymentProofPhoto();

    const modalTotal = document.getElementById('checkoutModalTotal');
    const receivedInput = document.getElementById('receivedCashInput');
    const changeDisplay = document.getElementById('changeAmountDisplay');

    if (modalTotal) modalTotal.innerText = 'Rp' + new Intl.NumberFormat('id-ID').format(currentTotal);
    if (receivedInput) {
        receivedInput.value = formatRupiahThousand(currentTotal); // default to uang pas
    }
    if (changeDisplay) {
        changeDisplay.className = 'fw-bold text-success fs-5';
        changeDisplay.innerText = 'Rp0';
    }

    // Highlight "Uang Pas" button by default
    const exactBtn = document.querySelector('.btn-quick-cash[data-val="exact"]');
    document.querySelectorAll('.btn-quick-cash').forEach(b => {
        b.classList.remove('btn-wk-primary', 'text-white', 'active');
        b.classList.add('btn-outline-secondary');
    });
    if (exactBtn) {
        exactBtn.classList.remove('btn-outline-secondary');
        exactBtn.classList.add('btn-wk-primary', 'text-white', 'active');
    }

    const modalEl = document.getElementById('checkoutModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
};

// 8. Submit Checkout Form
function initCheckoutForm() {
    const checkoutBtn = document.getElementById('submitCheckoutBtn');
    if (!checkoutBtn) return;

    checkoutBtn.addEventListener('click', function() {
        const methodElem = document.querySelector('input[name="pos_payment_method"]:checked');
        const method = methodElem ? methodElem.value : 'CASH';

        const receivedInput = document.getElementById('receivedCashInput');
        const rawReceived = receivedInput ? receivedInput.value.replace(/[^0-9]/g, '') : '';
        const received = parseFloat(rawReceived || (method === 'CASH' ? '0' : currentTotal.toString()));

        if (method === 'CASH' && received < currentTotal) {
            Swal.fire({
                icon: 'error',
                title: 'Uang Kurang',
                text: 'Nominal uang yang diterima kurang dari total belanja.',
                confirmButtonColor: '#6F4E37'
            });
            return;
        }

        const providerElem = document.querySelector('input[name="ewallet_provider"]:checked');
        const provider = (method === 'EWALLET' && providerElem) ? providerElem.value : null;
        const refNumber = document.getElementById('referenceNumberInput')?.value || '';

        // Prepare Payload
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value);
        formData.append('cart_json', JSON.stringify(cart));
        formData.append('payment_method', method);
        formData.append('payment_provider', provider || '');
        formData.append('received_amount', method === 'CASH' ? received : currentTotal);
        formData.append('reference_number', refNumber);
        formData.append('discount_type', discount.type);
        formData.append('discount_value', discount.value);

        // Append selected cashier if Owner cashier picker is present
        const cashierVal = getSelectedOwnerCashierId();
        if (cashierVal) {
            formData.append('selected_cashier_id', cashierVal);
        }

        // Append payment proof image if uploaded
        const proofInput = document.getElementById('posPaymentProofInput');
        if (method !== 'CASH' && proofInput && proofInput.files && proofInput.files[0]) {
            formData.append('payment_proof', proofInput.files[0]);
        }

        // UI Loading state
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Memproses Transaksi...`;

        fetch(window.posCheckoutUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            checkoutBtn.disabled = false;
            checkoutBtn.innerHTML = `<i class="bi bi-check2-circle me-1"></i> Selesaikan Pembayaran`;

            if (!data.status) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Memproses Transaksi',
                    text: data.message || 'Terjadi kesalahan sistem.',
                    confirmButtonColor: '#6F4E37'
                });
                return;
            }

            // Hide Modal
            const modalEl = document.getElementById('checkoutModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }

            // Transaction Success Popup
            const changeText = data.change_amount > 0 
                ? `<div class="p-3 bg-success-subtle rounded mt-3">
                     <span class="text-muted d-block small">Kembalian:</span>
                     <strong class="fs-4 text-success">Rp${new Intl.NumberFormat('id-ID').format(data.change_amount)}</strong>
                   </div>`
                : '';

            Swal.fire({
                icon: 'success',
                title: 'Transaksi Berhasil!',
                html: `
                    <div class="mb-2">Nomor: <strong>${data.transaction_code}</strong></div>
                    <div class="mb-2">Total: <strong>Rp${new Intl.NumberFormat('id-ID').format(data.grand_total)}</strong></div>
                    ${changeText}
                `,
                showCancelButton: true,
                confirmButtonColor: '#6F4E37',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-printer me-1"></i> Cetak Struk',
                cancelButtonText: '<i class="bi bi-plus-circle me-1"></i> Transaksi Baru',
                allowOutsideClick: false
            }).then((result) => {
                clearCart();
                if (result.isConfirmed) {
                    window.open(data.receipt_url, '_blank', 'width=420,height=600');
                }
            });
        })
        .catch(err => {
            checkoutBtn.disabled = false;
            checkoutBtn.innerHTML = `<i class="bi bi-check2-circle me-1"></i> Selesaikan Pembayaran`;
            Swal.fire({
                icon: 'error',
                title: 'Error Koneksi',
                text: 'Gagal terhubung ke server. Pastikan Apache & MySQL berjalan.',
                confirmButtonColor: '#6F4E37'
            });
        });
    });
}

// 9. Hold Cart
function initHoldCart() {
    window.holdCurrentCart = function() {
        if (cart.length === 0) return;

        Swal.fire({
            title: 'Simpan Pesanan (Hold)',
            input: 'text',
            inputLabel: 'Catatan Pelanggan (Meja / Nama):',
            inputPlaceholder: 'Contoh: Meja 4 / Bpk Joko',
            showCancelButton: true,
            confirmButtonColor: '#6F4E37',
            confirmButtonText: 'Simpan Hold',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const note = result.value || '';
                const formData = new FormData();
                formData.append('_token', document.querySelector('input[name="_token"]')?.value);
                formData.append('cart_json', JSON.stringify(cart));
                formData.append('hold_note', note);

                const cashierVal = getSelectedOwnerCashierId();
                if (cashierVal) {
                    formData.append('selected_cashier_id', cashierVal);
                }

                fetch(window.posHoldUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Pesanan Tersimpan',
                            text: `Kode pesanan hold: ${data.code}`,
                            confirmButtonColor: '#6F4E37'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal Hold', text: data.message, confirmButtonColor: '#6F4E37' });
                    }
                });
            }
        });
    };

    window.resumeOrder = function(transId) {
        fetch(window.posResumeUrl + '/' + transId, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status) {
                cart = data.cart.map(i => ({
                    product_id: i.product_id,
                    name: i.name,
                    price: i.price,
                    qty: i.qty
                }));
                renderCart();

                const heldModal = document.getElementById('heldOrdersModal');
                if (heldModal) {
                    const modal = bootstrap.Modal.getInstance(heldModal);
                    if (modal) modal.hide();
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Pesanan Dipulihkan',
                    text: 'Item berhasil dimuat kembali ke keranjang.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    };
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper: Get currently selected owner cashier ID
function getSelectedOwnerCashierId() {
    const desktopPicker = document.getElementById('posCartCashierSelect');
    const mobilePicker = document.getElementById('posMobileCashierSelect');
    if (desktopPicker && desktopPicker.offsetParent !== null && desktopPicker.value) {
        return desktopPicker.value;
    }
    if (mobilePicker && mobilePicker.offsetParent !== null && mobilePicker.value) {
        return mobilePicker.value;
    }
    return desktopPicker?.value || mobilePicker?.value || window.selectedPosCashierId || localStorage.getItem('pos_selected_cashier_id') || null;
}

// 10. Owner Cashier Picker (Cart Only)
function initOwnerCashierPicker() {
    const pickers = document.querySelectorAll('.owner-cashier-picker');
    if (pickers.length === 0) return;

    // Default to the first cashier option
    window.selectedPosCashierId = pickers[0].value;

    // Restore previously selected cashier from localStorage if option still exists
    const saved = localStorage.getItem('pos_selected_cashier_id');
    if (saved) {
        let matched = false;
        pickers.forEach(p => {
            if (p.querySelector(`option[value="${saved}"]`)) {
                p.value = saved;
                matched = true;
            }
        });
        if (matched) {
            window.selectedPosCashierId = saved;
        }
    }

    pickers.forEach(picker => {
        picker.addEventListener('change', function() {
            const val = this.value;
            window.selectedPosCashierId = val;
            localStorage.setItem('pos_selected_cashier_id', val);
            pickers.forEach(other => {
                if (other !== picker) other.value = val;
            });
        });
    });
}
