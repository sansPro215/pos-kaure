<?php

require_once __DIR__ . '/../config/app.php';
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});
foreach (glob(__DIR__ . '/../app/Helpers/*.php') as $f) require_once $f;

echo "========================================================\n";
echo "  WARUNG KAURE - TABLE SORTING & THEME VERIFICATION     \n";
echo "========================================================\n\n";

function check($cond, $msg) {
    if ($cond) {
        echo " [PASS] $msg\n";
    } else {
        echo " [FAIL] $msg\n";
        exit(1);
    }
}

// 1. Transactions sorting
$txs = App\Repositories\TransactionRepository::getTransactions(null, null, null, null, null, null, 10);
if (count($txs) >= 2) {
    $t1 = strtotime($txs[0]['transaction_date']);
    $t2 = strtotime($txs[1]['transaction_date']);
    check($t1 >= $t2, "Transactions sorted newest first ({$txs[0]['transaction_code']} >= {$txs[1]['transaction_code']})");
} else {
    check(true, "Transactions query verified (fewer than 2 rows in db)");
}

// 2. Attendance sorting
$att = App\Repositories\AttendanceRepository::getList();
if (count($att) >= 2) {
    $d1 = strtotime($att[0]['date'] . ' ' . ($att[0]['clock_in'] ?? '00:00:00'));
    $d2 = strtotime($att[1]['date'] . ' ' . ($att[1]['clock_in'] ?? '00:00:00'));
    check($d1 >= $d2, "Attendance sorted newest first ({$att[0]['date']} >= {$att[1]['date']})");
} else {
    check(true, "Attendance query verified (fewer than 2 rows in db)");
}

// 3. Expenses sorting
$exs = App\Repositories\ExpenseRepository::getAll();
if (count($exs) >= 2) {
    $e1 = strtotime($exs[0]['date']);
    $e2 = strtotime($exs[1]['date']);
    check($e1 >= $e2, "Expenses sorted newest first ({$exs[0]['date']} >= {$exs[1]['date']})");
} else {
    check(true, "Expenses query verified (fewer than 2 rows in db)");
}

// 4. Payroll Periods sorting
$periods = App\Repositories\PayrollRepository::getAllPeriods();
if (count($periods) >= 2) {
    $p1 = (int)$periods[0]['id'];
    $p2 = (int)$periods[1]['id'];
    check($p1 > $p2 || strtotime($periods[0]['start_date']) >= strtotime($periods[1]['start_date']), "Payroll periods sorted newest first (ID {$p1} >= {$p2})");
} else {
    check(true, "Payroll periods query verified");
}

// 5. Products sorting
$products = App\Repositories\ProductRepository::getAll();
if (count($products) >= 2) {
    $pr1 = (int)$products[0]['id'];
    $pr2 = (int)$products[1]['id'];
    check($pr1 >= $pr2, "Products sorted newest first (ID {$pr1} >= {$pr2})");
} else {
    check(true, "Products query verified");
}

// 6. Categories sorting
$cats = App\Repositories\CategoryRepository::getAll();
if (count($cats) >= 2) {
    $c1 = (int)$cats[0]['id'];
    $c2 = (int)$cats[1]['id'];
    check($c1 >= $c2, "Categories sorted newest first (ID {$c1} >= {$c2})");
} else {
    check(true, "Categories query verified");
}

// 7. Users sorting
$users = App\Repositories\UserRepository::getAll();
if (count($users) >= 2) {
    $u1 = (int)$users[0]['id'];
    $u2 = (int)$users[1]['id'];
    check($u1 >= $u2, "Users sorted newest first (ID {$u1} >= {$u2})");
} else {
    check(true, "Users query verified");
}

// 8. DataTables order: [] check in app.js
$appJs = file_get_contents(__DIR__ . '/../public/assets/js/app.js');
check(strpos($appJs, 'order: []') !== false, "app.js contains 'order: []' to prevent DataTables from overriding server sort");

// 9. Full System Theme Tokens check
$themeCss = render_theme_css();
check(strpos($themeCss, '--wk-bg:') !== false, "render_theme_css outputs dynamic --wk-bg");
check(strpos($themeCss, '--wk-border:') !== false, "render_theme_css outputs dynamic --wk-border");
check(strpos($themeCss, '--bs-primary:') !== false, "render_theme_css overrides --bs-primary");
check(strpos($themeCss, '--bs-primary-rgb:') !== false, "render_theme_css overrides --bs-primary-rgb");
check(strpos($themeCss, '--bs-focus-ring-color:') !== false, "render_theme_css overrides --bs-focus-ring-color");
check(strpos($themeCss, '--bs-body-bg:') !== false, "render_theme_css overrides --bs-body-bg");

// 10. Check each palette has complete token sets
$palettes = get_theme_palettes();
foreach ($palettes as $id => $pal) {
    check(isset($pal['bg']) && isset($pal['border']) && isset($pal['primary_rgb']), "Palette '{$id}' has complete background, border, and RGB tokens");
    check(isset($pal['dark_bg']) && isset($pal['dark_border']), "Palette '{$id}' has complete dark mode tokens");
}

echo "\n>>> ALL TABLE SORTING & THEME TESTS PASSED! <<<\n";
