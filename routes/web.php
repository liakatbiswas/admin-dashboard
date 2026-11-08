<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/dashboard', fn () => 'Dashboard OK: '.auth()->user()->name)
    ->middleware(['auth', 'verified'])->name('dashboard');
