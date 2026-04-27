<?php
use App\Http\Controllers\ValuationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('valuation/{valuation}/update-ajax', [ValuationController::class, 'updateAjax'])->name('valuation.updateAjax');
    Route::post('valuation/{valuation}/duplicate', [ValuationController::class, 'duplicate'])->name('valuation.duplicate');
    Route::get('valuation/{valuation}/pdf', [ValuationController::class, 'pdf'])->name('valuation.pdf');
    Route::post('valuation/{valuation}/upload-pdf', [ValuationController::class, 'uploadPdf'])->name('valuation.uploadPdf');
    Route::get('valuation/report', [ValuationController::class, 'report'])->name('valuation.report');
    Route::resource('valuation', ValuationController::class);
});



require __DIR__.'/auth.php';
