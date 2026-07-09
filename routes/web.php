<?php

use App\Http\Controllers\DocumentDownloadController;
use App\Livewire;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(Auth::check() ? 'dashboard' : 'login'));

// Auth (tanpa 2FA — keputusan terkunci)
Route::middleware('guest')->group(function () {
    Route::get('/login', Livewire\Auth\Login::class)->name('login');
    Route::get('/register', Livewire\Auth\Register::class)->name('register');
});

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Livewire\Dashboard::class)
        ->middleware('permission:dashboard.read')->name('dashboard');

    // Peneliti
    Route::get('/proposal', Livewire\Proposal\Index::class)
        ->middleware('permission:proposal.read')->name('proposal.index');
    Route::get('/proposal/baru', Livewire\Proposal\Create::class)
        ->middleware('permission:proposal.create')->name('proposal.create');
    Route::get('/proposal/{proposal}', Livewire\Proposal\Show::class)
        ->name('proposal.show'); // otorisasi kepemilikan/unit di komponen

    // Antrian unit
    Route::get('/antrian/cru', Livewire\Antrian\Cru::class)
        ->middleware('permission:antrian-cru.read')->name('antrian.cru');
    Route::get('/antrian/kaji-etik', Livewire\Antrian\Kepk::class)
        ->middleware('permission:kaji-etik.read')->name('antrian.kepk');
    Route::get('/antrian/reviewer', Livewire\Antrian\Reviewer::class)
        ->middleware('permission:antrian-reviewer.read')->name('antrian.reviewer');

    // Admin
    Route::get('/admin/users', Livewire\Admin\Users::class)
        ->middleware('permission:users.read')->name('admin.users');
    Route::get('/admin/roles', Livewire\Admin\Roles::class)
        ->middleware('permission:roles.read')->name('admin.roles');
    Route::get('/admin/menus', Livewire\Admin\Menus::class)
        ->middleware('permission:menus.read')->name('admin.menus');
    Route::get('/admin/survey', Livewire\Admin\Survey::class)
        ->middleware('permission:master-survey.read')->name('admin.survey');
    Route::get('/admin/kontak', Livewire\Admin\Kontak::class)
        ->middleware('permission:informasi-kontak.read')->name('admin.kontak');

    // Laporan & audit
    Route::get('/laporan', Livewire\Laporan::class)
        ->middleware('permission:laporan.read')->name('laporan');
    Route::get('/audit-log', Livewire\AuditLog::class)
        ->middleware('permission:audit-log.read')->name('audit-log');

    // Unduhan dokumen ber-gate (survey gate untuk izin_final di controller)
    Route::get('/dokumen/{document}', DocumentDownloadController::class)->name('dokumen.download');
});
