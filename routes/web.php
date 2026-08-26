<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FbrSubmissionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePreviewController;
use App\Http\Controllers\InvoiceUploadController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Application Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Invoice Upload & Parsing
    Route::get('/invoices/upload', [InvoiceUploadController::class, 'show'])->name('invoices.upload');
    Route::post('/invoices/upload', [InvoiceUploadController::class, 'store']);

    // Invoice Preview & Validation Breakdown
    Route::get('/invoices/preview', [InvoicePreviewController::class, 'show'])->name('invoices.preview');

    // FBR Transmission & Retries
    Route::get('/invoices/submit', [FbrSubmissionController::class, 'show'])->name('invoices.submit');
    Route::post('/invoices/submit', [FbrSubmissionController::class, 'submit']);
    Route::post('/invoices/retry', [FbrSubmissionController::class, 'retry'])->name('invoices.retry');

    // Invoice Archive / History & Detail
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/failed', [InvoiceController::class, 'failed'])->name('invoices.failed');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    // Reports & Analytics
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
});
