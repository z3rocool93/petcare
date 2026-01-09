<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
    Volt::route('mascotas', 'pets.index')->name('pets.index');
    Volt::route('agenda', 'appointments.index')->name('appointments.index');
    Volt::route('mascotas/{id}/historial', 'pets.history')->name('pets.history');
    Volt::route('/consultas', 'consultations.index')->name('consultations');
    Volt::route('/foro', 'forum.index')->name('forum.index');
    Volt::route('/membresias', 'pages.membership.index')->name('membership.index');
    Volt::route('/servicios', 'pages.services-info')->name('services.info');
    Volt::route('/veterinarias', 'pages.veterinarias')->name('veterinarias.index');
});
