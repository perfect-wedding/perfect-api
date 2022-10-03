<?php

use App\Http\Controllers\Api\v1\User\Messenger;
use Illuminate\Support\Facades\Route;

Route::name('messenger.')->prefix('messenger')->middleware('auth:sanctum')->controller(Messenger::class)->group(function () {
    Route::get('/conversations', 'conversations')->name('conversations');
    Route::get('/conversations/{id}/{mode?}', 'messages')->name('conversations.messages');
    Route::post('/conversations/{id}/{mode?}', 'create')->name('conversations.create');
    Route::get('/admin/support/{converstion_id?}', 'chatAdmin')->name('chat.admin');
    Route::post('/admin/support/{converstion_id?}', 'chatAdmin')->name('chat.admin.create');
});
