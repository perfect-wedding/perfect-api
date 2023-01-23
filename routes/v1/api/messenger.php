<?php

use App\Http\Controllers\Api\v1\User\Messenger;
use Illuminate\Support\Facades\Route;

Route::name('messenger.')->prefix('messenger')->middleware('auth:sanctum')->controller(Messenger::class)->group(function () {
    Route::get('/conversations', 'conversations')->name('conversations');
    Route::post('/conversations/{id}/toggle-state', 'toggleState')->name('conversations.toggle.state');
    Route::get('/conversations/{id}/participants', 'participants')->name('conversations.participants');
    Route::get('/conversations/{id}/{mode?}', 'messages')->name('conversations.messages');
    Route::post('/conversations/{id}/{mode?}', 'create');
    Route::delete('/conversations/{id}/delete', 'delete')->name('conversations.delete');
    Route::get('/admin/support/{converstion_id?}', 'chatAdmin')->name('admin.support');
    Route::post('/admin/support/{converstion_id?}', 'chatAdmin');
});
