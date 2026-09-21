<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">Pengaturan Sistem & Kedai</h4>
            <p class="text-muted small mb-0">Atur profil kedai, format struk, dan parameter penggajian</p>
        </div>
    </div>

    <div class="wk-card p-4 shadow-sm">
        <form action="<?= url('/settings') ?>" method="POST">
            <?= csrf_field() ?>

            <h6 class="fw-bold mb-3 text-wk-primary"><i class="bi bi-shop me-2"></i>Identitas Kedai & Struk</h6>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Nama Kedai / Usaha (Header & Struk) <span class="text-danger">*</span></label>
                    <input type="text" name="shop_name" class="form-control" value="<?= e($settings['shop_name']) ?>" placeholder="contoh: Warung Kaure" required>
                    <div class="form-text small">Nama ini otomatis sinkron dengan nama header atas dan kop struk kasir.</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Nomor Telepon / Kontak</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($settings['phone']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small">Alamat Kedai</label>
                    <textarea name="address" class="form-control" rows="2"><?= e($settings['address']) ?></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Lebar Kertas Printer Struk</label>
                    <select name="receipt_width" class="form-select">
                        <option value="auto" <?= $settings['receipt_width'] === 'auto' ? 'selected' : '' ?>>Auto / Standard Browser Print</option>
                        <option value="58" <?= $settings['receipt_width'] === '58' ? 'selected' : '' ?>>58 mm (Thermal Printer Kecil)</option>
                        <option value="80" <?= $settings['receipt_width'] === '80' ? 'selected' : '' ?>>80 mm (Thermal Printer Besar)</option>
                    </select>
                    <div class="form-text small">
                        Format CSS cetak disesuaikan riil dengan roll kertas thermal (@page size).
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Batas Maksimal Diskon Kasir (%)</label>
                    <input type="number" step="0.5" name="cashier_max_discount_percent" class="form-control" value="<?= (float)$settings['cashier_max_discount_percent'] ?>" min="0" max="100">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small">Catatan Footer Struk</label>
                    <textarea name="receipt_footer" class="form-control" rows="2"><?= e($settings['receipt_footer']) ?></textarea>
                    <div class="form-text small">Teks yang dicetak di bagian paling bawah struk belanja pelanggan.</div>
                </div>
            </div>

            <h6 class="fw-bold mb-3 text-wk-primary border-top pt-3"><i class="bi bi-layout-text-window-reverse me-2"></i>Tampilan & Layout Modul POS</h6>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label class="form-label fw-semibold small d-block">Pilihan Layout Tampilan Produk di Kasir</label>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="border rounded-3 p-3 d-flex align-items-start gap-3 w-100 h-100 cursor-pointer <?= ($settings['pos_layout'] ?? 'grid_large') === 'grid_large' ? 'border-primary bg-primary-subtle shadow-sm' : 'bg-wk-surface' ?>" style="cursor: pointer;">
                                <input type="radio" name="pos_layout" value="grid_large" <?= ($settings['pos_layout'] ?? 'grid_large') === 'grid_large' ? 'checked' : '' ?> class="form-check-input mt-1">
                                <div>
                                    <div class="fw-bold text-dark fs-6 mb-1"><i class="bi bi-grid-3x3-gap text-primary me-2"></i>1. Grid Produk Besar (Default)</div>
                                    <div class="text-muted small">Kartu visual produk berukuran besar dengan foto menu yang jelas & scroll horizontal pill kategori. Sangat nyaman untuk layar sentuh (touchscreen) dan kasir kafe/resto visual.</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="border rounded-3 p-3 d-flex align-items-start gap-3 w-100 h-100 cursor-pointer <?= ($settings['pos_layout'] ?? '') === 'table_list' ? 'border-primary bg-primary-subtle shadow-sm' : 'bg-wk-surface' ?>" style="cursor: pointer;">
                                <input type="radio" name="pos_layout" value="table_list" <?= ($settings['pos_layout'] ?? '') === 'table_list' ? 'checked' : '' ?> class="form-check-input mt-1">
                                <div>
                                    <div class="fw-bold text-dark fs-6 mb-1"><i class="bi bi-table text-success me-2"></i>2. Tabel Produk Memanjang (Kolom ke Samping)</div>
                                    <div class="text-muted small">Tabel produk dengan informasi terdistribusi ke samping (Foto, SKU, Nama Barang, Kategori, Harga Satuan, dan Tombol Tambah). Tampilan POS dan keranjang tetap rapi seperti standar Warung Kaure.</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mb-3 text-wk-primary border-top pt-3"><i class="bi bi-palette me-2"></i>Nuansa Warna & Tema Aplikasi (Color Palette)</h6>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <p class="text-muted small mb-3">Pilih nuansa warna tema sistem untuk disesuaikan dengan identitas kedai Anda, atau tentukan warna kustom sendiri.</p>
                    <div class="row g-2">
                        <?php 
                        $palettes = get_theme_palettes();
                        $currentTheme = $settings['theme_color'] ?? 'coffee';
                        $isCustom = !isset($palettes[$currentTheme]) && preg_match('/^#[0-9a-fA-F]{6}$/', $currentTheme);
                        foreach ($palettes as $pId => $pData): 
                            $isSelected = ($currentTheme === $pId);
                        ?>
                            <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                                <label class="border rounded p-3 d-flex flex-column gap-2 w-100 h-100 cursor-pointer theme-palette-card <?= $isSelected ? 'border-primary bg-primary-subtle' : 'bg-wk-surface' ?>" style="cursor: pointer;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="radio" name="theme_color" value="<?= e($pId) ?>" <?= $isSelected ? 'checked' : '' ?> class="form-check-input mt-0 theme-radio-input" 
                                                   data-primary="<?= $pData['primary'] ?>" 
                                                   data-primary-rgb="<?= $pData['primary_rgb'] ?>" 
                                                   data-primary-dark="<?= $pData['primary_dark'] ?>" 
                                                   data-primary-light="<?= $pData['primary_light'] ?>"
                                                   data-bg="<?= $pData['bg'] ?>"
                                                   data-surface="<?= $pData['surface'] ?>"
                                                   data-surface-elevated="<?= $pData['surface_elevated'] ?>"
                                                   data-border="<?= $pData['border'] ?>"
                                                   data-text="<?= $pData['text'] ?>"
                                                   data-text-muted="<?= $pData['text_muted'] ?>">
                                            <span class="fw-bold small"><?= e($pData['name']) ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 my-1">
                                        <div class="rounded-circle shadow-sm" style="width: 22px; height: 22px; background-color: <?= $pData['primary'] ?>;" title="Primary: <?= $pData['primary'] ?>"></div>
                                        <div class="rounded-circle shadow-sm" style="width: 18px; height: 18px; background-color: <?= $pData['primary_dark'] ?>;" title="Dark: <?= $pData['primary_dark'] ?>"></div>
                                        <div class="rounded-circle border shadow-sm" style="width: 18px; height: 18px; background-color: <?= $pData['primary_light'] ?>;" title="Light: <?= $pData['primary_light'] ?>"></div>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.73rem;"><?= e($pData['description']) ?></div>
                                </label>
                            </div>
                        <?php endforeach; ?>

                        <!-- Custom Color Option -->
                        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                            <label class="border rounded p-3 d-flex flex-column gap-2 w-100 h-100 cursor-pointer theme-palette-card <?= $isCustom ? 'border-primary bg-primary-subtle' : 'bg-wk-surface' ?>" style="cursor: pointer;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="radio" name="theme_color" value="custom" <?= $isCustom ? 'checked' : '' ?> id="themeCustomRadio" class="form-check-input mt-0 theme-radio-input">
                                        <span class="fw-bold small"><i class="bi bi-eyedropper me-1"></i> Kustom Mandiri</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <input type="color" id="customColorPicker" name="custom_theme_hex" value="<?= $isCustom ? e($currentTheme) : '#6F4E37' ?>" class="form-control form-control-color p-0 border-0" style="width: 32px; height: 32px; cursor: pointer;" title="Pilih warna kustom">
                                    <span class="small font-monospace text-muted" id="customHexLabel"><?= $isCustom ? strtoupper(e($currentTheme)) : '#6F4E37' ?></span>
                                </div>
                                <div class="text-muted" style="font-size: 0.73rem;">Bebas tentukan warna primer identitas kedai Anda</div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mb-3 text-wk-primary border-top pt-3"><i class="bi bi-clock me-2"></i>Parameter Jam Kerja & Sistem Gaji (Payroll)</h6>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label fw-semibold small">Jam Kerja Reguler / Hari</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="regular_hours_per_day" class="form-control" value="<?= (int)$settings['regular_hours_per_day'] ?>" min="1" max="24" required>
                        <span class="input-group-text">Jam</span>
                    </div>
                    <div class="form-text small">Standar: 8 jam</div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label fw-semibold small">Gaji Reguler (Rp/Jam)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="default_regular_rate" class="form-control" value="<?= (int)($settings['default_regular_rate'] ?? 15000) ?>" min="0" required>
                    </div>
                    <div class="form-text small">Tarif pokok dasar pegawai.</div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label fw-semibold small">Gaji Lembur (Rp/Jam)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="default_overtime_rate" class="form-control" value="<?= (int)$settings['default_overtime_rate'] ?>" min="0" required>
                    </div>
                    <div class="form-text small">Standar: Rp5.000 / jam</div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label fw-semibold small">Siklus / Periode Payroll</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="payroll_cycle_days" id="payrollCycleInput" class="form-control" value="<?= (int)$settings['payroll_cycle_days'] ?>" min="1" required>
                        <span class="input-group-text">Hari</span>
                    </div>
                    <div class="d-flex gap-1 mt-1">
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1" style="font-size: 0.7rem;" onclick="document.getElementById('payrollCycleInput').value=14">14 Hari</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1" style="font-size: 0.7rem;" onclick="document.getElementById('payrollCycleInput').value=30">30 Hari</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1" style="font-size: 0.7rem;" onclick="document.getElementById('payrollCycleInput').value=7">7 Hari</button>
                    </div>
                </div>

                <div class="col-12">
                    <div class="p-3 border rounded-3 bg-light text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-repeat text-primary fs-5 flex-shrink-0"></i>
                        <div class="small text-muted" style="font-size: 0.82rem;">
                            <strong class="text-dark">Sinkronisasi Otomatis:</strong> Tarif gaji reguler dan lembur yang diatur di atas akan langsung disinkronkan secara otomatis ke seluruh akun kasir aktif dan kalkulasi periode payroll yang sedang berjalan saat pengaturan disimpan (tanpa perlu konfirmasi manual).
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mb-3 text-wk-primary border-top pt-3"><i class="bi bi-pie-chart me-2"></i>Persentase Insentif & Bagi Hasil Pemegang Saham</h6>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Persentase Intensif Manager / Gaji Owner (%)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.5" name="manager_incentive_percent" class="form-control" value="<?= (float)($settings['manager_incentive_percent'] ?? 20.00) ?>" min="0" max="100" required>
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text small">Persentase dari Laba Kotor kedai untuk insentif manager / gaji owner (standar: 20%).</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Persentase Pemegang Saham / Dividen (%)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.5" name="shareholder_percent" class="form-control" value="<?= (float)($settings['shareholder_percent'] ?? 80.00) ?>" min="0" max="100" required>
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text small">Persentase dividen pemegang saham dari Laba Kotor (standar: 80%).</div>
                </div>
            </div>

            <div class="d-flex justify-content-end pt-3 border-top">
                <button type="submit" class="btn btn-wk-primary px-4 py-2">
                    <i class="bi bi-save me-1"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.theme-palette-card');
    const radios = document.querySelectorAll('.theme-radio-input');
    const customRadio = document.getElementById('themeCustomRadio');
    const colorPicker = document.getElementById('customColorPicker');
    const hexLabel = document.getElementById('customHexLabel');

    function updateCardHighlights() {
        cards.forEach(c => {
            const r = c.querySelector('input[type="radio"]');
            if (r && r.checked) {
                c.classList.add('border-primary', 'bg-primary-subtle');
                c.classList.remove('bg-wk-surface');
            } else {
                c.classList.remove('border-primary', 'bg-primary-subtle');
                c.classList.add('bg-wk-surface');
            }
        });
    }

    function applyFullThemeCss(t) {
        document.documentElement.style.setProperty('--wk-primary', t.primary);
        document.documentElement.style.setProperty('--wk-primary-rgb', t.primaryRgb);
        document.documentElement.style.setProperty('--wk-primary-dark', t.primaryDark);
        document.documentElement.style.setProperty('--wk-primary-light', t.primaryLight);
        document.documentElement.style.setProperty('--wk-bg', t.bg);
        document.documentElement.style.setProperty('--wk-surface', t.surface);
        document.documentElement.style.setProperty('--wk-surface-elevated', t.surfaceElevated);
        document.documentElement.style.setProperty('--wk-border', t.border);
        document.documentElement.style.setProperty('--wk-text', t.text);
        document.documentElement.style.setProperty('--wk-text-muted', t.textMuted);

        // Bootstrap CSS variables
        document.documentElement.style.setProperty('--bs-primary', t.primary);
        document.documentElement.style.setProperty('--bs-primary-rgb', t.primaryRgb);
        document.documentElement.style.setProperty('--bs-primary-bg-subtle', t.primaryLight);
        document.documentElement.style.setProperty('--bs-primary-border-subtle', t.primaryLight);
        document.documentElement.style.setProperty('--bs-primary-text-emphasis', t.primaryDark);
        document.documentElement.style.setProperty('--bs-link-color', t.primary);
        document.documentElement.style.setProperty('--bs-link-hover-color', t.primaryDark);
        document.documentElement.style.setProperty('--bs-focus-ring-color', `rgba(${t.primaryRgb}, 0.25)`);
        document.documentElement.style.setProperty('--bs-body-bg', t.bg);
        document.documentElement.style.setProperty('--bs-body-color', t.text);
        document.documentElement.style.setProperty('--bs-border-color', t.border);

        document.body.style.backgroundColor = t.bg;
    }

    radios.forEach(radio => {
        radio.addEventListener('change', () => {
            updateCardHighlights();
            if (radio.value !== 'custom') {
                const t = {
                    primary: radio.getAttribute('data-primary'),
                    primaryRgb: radio.getAttribute('data-primary-rgb') || '111, 78, 55',
                    primaryDark: radio.getAttribute('data-primary-dark'),
                    primaryLight: radio.getAttribute('data-primary-light'),
                    bg: radio.getAttribute('data-bg') || '#F8F5F2',
                    surface: radio.getAttribute('data-surface') || '#FFFFFF',
                    surfaceElevated: radio.getAttribute('data-surface-elevated') || '#FAF8F7',
                    border: radio.getAttribute('data-border') || '#E8E1DC',
                    text: radio.getAttribute('data-text') || '#2A211C',
                    textMuted: radio.getAttribute('data-text-muted') || '#73665E'
                };
                if (t.primary) applyFullThemeCss(t);
            } else if (colorPicker) {
                applyCustomHex(colorPicker.value);
            }
        });
    });

    function applyCustomHex(hex) {
        if (hexLabel) hexLabel.textContent = hex.toUpperCase();
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        const darkR = Math.max(0, Math.floor(r * 0.75)).toString(16).padStart(2, '0');
        const darkG = Math.max(0, Math.floor(g * 0.75)).toString(16).padStart(2, '0');
        const darkB = Math.max(0, Math.floor(b * 0.75)).toString(16).padStart(2, '0');
        const lightR = Math.min(255, Math.floor(r + (255 - r) * 0.85)).toString(16).padStart(2, '0');
        const lightG = Math.min(255, Math.floor(g + (255 - g) * 0.85)).toString(16).padStart(2, '0');
        const lightB = Math.min(255, Math.floor(b + (255 - b) * 0.85)).toString(16).padStart(2, '0');

        const bgR = Math.min(255, Math.floor(255 * 0.95 + r * 0.05)).toString(16).padStart(2, '0');
        const bgG = Math.min(255, Math.floor(255 * 0.95 + g * 0.05)).toString(16).padStart(2, '0');
        const bgB = Math.min(255, Math.floor(255 * 0.95 + b * 0.05)).toString(16).padStart(2, '0');

        const borderR = Math.min(255, Math.floor(255 * 0.83 + r * 0.17)).toString(16).padStart(2, '0');
        const borderG = Math.min(255, Math.floor(255 * 0.83 + g * 0.17)).toString(16).padStart(2, '0');
        const borderB = Math.min(255, Math.floor(255 * 0.83 + b * 0.17)).toString(16).padStart(2, '0');

        const textR = Math.floor(r * 0.2).toString(16).padStart(2, '0');
        const textG = Math.floor(g * 0.2).toString(16).padStart(2, '0');
        const textB = Math.floor(b * 0.2).toString(16).padStart(2, '0');

        const textMutedR = Math.floor(r * 0.5).toString(16).padStart(2, '0');
        const textMutedG = Math.floor(g * 0.5).toString(16).padStart(2, '0');
        const textMutedB = Math.floor(b * 0.5).toString(16).padStart(2, '0');

        applyFullThemeCss({
            primary: hex,
            primaryRgb: `${r}, ${g}, ${b}`,
            primaryDark: `#${darkR}${darkG}${darkB}`,
            primaryLight: `#${lightR}${lightG}${lightB}`,
            bg: `#${bgR}${bgG}${bgB}`,
            surface: '#FFFFFF',
            surfaceElevated: '#FAF8F7',
            border: `#${borderR}${borderG}${borderB}`,
            text: `#${textR}${textG}${textB}`,
            textMuted: `#${textMutedR}${textMutedG}${textMutedB}`
        });
    }

    if (colorPicker) {
        colorPicker.addEventListener('input', (e) => {
            if (customRadio) customRadio.checked = true;
            updateCardHighlights();
            applyCustomHex(e.target.value);
        });
    }
});
</script>
