<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Controllers\StaffInventoryController;
use App\Http\Controllers\StaffMoneyInventoryController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminMoneyInventoryController;
use App\Http\Controllers\Admin\AdminInventoryController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', function () {
    $user = request()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('staff.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('admin')
    ->middleware(['auth', 'verified', 'password.current', 'role:admin'])
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/details', [AdminOrderController::class, 'details'])->name('orders.details');
        Route::get('/orders/details-json', [AdminOrderController::class, 'detailsJson'])->name('orders.details-json');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/products/json', [AdminProductController::class, 'indexJson'])->name('products.index-json');
        Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::get('/products/{product}/edit-data', [AdminProductController::class, 'editData'])->name('products.edit-data');
        Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/json', [AdminCategoryController::class, 'indexJson'])->name('categories.index-json');
        Route::put('/categories', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('/money-inventory', [AdminMoneyInventoryController::class, 'index'])->name('money-inventory.index');

        Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/history', [AdminInventoryController::class, 'history'])->name('inventory.history');
        Route::post('/inventory/add-stock', [AdminInventoryController::class, 'addStock'])->name('inventory.add-stock');
        Route::post('/inventory/delete-stock', [AdminInventoryController::class, 'deleteStock'])->name('inventory.delete-stock');
        Route::post('/inventory/initialize', [AdminInventoryController::class, 'initializeInventory'])->name('inventory.initialize');
    });

Route::prefix('staff')
    ->middleware(['auth', 'verified', 'password.current', 'role:staff'])
    ->name('staff.')
    ->group(function () {
        Route::get('/dashboard', StaffDashboardController::class)->name('dashboard');

        Route::get('/money-inventory', [StaffMoneyInventoryController::class, 'index'])->name('money-inventory.index');
        Route::post('/money-inventory', [StaffMoneyInventoryController::class, 'save'])->name('money-inventory.save');
        Route::post('/money-inventory/payment-entry', [StaffMoneyInventoryController::class, 'storePaymentEntry'])->name('money-inventory.payment-entries.store');
        Route::put('/money-inventory/payment-entry/{entry}', [StaffMoneyInventoryController::class, 'updatePaymentEntry'])->name('money-inventory.payment-entries.update');
        Route::delete('/money-inventory/payment-entry/{entry}', [StaffMoneyInventoryController::class, 'deletePaymentEntry'])->name('money-inventory.payment-entries.destroy');
        Route::post('/money-inventory/reset-todays-sales', [StaffMoneyInventoryController::class, 'resetTodaysSales'])->name('money-inventory.reset-todays-sales');
        Route::post('/money-inventory/undo-reconcile', [StaffMoneyInventoryController::class, 'undoTodaysSalesReconciliation'])->name('money-inventory.undo-reconcile');

        Route::get('/inventory', [StaffInventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/delete-stock', [StaffInventoryController::class, 'deleteStock'])->name('inventory.delete-stock');
        Route::post('/inventory/add-stock', [StaffInventoryController::class, 'addStock'])->name('inventory.add-stock');
    });

Route::middleware(['auth', 'verified', 'password.current', 'role:staff'])
    ->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::get('/pos', [OrderController::class, 'pos'])->name('pos');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');

        Route::get('/orders/{order}/details', [OrderController::class, 'details'])->name('orders.details');
        Route::put('/orders/{order}/items/{item}', [OrderController::class, 'updateItem'])->name('orders.items.update');
        Route::delete('/orders/{order}/items/{item}', [OrderController::class, 'deleteItem'])->name('orders.items.delete');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/profile/pos-layout', [ProfileController::class, 'updatePosLayout'])
    ->middleware(['auth', 'verified', 'role:staff'])
    ->name('profile.pos-layout');

Route::post('/profile/clocked-in', [ProfileController::class, 'updateClockedIn'])
    ->middleware(['auth', 'verified', 'role:staff'])
    ->name('profile.clocked-in');

require __DIR__.'/auth.php';
