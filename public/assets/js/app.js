/**
 * WARUNG KAURE POS & MANAGEMENT SYSTEM
 * Global Client Script
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Dark Mode Initialization & Toggle
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const htmlElem = document.documentElement;

    const savedTheme = localStorage.getItem('wk_theme') || 'light';
    htmlElem.setAttribute('data-bs-theme', savedTheme);
    updateThemeIcon(savedTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElem.getAttribute('data-bs-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            htmlElem.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('wk_theme', newTheme);
            updateThemeIcon(newTheme);
        });
    }

    function updateThemeIcon(theme) {
        const icon = document.getElementById('themeToggleIcon');
        if (!icon) return;
        if (theme === 'dark') {
            icon.className = 'bi bi-sun-fill text-warning';
        } else {
            icon.className = 'bi bi-moon-stars-fill';
        }
    }

    // 2. Initialize DataTables if present
    if (window.$ && $.fn.DataTable) {
        $('.datatable').each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                let initialOrder = []; // default order: []
                const customOrder = $(this).attr('data-order');
                if (customOrder) {
                    try {
                        initialOrder = JSON.parse(customOrder);
                    } catch (e) {
                        console.warn('Invalid data-order JSON on table', e);
                    }
                }
                $(this).DataTable({
                    order: initialOrder, // Supports custom data-order or preserves server-side newest-first (order: [])
                    language: {
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        infoEmpty: "Tidak ada data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: "Data tidak ditemukan",
                        paginate: {
                            first: "Awal",
                            last: "Akhir",
                            next: "Berikutnya",
                            previous: "Sebelumnya"
                        }
                    },
                    pageLength: 10,
                    responsive: true,
                    ordering: true
                });
            }
        });
    }

    // 3. Confirm Delete / Void / Action via SweetAlert2
    document.querySelectorAll('.btn-confirm').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const title = this.getAttribute('data-title') || 'Apakah Anda yakin?';
            const text = this.getAttribute('data-text') || 'Tindakan ini tidak dapat dibatalkan.';
            const confirmBtnText = this.getAttribute('data-btn') || 'Ya, Lanjutkan';

            if (window.Swal) {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#6F4E37',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: confirmBtnText,
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (form) form.submit();
                        else if (this.href) window.location.href = this.href;
                    }
                });
            } else {
                if (confirm(title + '\n' + text)) {
                    if (form) form.submit();
                    else if (this.href) window.location.href = this.href;
                }
            }
        });
    });
});
