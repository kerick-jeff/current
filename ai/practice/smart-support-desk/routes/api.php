<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SupportController;

Route::middleware('auth:sanctum')
    ->controller(SupportController::class)
    ->prefix('support')
    ->group(function () {
    // Single ticket analysis — returns structured AI output immediately
    Route::post('support/tickets/{ticket}/analyze', 'analyze');

    // Conversational support chat
    Route::post('/support/chat', [SupportController::class, 'chat']);
    Route::post('/support/chat/{conversationId}/continue', [SupportController::class, 'continueChat']);

    // SSE streaming endpoint — returns raw streamed text, not structured output
    Route::get('/support/stream', [SupportController::class, 'stream']);

    // Bulk analysis — dispatches to queue, returns 202 immediately
    Route::post('/support/tickets/analyze/bulk', [SupportController::class, 'analyzeBulk']);
});
