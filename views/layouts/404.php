<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-body-tertiary">
    <div class="text-center p-4">
        <h1 class="display-1 fw-bold text-wk-primary">404</h1>
        <h4 class="mb-3">Halaman Tidak Ditemukan</h4>
        <p class="text-muted mb-4">Halaman yang Anda tuju tidak tersedia atau telah dipindahkan.</p>
        <a href="<?= url('/') ?>" class="btn btn-wk-primary px-4 py-2">
            Kembali ke Beranda
        </a>
    </div>
</body>
</html>
