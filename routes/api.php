<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\PublicSettingsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Superadmin\SuperadminAccountTypeController;
use App\Http\Controllers\Superadmin\SuperadminAppSettingController;
use App\Http\Controllers\Superadmin\SuperadminAuditLogController;
use App\Http\Controllers\Superadmin\SuperadminEmailLogController;
use App\Http\Controllers\Superadmin\SuperadminCategoryController;
use App\Http\Controllers\Superadmin\SuperadminCurrencyController;
use App\Http\Controllers\Superadmin\SuperadminDashboardController;
use App\Http\Controllers\Superadmin\SuperadminUserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/settings/public', [PublicSettingsController::class, 'index']);

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:5,1')->post('/register', [AuthController::class, 'register']);
    Route::middleware('throttle:5,1')->post('/login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

    Route::get('/google/redirect', [GoogleOAuthController::class, 'redirect']);
    Route::get('/google/callback', [GoogleOAuthController::class, 'callback']);

    Route::get('/sso/redirect', [SsoController::class, 'redirect']);
    Route::get('/sso/callback',  [SsoController::class, 'callback']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete']);

    // Profile
    Route::get('/profile',           [ProfileController::class, 'show']);
    Route::post('/profile',          [ProfileController::class, 'update']);
    Route::put('/profile/password',  [ProfileController::class, 'updatePassword']);
    Route::get('/profile/notification-preferences',  [ProfileController::class, 'getNotificationPreferences']);
    Route::put('/profile/notification-preferences',  [ProfileController::class, 'updateNotificationPreferences']);
    Route::delete('/profile',        [ProfileController::class, 'deactivate']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'stats']);

    // Accounts
    Route::get('/accounts',                   [AccountController::class, 'index']);
    Route::post('/accounts',                  [AccountController::class, 'store']);
    Route::put('/accounts/{id}',              [AccountController::class, 'update']);
    Route::patch('/accounts/{id}/archive',         [AccountController::class, 'archive']);
    Route::post('/accounts/{id}/adjust-balance',   [AccountController::class, 'adjustBalance']);
    Route::patch('/accounts/{id}/set-default',    [AccountController::class, 'setDefault']);
    Route::delete('/accounts/{id}',           [AccountController::class, 'destroy']);

    // Categories
    Route::get('/categories',         [CategoryController::class, 'index']);
    Route::post('/categories',        [CategoryController::class, 'store']);
    Route::put('/categories/{id}',    [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Transactions
    Route::get('/transactions',             [TransactionController::class, 'index']);
    Route::get('/transactions/export/csv',  [TransactionController::class, 'exportCsv']);
    Route::post('/transactions',            [TransactionController::class, 'store']);
    Route::get('/transactions/{id}',        [TransactionController::class, 'show']);
    Route::put('/transactions/{id}',    [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

    // Recurring Transactions
    Route::get('/recurring',         [RecurringTransactionController::class, 'index']);
    Route::post('/recurring',        [RecurringTransactionController::class, 'store']);
    Route::delete('/recurring/{id}', [RecurringTransactionController::class, 'destroy']);

    // Budgets
    Route::get('/budgets',         [BudgetController::class, 'index']);
    Route::post('/budgets',        [BudgetController::class, 'store']);
    Route::put('/budgets/{id}',    [BudgetController::class, 'update']);
    Route::delete('/budgets/{id}', [BudgetController::class, 'destroy']);

    // Notifications
    Route::get('/notifications',           [NotificationController::class, 'index']);
    Route::post('/notifications/mark-read',[NotificationController::class, 'markRead']);
    Route::delete('/notifications/{id}',   [NotificationController::class, 'destroy']);

    // Reports
    Route::get('/reports/summary',     [ReportController::class, 'summary']);
    Route::get('/reports/export/pdf',  [ReportController::class, 'exportPdf']);
    Route::get('/reports/export/csv',  [ReportController::class, 'exportCsv']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'stats']);

    Route::get('/users',          [AdminUserController::class, 'index']);
    Route::get('/users/{id}',     [AdminUserController::class, 'show']);
    Route::delete('/users/{id}',  [AdminUserController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/dashboard', [SuperadminDashboardController::class, 'stats']);

    Route::get('/users',                      [SuperadminUserController::class, 'index']);
    Route::get('/users/{id}',                 [SuperadminUserController::class, 'show']);
    Route::delete('/users/{id}',              [SuperadminUserController::class, 'destroy']);
    Route::post('/users/{id}/restore',        [SuperadminUserController::class, 'restore']);
    Route::patch('/users/{id}/toggle-status', [SuperadminUserController::class, 'toggleStatus']);

    Route::get('/currencies',        [SuperadminCurrencyController::class, 'index']);
    Route::post('/currencies',       [SuperadminCurrencyController::class, 'store']);
    Route::put('/currencies/{id}',   [SuperadminCurrencyController::class, 'update']);

    Route::get('/account-types',        [SuperadminAccountTypeController::class, 'index']);
    Route::post('/account-types',       [SuperadminAccountTypeController::class, 'store']);
    Route::put('/account-types/{id}',   [SuperadminAccountTypeController::class, 'update']);

    Route::get('/categories',        [SuperadminCategoryController::class, 'index']);
    Route::post('/categories',       [SuperadminCategoryController::class, 'store']);
    Route::put('/categories/{id}',   [SuperadminCategoryController::class, 'update']);
    Route::delete('/categories/{id}',[SuperadminCategoryController::class, 'destroy']);

    Route::get('/audit-logs',        [SuperadminAuditLogController::class, 'index']);

    Route::get('/logs/email',            [SuperadminEmailLogController::class, 'index']);
    Route::get('/logs/email/export/csv', [SuperadminEmailLogController::class, 'exportCsv']);
    Route::get('/logs/email/export/pdf', [SuperadminEmailLogController::class, 'exportPdf']);
    Route::get('/logs/email/{id}',       [SuperadminEmailLogController::class, 'show']);

    Route::get('/app-settings',      [SuperadminAppSettingController::class, 'index']);
    Route::put('/app-settings',      [SuperadminAppSettingController::class, 'update']);
});
