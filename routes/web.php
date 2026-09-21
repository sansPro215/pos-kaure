<?php

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\PosController;
use App\Controllers\ProductController;
use App\Controllers\CategoryController;
use App\Controllers\TransactionController;
use App\Controllers\ReportController;
use App\Controllers\UserController;
use App\Controllers\AttendanceController;
use App\Controllers\PayrollController;
use App\Controllers\ExpenseController;
use App\Controllers\AuditController;
use App\Controllers\SettingController;

// Root entry point
Router::get('/', function() {
    if (auth_check()) {
        if (is_owner()) {
            header('Location: ' . Router::url('/dashboard'));
        } else {
            header('Location: ' . Router::url('/pos'));
        }
    } else {
        header('Location: ' . Router::url('/login'));
    }
    exit;
});

// Authentication Routes
Router::get('/login', [AuthController::class, 'showLogin']);
Router::post('/login', [AuthController::class, 'login']);
Router::post('/logout', [AuthController::class, 'logout'], ['auth']);

// Owner Dashboard
Router::get('/dashboard', [DashboardController::class, 'index'], ['auth', 'role:OWNER']);
Router::get('/dashboard/print', [DashboardController::class, 'print'], ['auth', 'role:OWNER']);

// POS Routes (Available to Cashier and Owner)
Router::get('/pos', [PosController::class, 'index'], ['auth']);
Router::post('/pos/checkout', [PosController::class, 'checkout'], ['auth']);
Router::post('/pos/hold', [PosController::class, 'hold'], ['auth']);
Router::get('/pos/resume/{id}', [PosController::class, 'resume'], ['auth']);
Router::post('/pos/cancel-hold/{id}', [PosController::class, 'cancelHold'], ['auth']);

// Products Management (Owner only)
Router::get('/products', [ProductController::class, 'index'], ['auth', 'role:OWNER']);
Router::get('/products/create', [ProductController::class, 'create'], ['auth', 'role:OWNER']);
Router::post('/products/create', [ProductController::class, 'store'], ['auth', 'role:OWNER']);
Router::get('/products/{id}/edit', [ProductController::class, 'edit'], ['auth', 'role:OWNER']);
Router::post('/products/{id}/update', [ProductController::class, 'update'], ['auth', 'role:OWNER']);
Router::post('/products/{id}/delete', [ProductController::class, 'delete'], ['auth', 'role:OWNER']);

// Categories Management (Owner only)
Router::get('/categories', [CategoryController::class, 'index'], ['auth', 'role:OWNER']);
Router::post('/categories/create', [CategoryController::class, 'store'], ['auth', 'role:OWNER']);
Router::post('/categories/{id}/update', [CategoryController::class, 'update'], ['auth', 'role:OWNER']);
Router::post('/categories/{id}/delete', [CategoryController::class, 'delete'], ['auth', 'role:OWNER']);

// Stock & Inventory routes redirected to /products (Stock Module removed)
Router::get('/inventory', function() { header('Location: ' . \App\Core\Router::url('/products')); exit; }, ['auth', 'role:OWNER']);
Router::get('/stock', function() { header('Location: ' . \App\Core\Router::url('/products')); exit; }, ['auth', 'role:OWNER']);
Router::get('/ingredients', function() { header('Location: ' . \App\Core\Router::url('/products')); exit; }, ['auth', 'role:OWNER']);
Router::get('/recipes', function() { header('Location: ' . \App\Core\Router::url('/products')); exit; }, ['auth', 'role:OWNER']);
Router::get('/recipes/{id}', function() { header('Location: ' . \App\Core\Router::url('/products')); exit; }, ['auth', 'role:OWNER']);

// Transactions Management (Cashier & Owner)
Router::get('/transactions', [TransactionController::class, 'index'], ['auth']);
Router::get('/transactions/export-xlsx', [TransactionController::class, 'exportXlsx'], ['auth']);
Router::get('/transactions/export-csv', [TransactionController::class, 'exportCsv'], ['auth']);
Router::get('/transactions/{id}', [TransactionController::class, 'show'], ['auth']);
Router::post('/transactions/{id}/update', [TransactionController::class, 'update'], ['auth', 'role:OWNER']);
Router::get('/transactions/{id}/receipt', [PosController::class, 'receipt'], ['auth']);
Router::post('/transactions/{id}/void', [TransactionController::class, 'void'], ['auth', 'role:OWNER']);
Router::post('/transactions/{id}/refund', [TransactionController::class, 'refund'], ['auth', 'role:OWNER']);

