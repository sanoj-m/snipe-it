<?php

use App\Models\License;
use Illuminate\Support\Facades\Route;
use SnipeIt\FloatingLicenses\Http\Controllers\FloatingLicenseController;
use SnipeIt\FloatingLicenses\Http\Controllers\LicenseUsersController;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig;
use Tabuna\Breadcrumbs\Trail;

Route::get('/licenses/export-full', [LicenseUsersController::class, 'exportFull'])->name('floating-licenses.licenses.export-full');
Route::get('/licenses/{license}/users-export', [LicenseUsersController::class, 'exportUsers'])->name('floating-licenses.license.users-export');
Route::post('/licenses/{license}/users-import', [LicenseUsersController::class, 'importUsers'])->name('floating-licenses.license.users-import');

Route::group(['prefix' => 'floating-licenses', 'as' => 'floating-licenses.'], function () {
    Route::get('/', [FloatingLicenseController::class, 'index'])->name('index')
        ->breadcrumbs(fn (Trail $trail) => $trail->parent('licenses.index', route('licenses.index'))
            ->push(trans('floating-licenses::floating.title'), route('floating-licenses.index')));
    Route::get('/create', [FloatingLicenseController::class, 'create'])->name('create')
        ->breadcrumbs(fn (Trail $trail) => $trail->parent('floating-licenses.index', route('floating-licenses.index'))
            ->push(trans('floating-licenses::floating.enable'), route('floating-licenses.create')));
    Route::post('/licenses/{license}/enable', [FloatingLicenseController::class, 'store'])->name('store');
    Route::get('/{config}', [FloatingLicenseController::class, 'show'])->name('show')
        ->breadcrumbs(fn (Trail $trail, FloatingLicenseConfig $config) => $trail->parent('floating-licenses.index', route('floating-licenses.index'))
            ->push($config->license?->name ?? trans('floating-licenses::floating.title'), route('floating-licenses.show', $config)));
    Route::get('/{config}/edit', [FloatingLicenseController::class, 'edit'])->name('edit')
        ->breadcrumbs(fn (Trail $trail, FloatingLicenseConfig $config) => $trail->parent('floating-licenses.show', $config)
            ->push(trans('general.update'), route('floating-licenses.edit', $config)));
    Route::put('/{config}', [FloatingLicenseController::class, 'update'])->name('update');
    Route::delete('/{config}', [FloatingLicenseController::class, 'destroy'])->name('destroy');
    Route::post('/{config}/allocate', [FloatingLicenseController::class, 'allocate'])->name('allocate');
    Route::post('/allocations/{allocation}/release', [FloatingLicenseController::class, 'release'])->name('allocations.release');
    Route::post('/license/{license}/bulk-add', [FloatingLicenseController::class, 'bulkAddUsers'])->name('license.bulk-add');
    Route::post('/license/{license}/bulk-remove', [FloatingLicenseController::class, 'bulkRemoveUsers'])->name('license.bulk-remove');
    Route::get('/license/{license}/bulk-add', [FloatingLicenseController::class, 'bulkAddForm'])->name('license.bulk-add.form')
        ->breadcrumbs(fn (Trail $trail, License $license) => $trail->parent('licenses.show', $license)
            ->push(trans('floating-licenses::floating.bulk_add'), route('floating-licenses.license.bulk-add.form', $license)));
    Route::get('/license/{license}/bulk-remove', [FloatingLicenseController::class, 'bulkRemoveForm'])->name('license.bulk-remove.form')
        ->breadcrumbs(fn (Trail $trail, License $license) => $trail->parent('licenses.show', $license)
            ->push(trans('floating-licenses::floating.bulk_remove'), route('floating-licenses.license.bulk-remove.form', $license)));
});
