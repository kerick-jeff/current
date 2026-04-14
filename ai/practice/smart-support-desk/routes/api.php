<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SupportController;

Route::middleware('auth:sanctum')
    ->controller(SupportController::class)
    ->prefix('support')
    ->group(function () {
    // Single ticket analysis — returns structured AI output immediately
    Route::post('notickets/{ticket}/analyze', 'analyze');


});
