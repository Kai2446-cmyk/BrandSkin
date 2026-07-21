<?php

use App\Http\Controllers\SkinAnalyzerController;
use Illuminate\Support\Facades\Route;

Route::get('/skin-analyzer', [SkinAnalyzerController::class, 'index'])->name('skin-analyzer.index');
Route::get('/skin-analyzer/start', [SkinAnalyzerController::class, 'start'])->name('skin-analyzer.start');
Route::get('/skin-analyzer/report', [SkinAnalyzerController::class, 'report'])->name('skin-analyzer.report');
Route::get('/skin-analyzer/recommendations', [SkinAnalyzerController::class, 'recommendations'])->name('skin-analyzer.recommendations');
Route::get('/profile/skin-diary', [SkinAnalyzerController::class, 'diary'])->name('profile.skin-diary');