// Reports (Owner only)
Router::get('/reports', function() {
    header('Location: ' . \App\Core\Router::url('/reports/sales'));
    exit;
}, ['auth', 'role:OWNER']);
Router::get('/reports/sales', [ReportController::class, 'sales'], ['auth', 'role:OWNER']);
Router::get('/reports/payments', [ReportController::class, 'payments'], ['auth', 'role:OWNER']);
Router::get('/reports/profit', [ReportController::class, 'profit'], ['auth', 'role:OWNER']);

// Operational Expenses (Cashier & Owner)
Router::get('/expenses', [ExpenseController::class, 'index'], ['auth']);
Router::post('/expenses/create', [ExpenseController::class, 'store'], ['auth']);
Router::post('/expenses/{id}/update', [ExpenseController::class, 'update'], ['auth']);
Router::post('/expenses/{id}/delete', [ExpenseController::class, 'delete'], ['auth']);

// Employee Accounts (Owner only)
Router::get('/users', [UserController::class, 'index'], ['auth', 'role:OWNER']);
Router::post('/users/create', [UserController::class, 'store'], ['auth', 'role:OWNER']);
Router::post('/users/{id}/update', [UserController::class, 'update'], ['auth', 'role:OWNER']);
Router::post('/users/{id}/reset-password', [UserController::class, 'resetPassword'], ['auth', 'role:OWNER']);
Router::post('/users/{id}/delete', [UserController::class, 'delete'], ['auth', 'role:OWNER']);

// Attendance (Both Cashier & Owner)
Router::get('/attendance', [AttendanceController::class, 'index'], ['auth']);
Router::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'], ['auth']);
Router::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'], ['auth']);
Router::post('/attendance/manual', [AttendanceController::class, 'storeManual'], ['auth', 'role:OWNER']);
Router::post('/attendance/{id}/update', [AttendanceController::class, 'update'], ['auth', 'role:OWNER']);
Router::post('/attendance/{id}/delete', [AttendanceController::class, 'delete'], ['auth', 'role:OWNER']);

// Payroll 14 Hari (Owner only)
Router::get('/payroll', [PayrollController::class, 'index'], ['auth', 'role:OWNER']);
Router::post('/payroll/create-period', [PayrollController::class, 'createPeriod'], ['auth', 'role:OWNER']);
Router::get('/payroll/{id}', [PayrollController::class, 'show'], ['auth', 'role:OWNER']);
Router::post('/payroll/{id}/generate', [PayrollController::class, 'generate'], ['auth', 'role:OWNER']);
Router::post('/payroll/{id}/update-item', [PayrollController::class, 'updateItem'], ['auth', 'role:OWNER']);
Router::post('/payroll/{id}/mark-paid', [PayrollController::class, 'markPaid'], ['auth', 'role:OWNER']);
Router::post('/payroll/{id}/close', [PayrollController::class, 'close'], ['auth', 'role:OWNER']);
Router::post('/payroll/{id}/delete', [PayrollController::class, 'deletePeriod'], ['auth', 'role:OWNER']);
Router::post('/payroll/item/{id}/delete', [PayrollController::class, 'deleteItem'], ['auth', 'role:OWNER']);
Router::get('/payroll/slip/{id}', [PayrollController::class, 'slip'], ['auth', 'role:OWNER']);
Router::get('/payroll/{id}/print', [PayrollController::class, 'printPeriodSummary'], ['auth', 'role:OWNER']);

// Settings & Audit Logs (Owner only)
Router::get('/settings', [SettingController::class, 'index'], ['auth', 'role:OWNER']);
Router::post('/settings', [SettingController::class, 'update'], ['auth', 'role:OWNER']);
Router::get('/audit', [AuditController::class, 'index'], ['auth', 'role:OWNER']);
Router::get('/audit/live-sessions', [AuditController::class, 'liveSessions'], ['auth', 'role:OWNER']);
Router::post('/audit/sessions/kill', [AuditController::class, 'killSession'], ['auth', 'role:OWNER']);
