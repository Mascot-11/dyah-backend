<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/test-twilio-env', function () {
    return response()->json([
        'TWILIO_SID' => env('TWILIO_SID'),
        'TWILIO_AUTH_TOKEN' => env('TWILIO_AUTH_TOKEN'),
        'TWILIO_WHATSAPP_FROM' => env('TWILIO_WHATSAPP_FROM'),
    ]);
});



//Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
//Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
//Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
//Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
